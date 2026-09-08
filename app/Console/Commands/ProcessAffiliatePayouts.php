<?php

namespace App\Console\Commands;

use App\Models\Affiliate;
use App\Models\AffiliatePayoutMethod;
use App\Models\Payout;
use App\Services\AutomaticAffiliatePayouts;
use App\Services\RevolutBankRegistration;
use App\Support\PayoutPolicy;
use Illuminate\Console\Command;
use Throwable;

class ProcessAffiliatePayouts extends Command
{
    protected $signature = 'affiliate:process-payouts {--preview : Read-only readiness counts; no bank API calls or database writes}';
    protected $description = 'Register bank recipients, prepare 30-day affiliate payouts, and submit after a seven-day hold.';

    public function handle(AutomaticAffiliatePayouts $payouts, RevolutBankRegistration $banks): int
    {
        if ($this->option('preview')) {
            $this->table(['State', 'Count'], [
                ['Approved EUR commissions without payout', \App\Models\AffiliateCommission::where('status', 'approved')->where('currency', 'EUR')->whereNull('payout_id')->count()],
                ['Bank accounts awaiting registration', AffiliatePayoutMethod::where('type', 'revolut_bank')->where('is_default', true)->whereNull('verified_at')->count()],
                ['Payouts needing attention', Payout::whereNotNull('attention_reason')->count()],
            ]);
            $this->info('Readiness counts only; this does not simulate payout amounts or eligibility.');
            return self::SUCCESS;
        }
        if (! config('payouts.enabled')) {
            $this->info('Automatic payouts are disabled.');
            return self::SUCCESS;
        }
        $cutoff = PayoutPolicy::cutoff((string) config('payouts.starts_on'), now()->toDateTimeImmutable());
        $failed = false;
        foreach (Affiliate::where('status', 'active')->where('payout_currency', 'EUR')->lazyById() as $affiliate) {
            try {
                $banks->register($affiliate->id);
                if ($cutoff) {
                    $payouts->prepare($affiliate->id, $cutoff);
                }
            } catch (Throwable $exception) {
                $failed = true;
                AffiliatePayoutMethod::where('affiliate_id', $affiliate->id)->where('type', 'revolut_bank')
                    ->where('is_default', true)->whereNull('verified_at')->update(['registration_error' => 'bank_registration_requires_attention']);
                // Raw HTTP exceptions may contain customer banking data. Never log them.
                $this->error('Affiliate '.$affiliate->id.': bank registration or payout preparation needs attention.');
            }
        }
        foreach (Payout::whereNotNull('request_id')->where(function ($query) {
            $query->whereIn('status', ['pending', 'processing'])
                ->orWhere(fn ($query) => $query->where('status', 'paid')->where('checked_at', '<=', now()->subDay()));
        })
            ->where('scheduled_at', '<=', now())->lazyById() as $payout) {
            try {
                $payouts->process($payout->id);
            } catch (Throwable $exception) {
                $failed = true;
                Payout::whereKey($payout->id)->whereIn('status', ['pending', 'processing'])
                    ->update(['attention_reason' => 'provider_check_required']);
                $this->error('Payout '.$payout->id.': provider check required; no automatic resubmission.');
            }
        }
        if (Payout::whereNotNull('request_id')->whereNotNull('attention_reason')->exists()) {
            $failed = true;
            $this->error('Some automatic payouts require operator attention.');
        }

        return $failed ? self::FAILURE : self::SUCCESS;
    }
}
