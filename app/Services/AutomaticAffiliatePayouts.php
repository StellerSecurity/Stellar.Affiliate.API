<?php

namespace App\Services;

use App\Models\Affiliate;
use App\Models\AffiliateCommissionStatusLog;
use App\Models\Payout;
use App\Support\PayoutPolicy;
use DateTimeImmutable;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;
use RuntimeException;
use Throwable;

class AutomaticAffiliatePayouts
{
    public function __construct(
        private RevolutBusinessClient $client,
        private ?AffiliatePayoutSlackNotifier $slackNotifier = null,
    ) {}

    private function fingerprint(Collection $commissions): string
    {
        return hash('sha256', $commissions->sortBy('id')->map(static fn ($commission) =>
            $commission->id.':'.$commission->amount.':'.$commission->currency.':'.$commission->status
        )->implode('|'));
    }

    public function prepare(int $affiliateId, DateTimeImmutable $cutoff): ?Payout
    {
        return DB::transaction(function () use ($affiliateId, $cutoff) {
            $affiliate = Affiliate::whereKey($affiliateId)->lockForUpdate()->firstOrFail();
            if ($affiliate->status !== 'active' || $affiliate->payout_currency !== 'EUR') {
                return null;
            }
            // Include unresolved legacy payouts: never overlap the manual and automatic lanes.
            if ($affiliate->payouts()->where('status', '<>', 'paid')->exists()
                || $affiliate->payouts()->where('cycle_at', $cutoff)->exists()) {
                return null;
            }
            $method = $affiliate->payoutMethods()->where('type', 'revolut_bank')->where('is_default', true)
                ->whereNotNull('verified_at')->latest('id')->first();
            if (! $method || ($method->data['environment'] ?? '') !== $this->client->environment()) {
                return null;
            }
            $commissions = $affiliate->commissions()->where('status', 'approved')->whereNull('payout_id')
                ->where('currency', 'EUR')->whereNotNull('eligible_payout_at')->where('eligible_payout_at', '<=', $cutoff)
                ->where('created_at', '<=', $cutoff)
                ->where(function ($query) use ($cutoff) {
                    $query->where('approved_at', '<=', $cutoff)
                        ->orWhere(fn ($query) => $query->whereNull('approved_at')->where('updated_at', '<=', $cutoff));
                })->orderBy('id')->lockForUpdate()->get();
            $total = 0;
            foreach ($commissions as $commission) {
                $value = PayoutPolicy::micros($commission->amount);
                if ($value < 0) {
                    throw new RuntimeException('Negative commission requires review.');
                }
                $total += $value;
            }
            // Preserve sub-cent commissions. Earlier paid payouts leave an exact carry balance.
            $carry = 0;
            foreach ($affiliate->payouts()->whereNotNull('request_id')->where('status', 'paid')->get() as $prior) {
                $carry += PayoutPolicy::micros($prior->commission_total) - PayoutPolicy::micros($prior->amount);
            }
            if ($commissions->isEmpty() || $total + $carry < 100000000) {
                return null;
            }
            $payout = new Payout;
            $payout->affiliate_id = $affiliateId;
            $payout->amount = PayoutPolicy::transfer($total + $carry);
            $payout->commission_total = PayoutPolicy::decimal($total);
            $payout->currency = 'EUR';
            $payout->status = 'pending';
            $payout->method_type = 'revolut_bank';
            $payout->method_details_snapshot = $this->snapshot($method);
            $payout->request_id = (string) Str::uuid();
            $payout->provider_environment = $this->client->environment();
            $payout->cycle_at = $cutoff;
            // A delayed scheduler must still give a full seven-day hold from preparation.
            $payout->scheduled_at = now()->addDays(7);
            $payout->commission_fingerprint = $this->fingerprint($commissions);
            $payout->save();
            $affiliate->commissions()->whereIn('id', $commissions->modelKeys())->update(['payout_id' => $payout->id]);

            return $payout;
        });
    }

