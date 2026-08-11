<?php

namespace App\Providers;

use App\Models\Affiliate;
use App\Models\AffiliateCampaign;
use App\Models\AffiliateCommission;
use App\Models\AffiliateInstallToken;
use App\Models\AffiliateSession;
use App\Services\AffiliateCommissionPolicy;
use App\Services\AffiliateOrderService;
use App\Support\CommissionMath;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\URL;
use Illuminate\Support\ServiceProvider;
use Throwable;

class AppServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        //
    }

    public function boot(): void
    {
        $appUrl = strtolower((string) config('app.url'));

        if (str_starts_with($appUrl, 'https://')) {
            URL::forceScheme('https');
        }

        Affiliate::created(function (Affiliate $affiliate): void {
            app(AffiliateCommissionPolicy::class)->ensureAffiliateEsimRate((int) $affiliate->id);
        });

        AffiliateCampaign::creating(function (AffiliateCampaign $campaign): void {
            if ((int) $campaign->affiliate_id <= 0
                || ! Schema::hasColumn('affiliate_campaigns', 'product')
                || ! Schema::hasColumn('affiliate_campaigns', 'commission_rate')) {
                return;
            }

            $policy = app(AffiliateCommissionPolicy::class);

            if (! $campaign->product) {
                $requestedProduct = request()->input('product', request()->query('product', 'esim'));
                $normalizedProduct = $policy->normalizeProduct(is_string($requestedProduct) ? $requestedProduct : 'esim');
                $campaign->product = in_array($normalizedProduct, ['esim', 'vpn', 'antivirus'], true)
                    ? $normalizedProduct
                    : 'esim';
            } else {
                $normalizedProduct = $policy->normalizeProduct((string) $campaign->product);
                $campaign->product = in_array($normalizedProduct, ['esim', 'vpn', 'antivirus'], true)
                    ? $normalizedProduct
                    : 'esim';
            }

            if ($campaign->commission_rate === null
                && in_array($campaign->product, ['esim', 'vpn', 'antivirus'], true)) {
                $resolved = $policy->effectiveRate(
                    (int) $campaign->affiliate_id,
                    (string) $campaign->product,
                    'initial'
                );

                $campaign->commission_rate = (float) $resolved['rate'];
            }

            if (Schema::hasColumn('affiliate_campaigns', 'redirect_url') && ! $campaign->redirect_url) {
                $requestedRedirect = request()->input('redirect_url', request()->query('redirect'));
                $requestedRedirect = is_string($requestedRedirect) ? trim($requestedRedirect) : '';
                $validRequestedRedirect = filter_var($requestedRedirect, FILTER_VALIDATE_URL)
                    && in_array(strtolower((string) parse_url($requestedRedirect, PHP_URL_SCHEME)), ['http', 'https'], true);

                $campaign->redirect_url = $validRequestedRedirect
                    ? $requestedRedirect
                    : (string) config(
                        'affiliate.products.'.((string) $campaign->product).'.default_redirect_url',
                        config('affiliate.products.esim.default_redirect_url')
                    );
            }
        });

        AffiliateCommission::creating(function (AffiliateCommission $commission): void {
            $request = request();

            if ($request->route()?->getName() !== 'affiliate.events.order_paid') {
                return;
            }

            $affiliateId = (int) $commission->affiliate_id;
            if ($affiliateId <= 0) {
                return;
            }

            $type = $commission->type === 'recurring' ? 'recurring' : 'initial';
            $rawProduct = (string) ($request->attributes->get('affiliate_commission_product') ?: $request->input('product', 'unknown'));
            $orderId = trim((string) $commission->getAttribute('order_id'));
            $incomingAmount = CommissionMath::money((string) $request->input('amount', 0));
            $orderAmount = $incomingAmount;
            $orderCurrency = strtoupper(trim((string) ($commission->currency ?: $request->input('currency', 'EUR'))));
            $orderTotalSource = 'event_fallback';

            if ($orderId !== '') {
                try {
                    $commerceTotal = app(AffiliateOrderService::class)->getCommissionTotal($orderId);
                    $commerceOrderAmount = CommissionMath::fromCents((int) $commerceTotal['grand_total_cents']);

                    if ($incomingAmount !== $commerceOrderAmount) {
                        Log::warning('[AffiliateCommission] Event amount differs from Commerce grand total', [
                            'order_id' => $orderId,
                            'affiliate_id' => $affiliateId,
                            'event_amount' => $incomingAmount,
                            'commerce_grand_total' => $commerceOrderAmount,
                        ]);
                    }

                    $orderAmount = $commerceOrderAmount;
                    $orderCurrency = (string) $commerceTotal['currency'];
                    $orderTotalSource = 'commerce_grand_total';
                } catch (Throwable $exception) {
                    Log::warning('[AffiliateCommission] Commerce total unavailable; event amount retained for later reconciliation', [
                        'order_id' => $orderId,
                        'affiliate_id' => $affiliateId,
                        'event_amount' => $incomingAmount,
                        'exception' => $exception::class,
                        'message' => $exception->getMessage(),
                    ]);
                }
            } else {
                Log::warning('[AffiliateCommission] Order ID missing; event amount retained for later reconciliation', [
                    'affiliate_id' => $affiliateId,
                    'event_amount' => $incomingAmount,
                ]);
            }

            $request->attributes->set('affiliate_commission_order_total_source', $orderTotalSource);

            $policy = app(AffiliateCommissionPolicy::class);
            $product = $policy->normalizeProduct($rawProduct);
            $resolved = $policy->effectiveRate($affiliateId, $product, $type);
            $rate = max(0, min(1, (float) $resolved['rate']));

            $commission->campaign_id = $this->resolveCampaignIdForCommission($commission, $affiliateId);
            $commission->product = $product;
            $commission->order_amount = $orderAmount;
            $commission->rate = $rate;
            $commission->rate_source = (string) $resolved['source'];
            if ($orderCurrency !== '') {
                $commission->currency = $orderCurrency;
            }
            $commission->amount = CommissionMath::calculate($orderAmount, $rate);
        });

        AffiliateCommission::created(function (AffiliateCommission $commission): void {
            if (request()->route()?->getName() !== 'affiliate.events.order_paid') {
                return;
            }

            Log::info('[AffiliateCommissionPolicy] Effective commission applied', [
                'commission_id' => $commission->id,
                'affiliate_id' => $commission->affiliate_id,
                'campaign_id' => $commission->campaign_id,
                'product' => $commission->product,
                'type' => $commission->type,
                'order_amount' => (string) $commission->order_amount,
                'rate' => (float) $commission->rate,
                'amount' => (string) $commission->amount,
                'rate_source' => $commission->rate_source,
                'order_total_source' => request()->attributes->get('affiliate_commission_order_total_source', 'event_fallback'),
            ]);
        });
    }

    private function resolveCampaignIdForCommission(AffiliateCommission $commission, int $affiliateId): ?int
    {
        $request = request();

        $sessionToken = trim((string) $request->input('session_token', ''));
        if ($sessionToken !== '') {
            $campaignId = AffiliateSession::query()
                ->where('affiliate_id', $affiliateId)
                ->where('session_token', $sessionToken)
                ->value('campaign_id');

            if ($campaignId) {
                return (int) $campaignId;
            }
        }

        $installToken = trim((string) $request->input('install_token', ''));
        if ($installToken !== '') {
            $campaignId = AffiliateInstallToken::query()
                ->where('affiliate_id', $affiliateId)
                ->where('install_token', $installToken)
                ->value('campaign_id');

            if ($campaignId) {
                return (int) $campaignId;
            }
        }

        $subscriptionId = trim((string) $commission->getRawOriginal('subscription_id'));
        if ($subscriptionId !== '') {
            $campaignId = AffiliateCommission::query()
                ->where('affiliate_id', $affiliateId)
                ->where('subscription_id', $subscriptionId)
                ->whereNotNull('campaign_id')
                ->oldest('id')
                ->value('campaign_id');

            if ($campaignId) {
                return (int) $campaignId;
            }
        }

        return null;
    }
}
