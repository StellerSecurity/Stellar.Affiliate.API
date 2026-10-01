<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;
use RuntimeException;

class AffiliateNotificationClient
{
    public function sendPayoutDetailsReminder(
        string $email,
        string $affiliateName,
        string $idempotencyKey,
        string $userReference,
        bool $test = false,
    ): void {
        $baseUrl = rtrim((string) config('stellar-notifications.base_url'), '/');
        $username = (string) config('stellar-notifications.basic_username');
        $password = (string) config('stellar-notifications.basic_password');
        $payoutUrl = (string) config('stellar-notifications.payout_details_url');

        if (parse_url($baseUrl, PHP_URL_SCHEME) !== 'https' || $username === '' || $password === '') {
            throw new RuntimeException('Stellar Notification API is not configured.');
        }

        if (! str_starts_with($payoutUrl, 'https://stellarafi.com/')) {
            throw new RuntimeException('Affiliate payout details URL is not trusted.');
        }

        $response = Http::withBasicAuth($username, $password)
            ->acceptJson()
            ->asJson()
            ->withoutRedirecting()
            ->connectTimeout(5)
            ->timeout(max(5, (int) config('stellar-notifications.timeout_seconds', 45)))
            ->post($baseUrl.'/api/v1/notification-events/ingest', [
                'product' => 'stellar-affiliate',
                'event_name' => 'affiliate_payout_details_required',
                'email' => $email,
                'user_ref' => $userReference,
                'from_email' => 'info@stellarsecurity.com',
                'from_name' => 'stellar.affiliate',
                'idempotency_key' => $idempotencyKey,
                'payload' => [
                    'affiliate_name' => $affiliateName,
                    'payout_url' => $payoutUrl,
                    'subject_prefix' => $test ? '[TEST] ' : '',
                ],
            ]);

        $deliveries = (int) $response->json('data.deliveries_created', 0)
            + (int) $response->json('data.deliveries_existing', 0);

        if (! $response->successful() || $deliveries < 1) {
            throw new RuntimeException('Affiliate payout details email was not accepted for delivery.');
        }
    }
}
