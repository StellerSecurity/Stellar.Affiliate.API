<?php

namespace Tests\Unit;

use App\Services\AffiliateNotificationClient;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

class AffiliateNotificationClientTest extends TestCase
{
    public function test_it_submits_an_idempotent_payout_details_event(): void
    {
        config()->set('stellar-notifications.base_url', 'https://notifications.example');
        config()->set('stellar-notifications.basic_username', 'service-user');
        config()->set('stellar-notifications.basic_password', 'service-password');
        config()->set('stellar-notifications.payout_details_url', 'https://stellarafi.com/affiliate/payouts');

        Http::fake([
            'https://notifications.example/api/v1/notification-events/ingest' => Http::response([
                'data' => ['deliveries_created' => 1, 'idempotent' => false],
            ]),
        ]);

        app(AffiliateNotificationClient::class)->sendPayoutDetailsReminder(
            'blerim@cazimi.dk',
            'Blerim',
            'payout-details-test-blerim',
            'test-blerim',
            true,
        );

        Http::assertSent(fn ($request) =>
            $request->url() === 'https://notifications.example/api/v1/notification-events/ingest'
            && $request['product'] === 'stellar-affiliate'
            && $request['event_name'] === 'affiliate_payout_details_required'
            && $request['email'] === 'blerim@cazimi.dk'
            && $request['idempotency_key'] === 'payout-details-test-blerim'
            && $request['payload']['subject_prefix'] === '[TEST] '
            && $request->hasHeader('Authorization')
        );
    }
}
