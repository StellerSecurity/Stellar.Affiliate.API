<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Affiliate;
use App\Models\AffiliateCommission;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Symfony\Component\HttpFoundation\Response;

class AffiliateEventController extends Controller
{
    /**
     * Handle normalized "order paid" events coming from the billing service.
     *
     * Expected JSON payload example:
     *
     * {
     *   "event": "order.paid",
     *   "order_id": 12345,
     *   "user_id": 678,
     *   "external_user_id": 678,
     *   "affiliate_id": 1,
     *   "affiliate_code": "123456",
     *   "product": "stellar_vpn",
     *   "plan": "annual",
     *   "amount": 59.99,
     *   "currency": "EUR",
     *   "is_initial": true,
     *   "subscription_id": 10
     * }
     *
     * Billing decides what to send. This service only cares about:
     * - which affiliate should get credit
     * - what is the order amount / currency
     * - is this initial or recurring
     */
    public function handleOrderPaid(Request $request): Response
    {
        $data = $request->validate([
            'event'           => 'required|string',     // e.g. "order.paid"
            'order_id'        => 'required|integer',
            'user_id'         => 'nullable|integer',
            'external_user_id'=> 'nullable|integer',
            'affiliate_id'    => 'nullable|integer',
            'affiliate_code'  => 'nullable|string',
            'product'         => 'required|string',
            'plan'            => 'required|string',
            'amount'          => 'required|numeric',
            'currency'        => 'required|string|size:3',
            'is_initial'      => 'required|boolean',
            'subscription_id' => 'nullable|integer',
        ]);

        // Resolve which affiliate gets the commission
        $affiliateId = $this->resolveAffiliateId($data);

        if (! $affiliateId) {
            // No affiliate found for this order. We just log and return 200 OK.
            Log::info('[AffiliateEvent] No affiliate found for order', [
                'order_id' => $data['order_id'],
                'user_id'  => $data['user_id'] ?? null,
            ]);

            return response()->json([
                'status'   => 'ignored',
                'reason'   => 'affiliate_not_found',
                'order_id' => $data['order_id'],
            ]);
        }

        $type = $data['is_initial'] ? 'initial' : 'recurring';

        // Commission rates can be configured via env:
        // AFFILIATE_INITIAL_RATE (default 1.0)
        // AFFILIATE_RECURRING_RATE (default 0.60)
        $initialRate   = (float) env('AFFILIATE_INITIAL_RATE', 1.0);
        $recurringRate = (float) env('AFFILIATE_RECURRING_RATE', 0.60);

        $rate = $data['is_initial'] ? $initialRate : $recurringRate;

        $commissionAmount = round($data['amount'] * $rate, 2);

        // Idempotency: do not create duplicate commissions for same order + type
        $existing = AffiliateCommission::where('affiliate_id', $affiliateId)
            ->where('order_id', $data['order_id'])
            ->where('type', $type)
            ->first();

        if ($existing) {
            Log::info('[AffiliateEvent] Duplicate commission ignored', [
                'order_id'     => $data['order_id'],
                'affiliate_id' => $affiliateId,
                'type'         => $type,
            ]);

            return response()->json([
                'status'         => 'duplicate',
                'commission_id'  => $existing->id,
                'affiliate_id'   => $affiliateId,
                'order_id'       => $data['order_id'],
            ]);
        }

        // Refund window (days) before commission is eligible for payout
        $refundDays = (int) env('AFFILIATE_REFUND_DAYS', 14);

        $commission = AffiliateCommission::create([
            'affiliate_id'       => $affiliateId,
            'order_id'           => $data['order_id'],
            'subscription_id'    => $data['subscription_id'] ?? null,
            'type'               => $type,
            'rate'               => $rate,
            'amount'             => $commissionAmount,
            'currency'           => strtoupper($data['currency']),
            'status'             => 'pending',
            'payout_id'          => null,
            'eligible_payout_at' => now()->addDays($refundDays),
        ]);

        Log::info('[AffiliateEvent] Commission created', [
            'commission_id' => $commission->id,
            'affiliate_id'  => $affiliateId,
            'order_id'      => $data['order_id'],
            'type'          => $type,
            'amount'        => $commissionAmount,
            'rate'          => $rate,
        ]);

        return response()->json([
            'status'         => 'ok',
            'commission_id'  => $commission->id,
            'affiliate_id'   => $affiliateId,
            'order_id'       => $data['order_id'],
            'type'           => $type,
            'rate'           => $rate,
            'amount'         => $commissionAmount,
            'eligible_after' => $commission->eligible_payout_at,
        ]);
    }

    /**
     * Resolve affiliate_id from incoming event data.
     *
     * Priority:
     *  1) explicit affiliate_id from billing
     *  2) affiliate_code (public_code)
     *  3) external_user_id match on affiliates.external_user_id
     */
    protected function resolveAffiliateId(array $data): ?int
    {
        // 1) If billing sends affiliate_id directly, we trust that
        if (! empty($data['affiliate_id'])) {
            $affiliate = Affiliate::find($data['affiliate_id']);
            if ($affiliate) {
                return $affiliate->id;
            }
        }

        // 2) If billing sends affiliate_code (e.g. "BLERIM01")
        if (! empty($data['affiliate_code'])) {
            $affiliate = Affiliate::where('public_code', $data['affiliate_code'])->first();
            if ($affiliate) {
                return $affiliate->id;
            }
        }

        // 3) If billing sends external_user_id
        if (! empty($data['external_user_id'])) {
            $affiliate = Affiliate::where('external_user_id', $data['external_user_id'])->first();
            if ($affiliate) {
                return $affiliate->id;
            }
        }

        return null;
    }
}
