<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\AffiliateCommission;
use App\Models\AffiliateSession;
use App\Models\AffiliateInstallToken;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Log;

class AffiliateEventController extends Controller
{
    /**
     * Handle normalized "order paid" events coming from billing / webhooks.
     *
     * Expected JSON payload (new style):
     *
     * {
     *   "order_id": 12345,
     *   "user_id": 678,
     *   "subscription_id": 10,
     *   "amount": 59.99,
     *   "currency": "EUR",
     *   "product": "stellar_vpn",
     *   "source": "web_checkout",
     *   "gateway": "stripe",
     *   "is_recurring": false,
     *   "session_token": "abc123...",      // web
     *   "install_token": null              // apps (optional)
     * }
     */
    public function handleOrderPaid(Request $request): JsonResponse
    {
        $data = $request->validate([
            // Optional generic marker
            'event'           => 'sometimes|string',

            // Core billing identifiers
            'order_id'        => 'required|integer',
            'user_id'         => 'nullable|integer',
            'subscription_id' => 'nullable|integer',

            // Money and product
            'amount'          => 'required|numeric',
            'currency'        => 'required|string|size:3',
            'product'         => 'required|string',
            'source'          => 'nullable|string|max:100',
            'gateway'         => 'nullable|string|max:100',

            // Recurrence + attribution
            'is_recurring'    => 'required|boolean',
            'session_token'   => 'nullable|string|max:255',
            'install_token'   => 'nullable|string|max:255',
        ]);

        $isRecurring = (bool) $data['is_recurring'];
        $type        = $isRecurring ? 'recurring' : 'initial';

        // Commission rates from env
        $initialRate   = (float) env('AFFILIATE_INITIAL_RATE', 1.0);   // 100% first
        $recurringRate = (float) env('AFFILIATE_RECURRING_RATE', 0.60); // 60% recurring

        $rate = $isRecurring ? $recurringRate : $initialRate;

        // Pure v2 attribution: install_token → session_token
        $affiliateId = $this->resolveAffiliateId($data);

        if (! $affiliateId) {
            Log::info('[AffiliateEvent] No affiliate found for order', [
                'order_id'      => $data['order_id'],
                'user_id'       => $data['user_id'] ?? null,
                'session_token' => $data['session_token'] ?? null,
                'install_token' => $data['install_token'] ?? null,
            ]);

            return response()->json([
                'status'   => 'ignored',
                'reason'   => 'affiliate_not_found',
                'order_id' => $data['order_id'],
            ]);
        }

        $commissionAmount = round($data['amount'] * $rate, 2);

        // Idempotency: avoid duplicate commission for same order + type
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
                'type'           => $type,
            ]);
        }

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
     *  1) install_token (apps)
     *  2) session_token (web)
     */
    protected function resolveAffiliateId(array $data): ?int
    {
        // 1) install_token attribution (apps)
        if (! empty($data['install_token'])) {
            $install = AffiliateInstallToken::where('install_token', $data['install_token'])
                ->latest()
                ->first();

            if ($install && $install->affiliate_id) {
                return (int) $install->affiliate_id;
            }
        }

        // 2) session_token attribution (web flows)
        if (! empty($data['session_token'])) {
            $session = AffiliateSession::where('session_token', $data['session_token'])
                ->where('expires_at', '>', now())
                ->first();

            if ($session && $session->affiliate_id) {
                return (int) $session->affiliate_id;
            }
        }

        return null;
    }
}
