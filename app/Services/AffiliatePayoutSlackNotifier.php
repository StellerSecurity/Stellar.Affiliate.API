<?php

namespace App\Services;

use App\Models\Payout;
use Illuminate\Http\Client\PendingRequest;
use Illuminate\Support\Facades\Http;
use RuntimeException;

class AffiliatePayoutSlackNotifier
{
    public function sendReadyForApproval(Payout $payout): string
    {
        $token = (string) config('services.slack.notifications.bot_user_oauth_token');
        $channel = (string) config('payouts.slack.channel_id');
        if ($token === '' || $channel === '') {
            throw new RuntimeException('Affiliate payout Slack notifications are not configured.');
        }

        $adminUrl = (string) config('payouts.slack.admin_url');
        $response = $this->request($token)->post('https://slack.com/api/chat.postMessage', [
            'channel' => $channel,
            'client_msg_id' => $this->clientMessageId($payout),
            'text' => 'An affiliate payout is ready for finance approval.',
            'unfurl_links' => false,
            'unfurl_media' => false,
            'blocks' => [
                [
                    'type' => 'header',
                    'text' => ['type' => 'plain_text', 'text' => 'Affiliate payout ready for approval', 'emoji' => true],
                ],
                [
                    'type' => 'section',
                    'text' => ['type' => 'mrkdwn', 'text' => 'A new affiliate payout draft requires finance approval in the protected admin portal.'],
                ],
                [
                    'type' => 'actions',
                    'elements' => [[
                        'type' => 'button',
                        'text' => ['type' => 'plain_text', 'text' => 'Review payouts'],
                        'url' => $adminUrl,
                        'action_id' => 'review_affiliate_payouts',
                    ]],
                ],
            ],
        ]);

        $payload = $response->json();
        if (! $response->successful() || ! is_array($payload) || ($payload['ok'] ?? false) !== true) {
            throw new RuntimeException('Slack rejected the affiliate payout notification: '.($payload['error'] ?? 'http_'.$response->status()));
        }

        return (string) ($payload['ts'] ?? 'sent');
    }

    private function request(string $token): PendingRequest
    {
        return Http::acceptJson()
            ->asJson()
            ->withToken($token)
            ->connectTimeout(3)
            ->timeout((int) config('payouts.slack.timeout_seconds', 10))
            ->retry(2, 250, throw: false);
    }

    private function clientMessageId(Payout $payout): string
    {
        $hex = hash('sha256', 'stellar-affiliate-payout-ready-'.$payout->id);

        return substr($hex, 0, 8).'-'.substr($hex, 8, 4).'-4'.substr($hex, 13, 3)
            .'-a'.substr($hex, 17, 3).'-'.substr($hex, 20, 12);
    }
}
