<?php

namespace App\Console\Commands;

use App\Models\Affiliate;
use App\Models\AffiliateEmailDelivery;
use App\Services\AffiliateNotificationClient;
use Illuminate\Console\Command;
use Throwable;

class EmailAffiliatePayoutDetails extends Command
{
    private const CAMPAIGN = 'payout-details-2026-10';

    private const EXCLUDED_CODES = ['KX9WTTWW', 'QFJCHZ01'];

    private const EXCLUDED_EMAILS = ['unggultunjungs@gmail.com', 'support@esimdb.com'];

    protected $signature = 'affiliate:email-payout-details
        {--preview : Show counts without sending email}
        {--send : Send the campaign to eligible affiliates}
        {--test= : Send one test email instead of the campaign}
        {--retry-failed : Retry deliveries that previously failed}';

    protected $description = 'Email active affiliates who still need to add payout bank details.';

    public function handle(AffiliateNotificationClient $notifications): int
    {
        if ($this->option('preview') === $this->option('send')) {
            $this->error('Choose exactly one of --preview or --send.');

            return self::INVALID;
        }

        if ($testAddress = $this->option('test')) {
            if (! $this->option('send') || mb_strtolower((string) $testAddress) !== 'blerim@cazimi.dk') {
                $this->error('The test address is not approved for this campaign.');

                return self::INVALID;
            }

            $notifications->sendPayoutDetailsReminder(
                (string) $testAddress,
                'Blerim',
                self::CAMPAIGN.'-test-blerim',
                'test-blerim',
                true,
            );
            $this->info('Test email sent to blerim@cazimi.dk. No affiliate campaign emails were sent.');

            return self::SUCCESS;
        }

        $recipients = Affiliate::query()
            ->where('status', 'active')
            ->whereNotNull('email')
            ->where('email', '<>', '')
            ->whereNotIn('public_code', self::EXCLUDED_CODES)
            ->whereRaw('LOWER(email) NOT IN (?, ?)', self::EXCLUDED_EMAILS)
            ->whereDoesntHave('payoutMethods', fn ($query) => $query
                ->where('type', 'revolut_bank')
                ->where('is_default', true)
                ->whereNotNull('encrypted_details'))
            ->orderBy('id');

        $excluded = Affiliate::query()
            ->where(fn ($query) => $query
                ->whereIn('public_code', self::EXCLUDED_CODES)
                ->orWhereRaw('LOWER(email) IN (?, ?)', self::EXCLUDED_EMAILS))
            ->count();

        $this->table(['Campaign', 'Eligible recipients', 'Explicit exclusions'], [[
            self::CAMPAIGN,
            (clone $recipients)->count(),
            $excluded,
        ]]);

        if ($this->option('preview')) {
            $this->line('Notification API: '.(config('stellar-notifications.base_url') ? 'configured' : 'not configured'));

            return self::SUCCESS;
        }

        $sent = 0;
        $skipped = 0;
        $failed = 0;

        $recipients->lazyById()->each(function (Affiliate $affiliate) use ($notifications, &$sent, &$skipped, &$failed) {
            $delivery = AffiliateEmailDelivery::firstOrCreate(
                ['affiliate_id' => $affiliate->id, 'campaign' => self::CAMPAIGN],
                ['status' => 'pending'],
            );

            if (in_array($delivery->status, ['sending', 'sent'], true)
                || ($delivery->status === 'failed' && ! $this->option('retry-failed'))) {
                $skipped++;

                return;
            }

            $delivery->update([
                'status' => 'sending',
                'attempted_at' => now(),
                'error_code' => null,
            ]);

            try {
                $notifications->sendPayoutDetailsReminder(
                    (string) $affiliate->email,
                    $affiliate->name ?: 'Affiliate partner',
                    self::CAMPAIGN.'-affiliate-'.$affiliate->id,
                    (string) $affiliate->id,
                );
                $delivery->update(['status' => 'sent', 'sent_at' => now()]);
                $sent++;
            } catch (Throwable $exception) {
                report($exception);
                $delivery->update([
                    'status' => 'failed',
                    'error_code' => substr(class_basename($exception), 0, 100),
                ]);
                $failed++;
            }
        });

        $this->table(['Sent', 'Skipped', 'Failed'], [[$sent, $skipped, $failed]]);

        return $failed === 0 ? self::SUCCESS : self::FAILURE;
    }
}