    private function snapshot($method): array
    {
        $data = $method->data;
        if (empty($data['counterparty_id']) || empty($data['account_id']) || ! config('payouts.revolut.source_account_id')) {
            throw new RuntimeException('Missing payment account mapping.');
        }

        return [
            'method_id' => $method->id,
            'source_account_id' => config('payouts.revolut.source_account_id'),
            'counterparty_id' => $data['counterparty_id'],
            'account_id' => $data['account_id'],
            'iban_last_four' => $data['iban_last_four'],
            'name_validation_id' => $data['name_validation_id'] ?? null,
            'submission_type' => 'payment_draft',
        ];
    }

    public function process(int $payoutId): void
    {
        $initial = Payout::findOrFail($payoutId);
        if ($initial->provider_environment !== $this->client->environment()) {
            throw new RuntimeException('Payout belongs to a different payment provider environment.');
        }
        $this->client->authenticate();
        if (in_array($initial->status, ['processing', 'paid'], true)) {
            if (($initial->method_details_snapshot['submission_type'] ?? null) === 'payment_draft') {
                $this->reconcileDraft($initial);
            } else {
                // Reconcile any transfer submitted by the previous direct-payment implementation.
                $transaction = $this->client->lookup($initial->request_id);
                if ($transaction) {
                    $this->record($initial->id, $transaction);
                } else {
                    Payout::whereKey($initial->id)->whereIn('status', ['processing', 'paid'])->update([
                        'checked_at' => now(), 'attention_reason' => 'submission_unconfirmed_no_resend',
                    ]);
                }
            }

            return;
        }
        $claimed = DB::transaction(function () use ($initial) {
            $affiliate = Affiliate::whereKey($initial->affiliate_id)->lockForUpdate()->firstOrFail();
            $payout = Payout::whereKey($initial->id)->lockForUpdate()->firstOrFail();
            if ($payout->status !== 'pending' || $payout->attempted_at || $payout->scheduled_at->isFuture()) {
                return null;
            }
            if ($affiliate->status !== 'active' || $affiliate->payout_currency !== 'EUR') {
                $payout->attention_reason = 'affiliate_not_active_eur';
                $payout->save();
                return null;
            }
            if ($affiliate->payouts()->where('id', '<>', $payout->id)->where('status', 'failed')->exists()) {
                $payout->attention_reason = 'earlier_payment_requires_reconciliation';
                $payout->save();
                return null;
            }
            $method = $affiliate->payoutMethods()->where('type', 'revolut_bank')->where('is_default', true)->latest('id')->first();
            if (! $method || ! $method->verified_at || ($method->data['environment'] ?? '') !== $this->client->environment()) {
                $payout->attention_reason = 'bank_registration_required';
                $payout->save();
                return null;
            }
            if ($method->id !== ($payout->method_details_snapshot['method_id'] ?? null)) {
                $payout->method_details_snapshot = $this->snapshot($method);
                $payout->scheduled_at = now()->addDays(7);
                $payout->attention_reason = null;
                $payout->save();
                return null;
            }
            $commissions = $payout->commissions()->orderBy('id')->lockForUpdate()->get();
            if (! hash_equals($payout->commission_fingerprint, $this->fingerprint($commissions))) {
                $payout->attention_reason = 'reserved_commissions_changed';
                $payout->save();
                return null;
            }
            $snapshot = $payout->method_details_snapshot;
            $source = $this->client->get('/accounts/'.rawurlencode($snapshot['source_account_id']));
            if (($source['currency'] ?? '') !== 'EUR' || ($source['state'] ?? '') !== 'active') {
                throw new RuntimeException('Source account must be active and denominated in EUR.');
            }
            $snapshot['submission_type'] = 'payment_draft';
            $payout->method_details_snapshot = $snapshot;
            $reference = $this->reference($payout);
            $payload = [
                'title' => $this->draftTitle($payout),
                'payments' => [[
                    'account_id' => $snapshot['source_account_id'],
                    'receiver' => ['counterparty_id' => $snapshot['counterparty_id'], 'account_id' => $snapshot['account_id']],
                    // Only convert to JSON number at the external API boundary.
                    'amount' => (float) $payout->amount,
                    'currency' => 'EUR',
                    'reference' => $reference,
                ]],
            ];
            // Commit the claim BEFORE creating the draft. No automatic second POST after uncertainty.
            $payout->status = 'processing';
            $payout->attempted_at = now();
            $payout->attention_reason = null;
            $payout->save();

            return ['id' => $payout->id, 'payload' => $payload];
        });
        if ($claimed) {
            $draft = $this->client->createPaymentDraft($claimed['payload']);
            $payout = $this->recordDraft($claimed['id'], $draft);
            if ($payout) {
                $this->notifyDraftIfNeeded($payout);
            }
        }
    }

