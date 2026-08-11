<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\AffiliateCommission;
use App\Models\AffiliateInstallToken;
use App\Models\AffiliateSession;
use App\Services\AffiliateCommissionPolicy;
use App\Services\AffiliateOrderService;
use App\Support\CommissionMath;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Throwable;

class AffiliateEventController extends Controller
{
    /**
     * Handle normalized order-paid events from Commerce / billing.
     */
    public function handleOrderPaid(
        Request $request,
        AffiliateCommissionPolicy $policy,
        AffiliateOrderService $orders
    ): JsonResponse {
        $data = $request->validate([
            'order_id' => 'required',
            'user_id' => 'nullable',
            'subscription_id' => 'nullable',
            'amount' => 'required|numeric|min:0',
            'currency' => 'required|string|size:3',
            'product' => 'required|string',
            'source' => 'nullable|string|max:100',
            'gateway' => 'nullable|string|max:100',
            'is_recurring' => 'required|boolean',
            'session_token' => 'nullable|string|max:255',
            'install_token' => 'nullable|string|max:255',
            'external_payment_id' => 'nullable|string|max:255',
        ]);

        $type = (bool) $data['is_recurring'] ? 'recurring' : 'initial';
        $attribution = $this->resolveAttribution($data);
        $affiliateId = $attribution['affiliate_id'] ?? null;
        $campaignId = $attribution['campaign_id'] ?? null;

        if (! $affiliateId) {
            Log::info('[AffiliateEvent] No affiliate found for order', [
                'order_id' => $data['order_id'],
                'user_id' => $data['user_id'] ?? null,
                'session_token' => $data['session_token'] ?? null,
                'install_token' => $data['install_token'] ?? null,
            ]);

            return response()->json([
                'status' => 'ignored',
                'reason' => 'affiliate_not_found',
                'order_id' => $data['order_id'],
            ]);
        }

        // Legacy links predate product tagging and are eSIM links. Normalize every
        // incoming product before resolving the commission policy.
        $product = $policy->normalizeProduct((string) ($data['product'] ?? 'esim'));
        if (! in_array($product, ['esim', 'vpn', 'antivirus'], true)) {
            $product = 'esim';
        }

        // The commission rate is resolved from the affiliate/product policy here,
        // before the commission is created. Never use the legacy env 100% fallback.
        $resolvedRate = $policy->effectiveRate($affiliateId, $product, $type);
        $rate = number_format(max(0, min(1, (float) $resolvedRate['rate'])), 4, '.', '');

        $orderAmount = CommissionMath::money((string) $data['amount']);
        $currency = strtoupper((string) $data['currency']);
        $orderTotalSource = 'event_fallback';

        // Commerce grand total is authoritative when available because it includes
        // discounts. Commerce outages must not drop conversions, so event amount is
        // retained as a fail-open fallback and can be reconciled later.
        try {
            $commerce = $orders->getCommissionTotal((string) $data['order_id']);
            $orderAmount = CommissionMath::fromCents((int) $commerce['grand_total_cents']);
            $currency = strtoupper((string) $commerce['currency']);
            $orderTotalSource = 'commerce_grand_total';
        } catch (Throwable $exception) {
            Log::warning('[AffiliateEvent] Commerce total unavailable; event amount retained', [
                'order_id' => $data['order_id'],
                'affiliate_id' => $affiliateId,
                'event_amount' => $orderAmount,
                'exception' => $exception::class,
                'message' => $exception->getMessage(),
            ]);
        }

        $commissionAmount = CommissionMath::calculate($orderAmount, $rate);

        // Share the already-resolved values with the model-level invariant so it
        // can validate the same policy without issuing a second Commerce request.
        $request->attributes->set('affiliate_commission_product', $product);
        $request->attributes->set('affiliate_commission_order_total_source', $orderTotalSource);

        // Idempotency prefers the external payment ID. If it is absent, fall back to
        // the order ID so null external IDs cannot collapse unrelated conversions.
        $existingQuery = AffiliateCommission::query()
            ->where('affiliate_id', $affiliateId)
            ->where('type', $type);

        if (! empty($data['external_payment_id'])) {
            $existingQuery->where('external_payment_id', $data['external_payment_id']);
        } else {
            $existingQuery->where('order_id', $data['order_id']);
        }

        $existing = $existingQuery->first();

        if ($existing) {
            if (! $existing->campaign_id && $campaignId) {
                $existing->campaign_id = $campaignId;
                $existing->save();
            }

            Log::info('[AffiliateEvent] Duplicate commission ignored', [
                'order_id' => $data['order_id'],
                'affiliate_id' => $affiliateId,
                'type' => $type,
                'external_payment_id' => $data['external_payment_id'] ?? null,
                'commission_id' => $existing->id,
            ]);

            return response()->json([
                'status' => 'duplicate',
                'commission_id' => $existing->id,
                'affiliate_id' => $affiliateId,
                'order_id' => $data['order_id'],
                'type' => $type,
                'rate' => (float) $existing->rate,
                'amount' => (float) $existing->amount,
            ]);
        }

        $refundDays = (int) env('AFFILIATE_REFUND_DAYS', 14);

        $commission = AffiliateCommission::create([
            'affiliate_id' => $affiliateId,
            'campaign_id' => $campaignId,
            'order_id' => $data['order_id'],
            'subscription_id' => $data['subscription_id'] ?? null,
            'product' => $product,
            'order_amount' => $orderAmount,
            'type' => $type,
            'rate' => $rate,
            'rate_source' => (string) $resolvedRate['source'],
            'amount' => $commissionAmount,
            'currency' => $currency,
            'status' => 'pending',
            'payout_id' => null,
            'eligible_payout_at' => now()->addDays($refundDays),
            'external_payment_id' => $data['external_payment_id'] ?? null,
        ]);

        Log::info('[AffiliateEvent] Commission created', [
            'commission_id' => $commission->id,
            'affiliate_id' => $affiliateId,
            'campaign_id' => $campaignId,
            'order_id' => $data['order_id'],
            'product' => $product,
            'type' => $type,
            'order_amount' => (string) $commission->order_amount,
            'rate' => (string) $commission->rate,
            'amount' => (string) $commission->amount,
            'rate_source' => $commission->rate_source,
            'order_total_source' => $orderTotalSource,
        ]);

        return response()->json([
            'status' => 'ok',
            'commission_id' => $commission->id,
            'affiliate_id' => $affiliateId,
            'order_id' => $data['order_id'],
            'type' => $type,
            'rate' => (float) $commission->rate,
            'amount' => (float) $commission->amount,
            'eligible_after' => $commission->eligible_payout_at,
        ]);
    }

    /**
     * Resolve the affiliate and campaign from the attribution token.
     *
     * Priority:
     *  1) install_token (apps)
     *  2) session_token (web)
     */
    protected function resolveAttribution(array $data): array
    {
        if (! empty($data['install_token'])) {
            $install = AffiliateInstallToken::where('install_token', $data['install_token'])
                ->latest()
                ->first();

            if ($install && $install->affiliate_id) {
                return [
                    'affiliate_id' => (int) $install->affiliate_id,
                    'campaign_id' => $install->campaign_id ? (int) $install->campaign_id : null,
                ];
            }
        }

        if (! empty($data['session_token'])) {
            $session = AffiliateSession::where('session_token', $data['session_token'])
                ->where('expires_at', '>', now())
                ->first();

            if ($session && $session->affiliate_id) {
                return [
                    'affiliate_id' => (int) $session->affiliate_id,
                    'campaign_id' => $session->campaign_id ? (int) $session->campaign_id : null,
                ];
            }
        }

        return [
            'affiliate_id' => null,
            'campaign_id' => null,
        ];
    }
}
