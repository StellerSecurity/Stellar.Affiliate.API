@extends('layouts.affiliate')

@section('title', 'Get Started · Stellar Affiliate')

@section('content')
    @php
        $profileDone = (bool) $currentAffiliate;
        $campaignDone = (bool) $campaign;
        $progress = $campaignDone ? 100 : ($profileDone ? 50 : 25);
        $percent = static fn (float $rate): string => rtrim(rtrim(number_format($rate * 100, 2), '0'), '.').'%';
    @endphp

    <section class="stellar-page-header">
        <div>
            <p class="stellar-eyebrow">Guided setup</p>
            <h1 class="stellar-page-title">Get your first link live.</h1>
            <p class="stellar-page-copy">Set up your affiliate account, create a campaign and get your tracking link.</p>
        </div>
        <span class="stellar-kicker">{{ $progress }}% ready</span>
    </section>

    <div class="stellar-progress" aria-label="Setup progress">
        <span style="width: {{ $progress }}%"></span>
    </div>

    <div class="stellar-stepper stellar-section">
        <div class="stellar-step is-complete">
            <span>Step 1</span>
            <strong>Account created</strong>
        </div>
        <div class="stellar-step {{ $profileDone ? 'is-complete' : 'is-current' }}">
            <span>Step 2</span>
            <strong>Affiliate profile</strong>
        </div>
        <div class="stellar-step {{ $campaignDone ? 'is-complete' : ($profileDone ? 'is-current' : '') }}">
            <span>Step 3</span>
            <strong>First campaign</strong>
        </div>
        <div class="stellar-step {{ $campaignDone ? 'is-current' : '' }}">
            <span>Step 4</span>
            <strong>Copy & share</strong>
        </div>
    </div>

    @if(!$currentAffiliate)
        <section class="stellar-grid-2 stellar-section">
            <div class="stellar-card stellar-card-pad">
                <p class="stellar-eyebrow">Step 2 · Affiliate profile</p>
                <h2 class="stellar-section-title">Tell us what to call your affiliate workspace.</h2>
                <p class="stellar-section-copy">Your affiliate code will be created for you.</p>

                <form method="POST" action="{{ route('affiliate.affiliates.store') }}" class="stellar-form-grid" style="margin-top: 20px;">
                    @csrf
                    <input type="hidden" name="is_active" value="1">

                    <div class="stellar-field">
                        <label for="affiliate-name" class="stellar-label">Affiliate name</label>
                        <input id="affiliate-name" name="name" class="stellar-input" value="{{ old('name', auth()->user()?->name) }}" required maxlength="255" autocomplete="organization" placeholder="Your name, channel or company">
                        @error('name')<span class="stellar-field-error">{{ $message }}</span>@enderror
                        <span class="stellar-field-help">You can use your personal name, brand, channel or company name.</span>
                    </div>


                    <button type="submit" class="stellar-btn stellar-btn-primary">Create affiliate profile</button>
                </form>
            </div>

            <aside class="stellar-card stellar-card-pad">
                <p class="stellar-eyebrow">What happens next</p>
                <div class="stellar-checklist">
                    <div class="stellar-check-item is-done">
                        <span class="stellar-check-dot">✓</span>
                        <div><strong>Account</strong><span>Your login is ready.</span></div>
                    </div>
                    <div class="stellar-check-item">
                        <span class="stellar-check-dot">2</span>
                        <div><strong>Affiliate identity</strong><span>We generate a unique public affiliate code for you.</span></div>
                    </div>
                    <div class="stellar-check-item">
                        <span class="stellar-check-dot">3</span>
                        <div><strong>Campaign</strong><span>Name where you are promoting Stellar so results stay understandable.</span></div>
                    </div>
                    <div class="stellar-check-item">
                        <span class="stellar-check-dot">4</span>
                        <div><strong>Tracking link</strong><span>Copy the finished link and start sharing.</span></div>
                    </div>
                </div>
                <p class="stellar-support-line">Need help? <a href="mailto:{{ config('affiliate.support_email', 'info@stellarsecurity.com') }}">Email support</a></p>
            </aside>
        </section>
    @elseif(!$campaign)
        <section class="stellar-grid-2 stellar-section">
            <div class="stellar-card stellar-card-pad">
                <p class="stellar-eyebrow">Step 3 · First campaign</p>
                <h2 class="stellar-section-title">Where will you share Stellar first?</h2>
                <p class="stellar-section-copy">Choose the product, name the campaign and select where the traffic will come from.</p>

                <form method="POST" action="{{ route('affiliate.campaigns.store') }}" class="stellar-form-grid" style="margin-top: 20px;" data-campaign-builder>
                    @csrf
                    <input type="hidden" name="return_to" value="onboarding">

                    @php
                        $selectedProduct = old('product', 'esim');
                        $esimInitialRate = (float) ($onboardingCommissionRates['esim']['initial'] ?? 0.10);
                        $esimRecurringRate = (float) ($onboardingCommissionRates['esim']['recurring'] ?? $esimInitialRate);
                        $vpnInitialRate = (float) ($onboardingCommissionRates['vpn']['initial'] ?? 1.00);
                        $vpnRecurringRate = (float) ($onboardingCommissionRates['vpn']['recurring'] ?? 0.60);
                        $antivirusInitialRate = (float) ($onboardingCommissionRates['antivirus']['initial'] ?? 1.00);
                        $antivirusRecurringRate = (float) ($onboardingCommissionRates['antivirus']['recurring'] ?? 0.60);
                        $destinationDefaults = [
                            'esim' => $productDefaultDestinations['esim'] ?? config('affiliate.products.esim.default_redirect_url'),
                            'vpn' => $productDefaultDestinations['vpn'] ?? config('affiliate.products.vpn.default_redirect_url'),
                            'antivirus' => $productDefaultDestinations['antivirus'] ?? config('affiliate.products.antivirus.default_redirect_url'),
                        ];
                        $selectedDestination = old('redirect_url', $destinationDefaults[$selectedProduct] ?? $destinationDefaults['esim']);
                    @endphp
                    <div class="stellar-field">
                        <label for="campaign-product" class="stellar-label">Product</label>
                        <select id="campaign-product" name="product" class="stellar-select" required>
                            <option value="esim" {{ $selectedProduct === 'esim' ? 'selected' : '' }}>Stellar eSIM · {{ $percent($esimInitialRate) }}</option>
                            <option value="vpn" {{ $selectedProduct === 'vpn' ? 'selected' : '' }}>Stellar VPN · {{ $percent($vpnInitialRate) }} first / {{ $percent($vpnRecurringRate) }} recurring</option>
                            <option value="antivirus" {{ $selectedProduct === 'antivirus' ? 'selected' : '' }}>Stellar Antivirus · {{ $percent($antivirusInitialRate) }} first / {{ $percent($antivirusRecurringRate) }} recurring</option>
                        </select>
                        @error('product')<span class="stellar-field-error">{{ $message }}</span>@enderror
                    </div>

                    <div
                        class="stellar-rate-preview"
                        data-commission-rate-preview
                        data-esim-initial="{{ $esimInitialRate }}"
                        data-esim-recurring="{{ $esimRecurringRate }}"
                        data-vpn-initial="{{ $vpnInitialRate }}"
                        data-vpn-recurring="{{ $vpnRecurringRate }}"
                        data-antivirus-initial="{{ $antivirusInitialRate }}"
                        data-antivirus-recurring="{{ $antivirusRecurringRate }}"
                    >
                        <div class="stellar-rate-preview-head">
                            <span>Commission</span>
                            <strong data-rate-product>Stellar eSIM</strong>
                        </div>
                        <div class="stellar-rate-preview-values">
                            <div>
                                <span data-rate-primary-label>Per sale</span>
                                <strong data-rate-primary-value>{{ rtrim(rtrim(number_format($esimInitialRate * 100, 2), '0'), '.') }}%</strong>
                            </div>
                            <div data-rate-secondary hidden>
                                <span>Recurring</span>
                                <strong data-rate-secondary-value>{{ rtrim(rtrim(number_format($esimRecurringRate * 100, 2), '0'), '.') }}%</strong>
                            </div>
                        </div>
                        <p data-rate-description>Your eSIM rate applies to every eSIM sale.</p>
                    </div>

                    <div class="stellar-field">
                        <label for="campaign-destination" class="stellar-label">Destination URL <span class="stellar-label-note">editable</span></label>
                        <input
                            id="campaign-destination"
                            type="url"
                            name="redirect_url"
                            class="stellar-input"
                            value="{{ $selectedDestination }}"
                            maxlength="2048"
                            required
                            data-campaign-destination
                            data-default-esim="{{ $destinationDefaults['esim'] }}"
                            data-default-vpn="{{ $destinationDefaults['vpn'] }}"
                            data-default-antivirus="{{ $destinationDefaults['antivirus'] }}"
                            placeholder="https://..."
                        >
                        @error('redirect_url')<span class="stellar-field-error">{{ $message }}</span>@enderror
                        <span class="stellar-field-help">Visitors land here after tracking. We prefill the recommended page for the selected product, and you can change it.</span>
                        <button type="button" class="stellar-text-button" data-use-product-destination>Use product default</button>
                    </div>

                    <div class="stellar-field">
                        <label for="campaign-name" class="stellar-label">Campaign name</label>
                        <input id="campaign-name" name="name" class="stellar-input" value="{{ old('name') }}" required maxlength="255" autocomplete="off" placeholder="e.g. youtube-review-august">
                        @error('name')<span class="stellar-field-error">{{ $message }}</span>@enderror
                        <span class="stellar-field-help">Keep it specific: channel + placement or month works well.</span>
                    </div>

                    <div class="stellar-field">
                        <label for="campaign-source" class="stellar-label">Traffic source</label>
                        <select id="campaign-source" name="source" class="stellar-select" required>
                            <option value="" disabled {{ old('source') ? '' : 'selected' }}>Choose where you will share</option>
                            @foreach(['youtube' => 'YouTube', 'instagram' => 'Instagram', 'tiktok' => 'TikTok', 'blog' => 'Blog / website', 'newsletter' => 'Newsletter', 'other' => 'Other'] as $value => $label)
                                <option value="{{ $value }}" {{ old('source') === $value ? 'selected' : '' }}>{{ $label }}</option>
                            @endforeach
                        </select>
                        @error('source')<span class="stellar-field-error">{{ $message }}</span>@enderror
                    </div>

                    <details class="stellar-detail-box">
                        <summary>Advanced tracking labels</summary>
                        <div class="stellar-form-grid">
                            <div class="stellar-field">
                                <label for="sub-id-1" class="stellar-label">Sub ID 1 <span class="stellar-label-note">optional</span></label>
                                <input id="sub-id-1" name="sub_id1" class="stellar-input" value="{{ old('sub_id1') }}" maxlength="255" placeholder="e.g. video-01">
                            </div>
                            <div class="stellar-field">
                                <label for="sub-id-2" class="stellar-label">Sub ID 2 <span class="stellar-label-note">optional</span></label>
                                <input id="sub-id-2" name="sub_id2" class="stellar-input" value="{{ old('sub_id2') }}" maxlength="255" placeholder="e.g. creator-name">
                            </div>
                        </div>
                    </details>

                    <button type="submit" class="stellar-btn stellar-btn-primary">Create campaign & tracking link</button>
                </form>
            </div>

            <aside class="stellar-card stellar-card-pad">
                <p class="stellar-eyebrow">Your profile is ready</p>
                <div class="stellar-stat-list">
                    <div class="stellar-stat-row"><span>Affiliate name</span><strong>{{ $currentAffiliate->name ?: 'Affiliate' }}</strong></div>
                    <div class="stellar-stat-row"><span>Affiliate code</span><strong>{{ $currentAffiliate->public_code }}</strong></div>
                    <div class="stellar-stat-row"><span>Tracking window</span><strong>180 days</strong></div>
                    <div class="stellar-stat-row"><span>Destination</span><strong data-campaign-destination-summary>{{ $selectedDestination }}</strong></div>
                </div>

                <div class="stellar-detail-box" style="margin-top: 16px;">
                    <p class="stellar-section-copy" style="margin: 0;">Your tracking link is generated for you.</p>
                </div>
            </aside>
        </section>
    @else
        <section class="stellar-hero stellar-section">
            <div class="stellar-hero-grid">
                <div>
                    <p class="stellar-eyebrow">Setup complete</p>
                    <h2 class="stellar-hero-title">
                        @if($currentAffiliate?->status === 'active')
                            Your first campaign is <span class="accent-text">ready to share.</span>
                        @else
                            Your first campaign is <span class="accent-text">ready.</span>
                        @endif
                    </h2>
                    <p class="stellar-hero-copy">
                        @if($currentAffiliate?->status === 'active')
                            Share this link. Clicks and conversions will be tracked to <strong>{{ $campaign->name }}</strong>.
                        @else
                            Your campaign is set up. Tracking activates when your affiliate account is active.
                        @endif
                    </p>

                    <div class="stellar-actions">
                        @if($currentAffiliate?->status === 'active')
                            <button type="button" class="stellar-btn stellar-btn-primary" data-copy="{{ $trackingUrl }}">Copy tracking link</button>
                        @endif
                        <a href="{{ route('affiliate.dashboard') }}" class="stellar-btn stellar-btn-secondary">Go to dashboard</a>
                    </div>
                </div>

                <div class="stellar-hero-panel">
                    <span class="stellar-hero-panel-label">Campaign</span>
                    <div class="stellar-hero-panel-value">{{ $campaign->name }}</div>
                    <div class="stellar-campaign-meta">
                        <span class="stellar-badge is-success">Live</span>
                        <span class="stellar-badge">{{ match($campaign->product ?: 'esim') { 'esim' => 'Stellar eSIM', 'vpn' => 'Stellar VPN', 'antivirus' => 'Stellar Antivirus', default => ucfirst((string) $campaign->product) } }}</span>
                        @if(($campaign->product ?: 'esim') === 'esim')
                            <span class="stellar-badge">{{ $percent((float) ($onboardingCommissionRates['esim']['initial'] ?? $campaign->commission_rate ?? 0.10)) }} commission</span>
                        @elseif(in_array($campaign->product, ['vpn', 'antivirus'], true))
                            @php
                                $completedProduct = (string) $campaign->product;
                                $completedInitialRate = (float) ($onboardingCommissionRates[$completedProduct]['initial'] ?? 1.00);
                                $completedRecurringRate = (float) ($onboardingCommissionRates[$completedProduct]['recurring'] ?? 0.60);
                            @endphp
                            <span class="stellar-badge">{{ $percent($completedInitialRate) }} first · {{ $percent($completedRecurringRate) }} recurring</span>
                        @endif
                        <span class="stellar-badge">{{ ucfirst($campaign->source ?: 'other') }}</span>
                        @php
                            $completedDestination = $campaign->redirect_url
                                ?: ($productDefaultDestinations[$campaign->product ?: 'esim'] ?? config('affiliate.products.esim.default_redirect_url'));
                        @endphp
                        <a href="{{ $completedDestination }}" target="_blank" rel="noopener noreferrer" class="stellar-badge">Open destination ↗</a>
                    </div>
                </div>
            </div>
        </section>

        <section class="stellar-grid-2 stellar-section">
            <div class="stellar-card stellar-card-pad">
                <div class="stellar-section-head">
                    <div>
                        <h2 class="stellar-section-title">Your tracking link</h2>
                        <p class="stellar-section-copy">Share the whole URL exactly as shown.</p>
                    </div>
                </div>
                @if($currentAffiliate?->status === 'active')
                    <div class="stellar-link-box">
                        <div class="stellar-link-value" title="{{ $trackingUrl }}">{{ $trackingUrl }}</div>
                        <button type="button" class="stellar-btn stellar-btn-primary stellar-btn-small" data-copy="{{ $trackingUrl }}">Copy link</button>
                    </div>
                    <p class="stellar-field-help" style="margin-top: 10px;">Copy the link and share it with your audience.</p>
                @else
                    <div class="stellar-flash is-warning" style="margin: 0;">Your tracking link will be available when the affiliate account is active.</div>
                @endif
            </div>

            <div class="stellar-card stellar-card-pad">
                <p class="stellar-eyebrow">Next</p>
                <div class="stellar-checklist">
                    <div class="stellar-check-item is-done"><span class="stellar-check-dot">✓</span><div><strong>Share the link</strong><span>Use it in your content, bio, newsletter or website.</span></div></div>
                    <div class="stellar-check-item"><span class="stellar-check-dot">2</span><div><strong>Watch conversions</strong><span>Conversions show the linked Order ID, commission type and payout status.</span></div></div>
                    <div class="stellar-check-item"><span class="stellar-check-dot">3</span><div><strong>Create more campaigns</strong><span>Use one campaign per channel or placement to keep analytics clear.</span></div></div>
                </div>
                <div class="stellar-actions">
                    <a href="{{ route('affiliate.campaigns.index') }}" class="stellar-btn stellar-btn-secondary stellar-btn-small">Create another campaign</a>
                    <a href="mailto:{{ config('affiliate.support_email', 'info@stellarsecurity.com') }}" class="stellar-btn stellar-btn-secondary stellar-btn-small">Contact support</a>
                </div>
            </div>
        </section>
    @endif
@endsection