    private function draftTitle(Payout $payout): string
    {
        return 'Stellar affiliate payout '.$payout->id;
    }

    private function reference(Payout $payout): string
    {
        return 'Stellar affiliate '.$payout->id;
    }

    private function recordDraft(int $id, array $draft): ?Payout
    {
        if (empty($draft['id']) || ! is_string($draft['id'])) {
            throw new RuntimeException('Incomplete payment draft response.');
        }
        return DB::transaction(function () use ($id, $draft) {
            $payout = Payout::whereKey($id)->lockForUpdate()->firstOrFail();
            if ($payout->status !== 'processing') {
                return null;
            }
            $snapshot = $payout->method_details_snapshot;
            if (! empty($snapshot['draft_id']) && $snapshot['draft_id'] !== $draft['id']) {
                throw new RuntimeException('Payment draft ID mismatch.');
            }
            $snapshot['submission_type'] = 'payment_draft';
            $snapshot['draft_id'] = $draft['id'];
            $payout->method_details_snapshot = $snapshot;
            $payout->external_reference = $draft['id'];
            $payout->provider_state = 'draft';
            $payout->checked_at = now();
            $payout->attention_reason = null;
            $payout->save();

            return $payout;
        });
    }

    private function notifyDraftIfNeeded(Payout $payout): void
    {
        $snapshot = $payout->method_details_snapshot;
        if (! empty($snapshot['slack_notified_at'])) {
            return;
        }

        try {
            $notifier = $this->slackNotifier ?: app(AffiliatePayoutSlackNotifier::class);
            $messageTimestamp = $notifier->sendReadyForApproval($payout);
            DB::transaction(function () use ($payout, $messageTimestamp) {
                $locked = Payout::whereKey($payout->id)->lockForUpdate()->firstOrFail();
                $snapshot = $locked->method_details_snapshot;
                if (! empty($snapshot['slack_notified_at'])) {
                    return;
                }
                $snapshot['slack_notified_at'] = now()->toIso8601String();
                $snapshot['slack_message_ts'] = $messageTimestamp;
                $locked->method_details_snapshot = $snapshot;
                $locked->save();
            });
        } catch (Throwable $exception) {
            Log::error('Affiliate payout Slack notification failed.', [
                'payout_id' => $payout->id,
                'error' => $exception->getMessage(),
            ]);
        }
    }

    private function reconcileDraft(Payout $payout): void
    {
        $snapshot = $payout->method_details_snapshot;
        $draftId = $snapshot['draft_id'] ?? null;
        if (! $draftId) {
            $matches = array_values(array_filter($this->client->paymentDrafts(), fn ($draft) =>
                ($draft['title'] ?? null) === $this->draftTitle($payout) && is_string($draft['id'] ?? null)
            ));
            if (count($matches) > 1) {
                Payout::whereKey($payout->id)->update(['checked_at' => now(), 'attention_reason' => 'duplicate_revolut_drafts']);
                return;
            }
            if (count($matches) === 1) {
                $recorded = $this->recordDraft($payout->id, $matches[0]);
                if ($recorded) {
                    $this->notifyDraftIfNeeded($recorded);
                }
                return;
            }
        } elseif ($this->client->paymentDraft($draftId)) {
            Payout::whereKey($payout->id)->where('status', 'processing')->update([
                'provider_state' => 'draft', 'checked_at' => now(), 'attention_reason' => null,
            ]);
            $this->notifyDraftIfNeeded($payout->fresh());
            return;
        }

        $transactions = array_values(array_filter($this->client->transactions([
            'from' => ($payout->attempted_at ?: $payout->created_at)->copy()->subDay()->toIso8601String(),
            'to' => now()->toIso8601String(),
            'account' => $snapshot['source_account_id'],
            'count' => 1000,
            'type' => 'transfer',
        ]), fn ($transaction) => $this->matchesTransaction($payout, $transaction)));
        if (count($transactions) > 1) {
            Payout::whereKey($payout->id)->update(['checked_at' => now(), 'attention_reason' => 'duplicate_revolut_transactions']);
            return;
        }
        if (count($transactions) === 1) {
            $this->record($payout->id, $transactions[0]);
            return;
        }

        Payout::whereKey($payout->id)->update([
            'provider_state' => $draftId ? 'draft_sent_or_deleted' : 'draft_submission_unconfirmed',
            'checked_at' => now(),
            'attention_reason' => 'draft_requires_reconciliation',
        ]);
    }

