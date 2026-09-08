<?php

namespace App\Services;

use App\Models\Affiliate;
use App\Models\AffiliatePayoutMethod;
use App\Support\BankDetails;
use Illuminate\Support\Facades\DB;
use RuntimeException;

class RevolutBankRegistration
{
    public function __construct(private RevolutBusinessClient $client) {}

    public function register(int $affiliateId): void
    {
        DB::transaction(function () use ($affiliateId) {
            $affiliate = Affiliate::whereKey($affiliateId)->lockForUpdate()->firstOrFail();
            if ($affiliate->status !== 'active') {
                return;
            }
            $method = AffiliatePayoutMethod::where('affiliate_id', $affiliateId)
                ->where('type', 'revolut_bank')->where('is_default', true)->latest('id')->lockForUpdate()->first();
            if (! $method || $method->verified_at) {
                return;
            }
            $details = $method->encrypted_details;
            if (! $details || ! BankDetails::validIban($details['iban'] ?? '')) {
                throw new RuntimeException('Missing valid bank details.');
            }
            $payload = [
                'profile_type' => $details['profile_type'],
                'bank_country' => $details['bank_country'],
                'currency' => 'EUR',
                'iban' => $details['iban'],
                'bic' => $details['bic'],
                'address' => array_filter(array_intersect_key($details, array_flip(['street_line1', 'street_line2', 'city', 'region', 'postcode', 'country']))),
            ];
            if ($details['profile_type'] === 'personal') {
                $payload['individual_name'] = ['first_name' => $details['first_name'], 'last_name' => $details['last_name']];
            } else {
                $payload['company_name'] = $details['company_name'];
            }
            if ($this->client->environment() === 'production') {
                $fields = $this->client->get('/counterparties/fields', [
                    'country' => $details['bank_country'], 'currency' => 'EUR', 'recipient_type' => $details['profile_type'],
                ]);
                if (! isset($fields['fields']) || ! is_array($fields['fields'])) {
                    throw new RuntimeException('Cannot determine bank field requirements.');
                }
                foreach ($fields['fields'] ?? [] as $field) {
                    $name = $field['name'] ?? '';
                    $value = data_get($payload, $name) ?? data_get($payload, 'individual_name.'.$name);
                    if (($field['required'] ?? false) && ($value === null || $value === '')) {
                        throw new RuntimeException('Additional bank fields required by Revolut.');
                    }
                }
            }
            $nameValidationId = null;
            if ($this->client->environment() === 'production') {
                $validationPayload = ['iban' => $details['iban']];
                if ($details['profile_type'] === 'personal') {
                    $validationPayload['individual_name'] = $payload['individual_name'];
                } else {
                    $validationPayload['company_name'] = $payload['company_name'];
                }
                $validation = $this->client->post('/account-name-validation', $validationPayload);
                if (($validation['result_code'] ?? null) !== 'matched' || empty($validation['name_validation_id'])) {
                    throw new RuntimeException('The bank account holder name could not be verified automatically.');
                }
                $nameValidationId = $validation['name_validation_id'];
            }
            // Counterparty creation cannot move money. A lost response may create a duplicate
            // counterparty on retry; only the returned, persisted account can receive a payout.
            $counterparty = $this->client->post('/counterparty', $payload);
            $accounts = array_values(array_filter($counterparty['accounts'] ?? [], static fn ($account) =>
                ($account['currency'] ?? '') === 'EUR'
                && BankDetails::normalize((string) ($account['iban'] ?? '')) === $details['iban']
            ));
            if (empty($counterparty['id']) || count($accounts) !== 1 || empty($accounts[0]['id'])) {
                throw new RuntimeException('Revolut did not return a matching EUR bank account.');
            }
            $method->data = [
                'iban_last_four' => substr($details['iban'], -4),
                'environment' => $this->client->environment(),
                'counterparty_id' => $counterparty['id'],
                'account_id' => $accounts[0]['id'],
                'name_validation_id' => $nameValidationId,
            ];
            // Production reaches this point only after Revolut returns an exact VoP match.
            // Sandbox cannot perform real name validation and is always synthetic.
            $method->verified_at = now();
            $method->registration_error = null;
            $method->save();
        });
    }
}
