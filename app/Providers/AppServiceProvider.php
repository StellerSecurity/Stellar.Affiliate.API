<?php

namespace App\Providers;

use App\Models\Affiliate;
use App\Models\AffiliateCampaign;
use App\Models\AffiliateCommission;
use App\Models\AffiliateInstallToken;
use App\Models\AffiliateSession;
use App\Services\AffiliateCommissionPolicy;
use App\Support\CommissionMath;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        //
    }

    public function boot(): void
    {
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
            $orderAmount = (float) $request->input('amount', 0);

            $policy = app(AffiliateCommissionPolicy::class);
            $product = $policy->normalizeProduct($rawProduct);
            $resolved = $policy->effectiveRate($affiliateId, $product, $type);
            $rate = max(0, min(1, (float) $resolved['rate']));

            $commission->campaign_id = $this->resolveCampaignIdForCommission($commission, $affiliateId);
            $commission->product = $product;
            $commission->order_amount = round($orderAmount, 2);
            $commission->rate = $rate;
            $commission->rate_source = (string) $resolved['source'];
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
                'rate' => (float) $commission->rate,
                'amount' => (float) $commission->amount,
                'rate_source' => $commission->rate_source,
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