    private function matchesTransaction(Payout $payout, array $transaction): bool
    {
        if (($transaction['type'] ?? null) !== 'transfer'
            || ($transaction['reference'] ?? null) !== $this->reference($payout)) {
            return false;
        }
        $sourceAccountId = $payout->method_details_snapshot['source_account_id'] ?? null;
        foreach ($transaction['legs'] ?? [] as $leg) {
            if (($leg['account_id'] ?? null) === $sourceAccountId
                && ($leg['currency'] ?? null) === 'EUR'
                && isset($leg['amount'])
                && PayoutPolicy::micros((string) abs((float) $leg['amount'])) === PayoutPolicy::micros($payout->amount)) {
                return true;
            }
        }

        return false;
    }

    private function record(int $id, array $transaction): void
    {
        if (empty($transaction['id']) || ! is_string($transaction['state'] ?? null)) {
            throw new RuntimeException('Incomplete payment response.');
        }
        $transaction['state'] = strtolower($transaction['state']);
        $initial = Payout::findOrFail($id);
        DB::transaction(function () use ($initial, $transaction) {
            Affiliate::whereKey($initial->affiliate_id)->lockForUpdate()->firstOrFail();
            $payout = Payout::whereKey($initial->id)->lockForUpdate()->firstOrFail();
            if (! in_array($payout->status, ['processing', 'paid'], true)) {
                return;
            }
            $snapshot = $payout->method_details_snapshot;
            $isDraft = ($snapshot['submission_type'] ?? null) === 'payment_draft';
            if (! $isDraft && $payout->external_reference && $payout->external_reference !== $transaction['id']) {
                throw new RuntimeException('Payment transaction ID mismatch.');
            }
            $payout->external_reference = $transaction['id'];
            $payout->provider_state = $transaction['state'];
            $payout->checked_at = now();
            $payout->attention_reason = null;
            if ($transaction['state'] === 'completed' && $payout->status !== 'paid') {
                $payout->status = 'paid';
                $payout->paid_at = now();
                foreach ($payout->commissions()->lockForUpdate()->get() as $commission) {
                    AffiliateCommissionStatusLog::create([
                        'affiliate_commission_id' => $commission->id,
                        'from_status' => $commission->status,
                        'to_status' => 'paid_out',
                        'note' => 'Payment provider completed payout '.$payout->id,
                    ]);
                    $commission->status = 'paid_out';
                    $commission->paid_out_at = $payout->paid_at;
                    $commission->save();
                }
            } elseif (in_array($transaction['state'], ['failed', 'reverted', 'declined'], true)) {
                if ($payout->status === 'paid') {
                    foreach ($payout->commissions()->lockForUpdate()->get() as $commission) {
                        AffiliateCommissionStatusLog::create([
                            'affiliate_commission_id' => $commission->id,
                            'from_status' => $commission->status,
                            'to_status' => 'approved',
                            'note' => 'Payment provider returned payout '.$payout->id.'; funds remain reserved for reconciliation.',
                        ]);
                        $commission->status = 'approved';
                        $commission->paid_out_at = null;
                        $commission->save();
                    }
                }
                $payout->status = 'failed';
                $payout->paid_at = null;
                $payout->attention_reason = 'revolut_transfer_failed';
                // Keep commissions reserved until an operator reconciles this payment.
            }
            $payout->save();
        });
    }
}
