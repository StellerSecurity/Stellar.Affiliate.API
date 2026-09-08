<?php

namespace App\Http\Controllers;

use App\Models\Affiliate;
use App\Models\AffiliatePayoutMethod;
use App\Models\Payout;
use App\Support\BankDetails;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rule;

class AffiliateBankDetailsController extends Controller
{
    public function update(Request $request)
    {
        $affiliate = $request->attributes->get('affiliate');
        abort_unless($affiliate instanceof Affiliate, 403);
        // Viewing a partner as an administrator must never grant bank-edit rights.
        abort_if($request->attributes->get('affiliate_impersonation'), 403);
        abort_unless((int) $affiliate->external_user_id === (int) $request->user()->id, 403);

        $input = $request->only(['profile_type', 'first_name', 'last_name', 'company_name', 'street_line1', 'street_line2', 'city', 'region', 'postcode', 'country', 'bank_country', 'iban', 'bic', 'current_password', 'confirm_ownership']);
        foreach (['iban', 'bic', 'country', 'bank_country'] as $key) {
            $input[$key] = BankDetails::normalize((string) ($input[$key] ?? ''));
        }
        $validator = Validator::make($input, [
            'profile_type' => ['required', Rule::in(['personal', 'business'])],
            'first_name' => ['nullable', 'required_if:profile_type,personal', 'string', 'max:100'],
            'last_name' => ['nullable', 'required_if:profile_type,personal', 'string', 'max:100'],
            'company_name' => ['nullable', 'required_if:profile_type,business', 'string', 'max:150'],
            'street_line1' => ['required', 'string', 'max:150'],
            'street_line2' => ['nullable', 'string', 'max:150'],
            'city' => ['required', 'string', 'max:100'],
            'region' => ['nullable', 'string', 'max:100'],
            'postcode' => ['required', 'string', 'max:20'],
            'country' => ['required', 'regex:/^[A-Z]{2}$/D'],
            'bank_country' => ['required', 'regex:/^[A-Z]{2}$/D'],
            'iban' => ['required', 'string', 'max:34', function ($attribute, $value, $fail) use ($input) {
                if (! BankDetails::validIban($value) || substr($value, 0, 2) !== $input['bank_country']) {
                    $fail('Enter a valid IBAN matching the bank country.');
                }
            }],
            'bic' => ['required', 'regex:/^[A-Z]{6}[A-Z0-9]{2}([A-Z0-9]{3})?$/D'],
            'current_password' => ['required', 'current_password:web'],
            'confirm_ownership' => ['accepted'],
        ]);
        if ($validator->fails()) {
            // Never flash bank details or passwords into the session.
            return back()->withErrors($validator);
        }
        $data = $validator->validated();
        unset($data['current_password'], $data['confirm_ownership']);

        DB::transaction(function () use ($affiliate, $data) {
            Affiliate::whereKey($affiliate->id)->lockForUpdate()->firstOrFail();
            // Already submitted transfers cannot be redirected to a different account.
            abort_if(Payout::where('affiliate_id', $affiliate->id)->whereNotNull('request_id')->where('status', 'processing')->exists(), 422, 'A transfer is processing. Wait for its result before changing bank details.');
            AffiliatePayoutMethod::where('affiliate_id', $affiliate->id)->update(['is_default' => false]);
            $method = new AffiliatePayoutMethod;
            $method->affiliate_id = $affiliate->id;
            $method->type = 'revolut_bank';
            $method->is_default = true;
            $method->encrypted_details = $data;
            $method->data = ['iban_last_four' => substr($data['iban'], -4)];
            $method->save();
            // A changed destination requires a fresh seven-day hold and bank registration.
            Payout::where('affiliate_id', $affiliate->id)->whereNotNull('request_id')
                ->where('status', 'pending')->update(['attention_reason' => 'bank_details_changed']);
        });

        return back()->with('status', 'Bank details saved securely. They will be checked with Revolut before payment.');
    }
}
