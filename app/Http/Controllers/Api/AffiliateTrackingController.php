<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\AffiliateInstallToken;
use App\Models\AffiliateSession;
use App\Models\AffiliateCommission;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class AffiliateEventController extends Controller
{
    /**
     * Handle normalized "order paid" event from billing service.
     *
     * Expected JSON payload example:
     *
     * {
     *   "order_id": 12345,
     *   "user_id": 998,
     *   "subscription_id": 551,
     *   "amount": 34.99,
     *   "currency": "EUR",
     *   "product": "vpn",
     *   "source": "app_ios",
     *   "gateway": "stripe",
     *   "is_recurring": false,
     *   "session_token": "web_session_token_if_any",
     *   "install_token": "ins_82SDks82ksB2"
     * }
     */
    public function handleOrderPaid(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'order_id'        => ['required', 'numeric'],
            'user_id'         => ['nullable', 'numeric'],
            'subscription_id' => ['nullable', 'numeric'],
            'amount'          => ['required', 'numeric'],
            'currency'        => ['required', 'string', 'max:10'],
            'product'         => ['nullable', 'string', 'max:50'],
            'source'          => ['nullable', 'string', 'max:100'],
            'gateway'         => ['nullable', 'string', 'max:50'],
            'is_recurring'    => ['nullable', 'boolean'],
            'session_token'   => ['nullable', 'string', 'max:255'],
            'install_token'   => ['nullable', 'string', 'max:100'],
            'platform'        => ['nullable', 'string', 'max:50'],
        ]);

        $orderId        = (int) $validated['order_id'];
        $userId         = $validated['user_id'] ?? null;
        $subscriptionId = $validated['subscription_id'] ?? null;
        $amount         = (float) $validated['amount'];
        $currency       = strtoupper($validated['currency']);
        $product        = $validated['product'] ?? null;
        $source         = $validated['source'] ?? null;
        $gateway        = $validated['gateway'] ?? null;
        $isRecurring    = (bool) ($validated['is_recurring'] ?? false);
        $sessionToken   = $validated['session_token'] ?? null;
        $installToken   = $validated['install_token'] ?? null;
        $platform       = $validated['platform'] ?? null;

        // If order already has a commission, do not create a duplicate.
        $existing = AffiliateCommission::where('order_id', $orderId)->first();
        if ($existing) {
            return response()->json([
                'status'    => 'skipped',
                'reason'    => 'commission_already_exists',
                'order_id'  => $orderId,
                'commission_id' => $existing->id,
            ], 200);
        }

        $attribution = null;

        try {
            // First: try install_token (Option B: app installs and in-app billing)
            if ($installToken) {
                $attribution = $this->resolveFromInstallToken(
                    $installToken,
                    $userId,
                    $subscriptionId,
                    $platform
                );
            }

            // Fallback: try web session token (cookie based flow)
            if (!$attribution && $sessionToken) {
                $attribution = $this->resolveFromSessionToken($sessionToken);
            }

            // If nothing found, no affiliate should be rewarded.
            if (!$attribution) {
                Log::info('AffiliateEventController: no affiliate attribution for order', [
                    'order_id'      => $orderId,
                    'install_token' => $installToken,
                    'session_token' => $sessionToken,
                ]);

                return response()->json([
                    'status'   => 'skipped',
                    'reason'   => 'no_affiliate_found',
                    'order_id' => $orderId,
                ], 200);
            }

            // Commission rate (40% as discussed; can be adjusted via config)
            $commissionRate = (float) config('affiliate.default_commission_rate', 0.40);
            $commissionAmount = round($amount * $commissionRate, 2);

            $isInitial   = ! $isRecurring;
            $isRecurringFlag = $isRecurring;

            $commission = DB::transaction(function () use (
                $orderId,
                $subscriptionId,
                $amount,
                $currency,
                $product,
                $source,
                $gateway,
                $commissionRate,
                $commissionAmount,
                $isInitial,
                $isRecurringFlag,
                $attribution
            ) {
                return AffiliateCommission::create([
                    'affiliate_id'   => $attribution['affiliate_id'],
                    'campaign_id'    => $attribution['campaign_id'],
                    'order_id'       => $orderId,
                    'subscription_id'=> $subscriptionId,
                    'amount'         => $amount,
                    'currency'       => $currency,
                    'commission_rate'=> $commissionRate,
                    'commission_amount' => $commissionAmount,
                    'product'        => $product ?? $attribution['product'],
                    'source'         => $source ?? $attribution['source'],
                    'gateway'        => $gateway,
                    'is_initial'     => $isInitial,
                    'is_recurring'   => $isRecurringFlag,
                    'status'         => 'approved',
                    'meta'           => [
                        'attribution_type' => $attribution['type'],
                        'install_token'    => $attribution['install_token'] ?? null,
                        'session_token'    => $attribution['session_token'] ?? null,
                    ],
                ]);
            });

            return response()->json([
                'status'            => 'success',
                'order_id'          => $orderId,
                'affiliate_id'      => $attribution['affiliate_id'],
                'campaign_id'       => $attribution['campaign_id'],
                'commission_id'     => $commission->id,
                'commission_amount' => $commissionAmount,
                'commission_rate'   => $commissionRate,
                'is_initial'        => $isInitial,
                'is_recurring'      => $isRecurringFlag,
            ], 201);

        } catch (\Throwable $e) {
            Log::error('AffiliateEventController: error in handleOrderPaid', [
                'error'  => $e->getMessage(),
                'order'  => $orderId,
                'trace'  => $e->getTraceAsString(),
            ]);

            return response()->json([
                'status'  => 'error',
                'message' => 'Failed to create affiliate commission.',
            ], 500);
        }
    }

    /**
     * Resolve affiliate attribution from install_token (Option B).
     */
    protected function resolveFromInstallToken(
        string $installToken,
        ?int $userId,
        ?int $subscriptionId,
        ?string $platform
    ): ?array {
        $record = AffiliateInstallToken::where('install_token', $installToken)
            ->where(function ($q) {
                $q->whereNull('expires_at')
                    ->orWhere('expires_at', '>', now());
            })
            ->first();

        if (! $record) {
            return null;
        }

        // Update record with user/subscription information if provided
        $dirty = false;

        if ($userId && $record->user_id !== $userId) {
            $record->user_id = $userId;
            $dirty = true;
        }

        if ($subscriptionId && $record->subscription_id !== $subscriptionId) {
            $record->subscription_id = $subscriptionId;
            $dirty = true;
        }

        if ($platform && $record->platform !== $platform) {
            $record->platform = $platform;
            $dirty = true;
        }

        if (! $record->claimed_at) {
            $record->claimed_at = now();
            $dirty = true;
        }

        if ($dirty) {
            $record->save();
        }

        return [
            'type'          => 'install_token',
            'affiliate_id'  => $record->affiliate_id,
            'campaign_id'   => $record->campaign_id,
            'source'        => $record->source,
            'product'       => $record->product,
            'install_token' => $record->install_token,
            'session_token' => null,
        ];
    }

    /**
     * Resolve affiliate attribution from a web session token (cookie flow).
     */
    protected function resolveFromSessionToken(string $sessionToken): ?array
    {
        $session = AffiliateSession::where('session_token', $sessionToken)
            ->where('expires_at', '>', now())
            ->first();

        if (! $session) {
            return null;
        }

        return [
            'type'          => 'session_token',
            'affiliate_id'  => $session->affiliate_id,
            'campaign_id'   => $session->campaign_id,
            'source'        => $session->source,
            'product'       => null,
            'install_token' => null,
            'session_token' => $sessionToken,
        ];
    }
}
