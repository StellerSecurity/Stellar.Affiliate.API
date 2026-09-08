<?php

namespace App\Services;

use App\Models\Affiliate;
use App\Models\AffiliateCommissionStatusLog;
use App\Models\Payout;
use App\Support\PayoutPolicy;
use DateTimeImmutable;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use RuntimeException;

class AutomaticAffiliatePayouts
{
    public function __construct(private RevolutBusinessClient $client) {}

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
            throw new RuntimeException('Missing Revolut account mapping.');
        }

        return [
            'method_id' => $method->id,
            'source_account_id' => config('payouts.revolut.source_account_id'),
            'counterparty_id' => $data['counterparty_id'],
            'account_id' => $data['account_id'],
            'iban_last_four' => $data['iban_last_four'],
            'name_validation_id' => $data['name_validation_id'] ?? null,
        ];
    }

    public function process(int $payoutId): void
    {
        $initial = Payout::findOrFail($payoutId);
        if ($initial->provider_environment !== $this->client->environment()) {
            throw new RuntimeException('Payout belongs to a different Revolut environment.');
        }
        $this->client->authenticate();
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
            $payload = [
                'request_id' => $payout->request_id,
                'account_id' => $snapshot['source_account_id'],
                'receiver' => ['counterparty_id' => $snapshot['counterparty_id'], 'account_id' => $snapshot['account_id']],
                // Only convert to JSON number at the external API boundary.
                'amount' => (float) $payout->amount,
                'currency' => 'EUR',
                'reference' => 'Stellar affiliate '.$payout->id,
            ];
            if (! empty($snapshot['name_validation_id'])) {
                $payload['name_validation_id'] = $snapshot['name_validation_id'];
            }
            if ($this->client->environment() === 'production') {
                $requirements = $this->client->post('/pay/fields', ['account_id' => $payload['account_id'], 'receiver' => $payload['receiver']]);
                if (! isset($requirements['fields']) || ! is_array($requirements['fields'])) {
                    throw new RuntimeException('Cannot determine transfer requirements.');
                }
                foreach ($requirements['fields'] as $field) {
                    if (($field['required'] ?? false) && ! array_key_exists($field['name'], $payload)) {
                        throw new RuntimeException('Additional transfer fields require configuration.');
                    }
                }
            }
            // Commit the claim BEFORE calling /pay. No automatic second POST, even after a crash.
            $payout->status = 'processing';
            $payout->attempted_at = now();
            $payout->attention_reason = null;
            $payout->save();

            return ['id' => $payout->id, 'payload' => $payload];
        });
        if ($claimed) {
            $transaction = $this->client->pay($claimed['payload']);
            $this->record($claimed['id'], $transaction);
        } elseif (in_array($initial->status, ['processing', 'paid'], true)) {
            $transaction = $this->client->lookup($initial->request_id);
            if ($transaction) {
                $this->record($initial->id, $transaction);
            } else {
                Payout::whereKey($initial->id)->whereIn('status', ['processing', 'paid'])->update([
                    'checked_at' => now(), 'attention_reason' => 'submission_unconfirmed_no_resend',
                ]);
            }
        }
    }

    private function record(int $id, array $transaction): void
    {
        if (empty($transaction['id']) || ! is_string($transaction['state'] ?? null)) {
            throw new RuntimeException('Incomplete Revolut payment response.');
        }
        $initial = Payout::findOrFail($id);
        DB::transaction(function () use ($initial, $transaction) {
            Affiliate::whereKey($initial->affiliate_id)->lockForUpdate()->firstOrFail();
            $payout = Payout::whereKey($initial->id)->lockForUpdate()->firstOrFail();
            if (! in_array($payout->status, ['processing', 'paid'], true)) {
                return;
            }
            if ($payout->external_reference && $payout->external_reference !== $transaction['id']) {
                throw new RuntimeException('Revolut transaction ID mismatch.');
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
                        'note' => 'Revolut completed payout '.$payout->id,
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
                            'note' => 'Revolut returned payout '.$payout->id.'; funds remain reserved for reconciliation.',
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
