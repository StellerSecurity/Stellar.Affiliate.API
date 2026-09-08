<section class="stellar-card stellar-card-pad stellar-section">
    <h2 class="stellar-section-title">Bank account for payouts</h2>
    <p class="stellar-field-help">Payouts are in EUR. Enter the account holder's legal name and address exactly as registered with the bank. Your bank details are encrypted and shared with Revolut to process your payouts.</p>
    @if($bankMethod ?? null)
        <p>Saved IBAN: •••• {{ $bankMethod->data['iban_last_four'] ?? '••••' }} · {{ $bankMethod->verified_at ? 'Registered with Revolut' : 'Awaiting bank registration' }}</p>
        @if($bankMethod->registration_error)
            <p role="status">Bank registration needs attention. Please check your details or contact support.</p>
        @endif
    @endif
    @if(!request()->attributes->get('affiliate_impersonation'))
    <details>
        <summary class="stellar-btn stellar-btn-secondary">{{ ($bankMethod ?? null) ? 'Replace bank details' : 'Add bank details' }}</summary>
        <form method="POST" action="{{ route('affiliate.payouts.bank-details') }}" class="stellar-section" autocomplete="off">
            @csrf
            @method('PUT')
            <label class="stellar-label" for="bank-profile-type">Account holder type</label>
            <select id="bank-profile-type" name="profile_type" class="stellar-select" required>
                <option value="personal">Individual</option>
                <option value="business">Business</option>
            </select>
            <p class="stellar-field-help">Individuals: enter first and last name. Businesses: enter the registered company name.</p>
            @foreach([
                'first_name' => ['First name', false, 100],
                'last_name' => ['Last name', false, 100],
                'company_name' => ['Legal company name', false, 150],
                'street_line1' => ['Account holder address', true, 150],
                'street_line2' => ['Address line 2', false, 150],
                'city' => ['City', true, 100],
                'region' => ['State / region', false, 100],
                'postcode' => ['Postal code', true, 20],
                'country' => ['Residence / company country (2-letter code, e.g. DK)', true, 2],
                'bank_country' => ['Bank country (2-letter code, e.g. DK)', true, 2],
                'iban' => ['IBAN', true, 42],
                'bic' => ['SWIFT / BIC', true, 11],
            ] as $field => [$label, $required, $maximum])
                <div style="margin-top:12px">
                    <label for="bank-{{ $field }}" class="stellar-label">{{ $label }}</label>
                    <input id="bank-{{ $field }}" name="{{ $field }}" type="text" class="stellar-input" maxlength="{{ $maximum }}" @required($required)>
                </div>
            @endforeach
            <p class="stellar-field-help">This form supports IBAN accounts. For banks requiring local routing numbers or extra country-specific details, contact support.</p>
            <label class="stellar-label" for="bank-password">Confirm your current portal password</label>
            <input id="bank-password" name="current_password" type="password" class="stellar-input" autocomplete="current-password" required>
            <p><label><input type="checkbox" name="confirm_ownership" value="1" required> I confirm that I own this account or am authorised to receive payments for this business.</label></p>
            <p class="stellar-field-help">Replacing an account triggers new bank checks and a seven-day payment hold. Please enter all details again.</p>
            <button type="submit" class="stellar-btn stellar-btn-primary">Save bank details</button>
        </form>
    </details>
    @endif
</section>
