<?php

namespace App\Console\Commands;

use App\Services\AffiliatePayoutSlackNotifier;
use Illuminate\Console\Command;
use Throwable;

class TestAffiliatePayoutSlack extends Command
{
    protected $signature = 'affiliate:test-payout-slack {--send : Send one non-sensitive test message}';

    protected $description = 'Validate affiliate payout Slack alert configuration.';

    public function handle(AffiliatePayoutSlackNotifier $notifier): int
    {
        if (! $this->option('send')) {
            $this->info('Add --send to deliver the non-sensitive Slack configuration test.');

            return self::SUCCESS;
        }

        try {
            $notifier->sendTest();
            $this->info('Affiliate payout Slack alert test sent successfully.');

            return self::SUCCESS;
        } catch (Throwable) {
            $this->error('Affiliate payout Slack alert test failed. Check the token, channel and app membership.');

            return self::FAILURE;
        }
    }
}
