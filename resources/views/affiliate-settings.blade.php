@extends('layouts.affiliate')

@section('title', 'Settings · Stellar Affiliate')

@section('content')
    <section class="stellar-page-header">
        <div>
            <p class="stellar-eyebrow">Workspace</p>
            <h1 class="stellar-page-title">Settings</h1>
            <p class="stellar-page-copy">Your affiliate details, commission rates and resources.</p>
        </div>
    </section>

    @if($needsAffiliateSetup ?? false)
        <section class="stellar-card stellar-empty">
            <div>
                <h3>Finish your affiliate setup</h3>
                <p>Complete setup to see your affiliate code, rates and resources.</p>
                <div class="stellar-actions" style="justify-content:center">
                    <a href="{{ route('affiliate.onboarding') }}" class="stellar-btn stellar-btn-primary">Continue setup</a>
                </div>
            </div>
        </section>
    @else
        <section class="stellar-grid-2">
            <article class="stellar-card stellar-card-pad">
                <p class="stellar-eyebrow">Affiliate profile</p>
                <h2 class="stellar-section-title">Your account</h2>
                <div class="stellar-stat-list" style="margin-top:18px">
                    <div class="stellar-stat-row"><span>Name</span><strong>{{ $currentAffiliate?->name ?: '—' }}</strong></div>
                    <div class="stellar-stat-row"><span>Email</span><strong>{{ $currentAffiliate?->email ?: '—' }}</strong></div>
                    <div class="stellar-stat-row"><span>Affiliate code</span><strong>{{ $currentAffiliate?->public_code ?: '—' }}</strong></div>
                    <div class="stellar-stat-row"><span>Status</span><strong>{{ $currentAffiliate?->status === 'banned' ? 'Disabled' : ucfirst($currentAffiliate?->status ?: 'unknown') }}</strong></div>
                </div>
                <p class="stellar-field-help" style="margin-top:16px;">Change the destination for any tracking link from Campaigns.</p>
                <div class="stellar-actions">
                    <a href="{{ route('affiliate.campaigns.index') }}" class="stellar-btn stellar-btn-primary stellar-btn-small">Manage campaigns</a>
                    @if($currentAffiliate?->public_code)
                        <button type="button" class="stellar-btn stellar-btn-secondary stellar-btn-small" data-copy="{{ $currentAffiliate->public_code }}">Copy affiliate code</button>
                    @endif
                </div>
            </article>

            <article class="stellar-card stellar-card-pad stellar-resource-card">
                <p class="stellar-eyebrow">eSIM resource</p>
                <h2 class="stellar-section-title">Product feed</h2>
                <p class="stellar-section-copy">Use the live eSIM feed on your site, comparison page or storefront.</p>
                <div class="stellar-resource-url" style="margin-top:18px">{{ $esimFeedUrl }}</div>
                <div class="stellar-actions">
                    <a class="stellar-btn stellar-btn-primary" href="{{ $esimFeedUrl }}" target="_blank" rel="noopener noreferrer">Open feed</a>
                    <button type="button" class="stellar-btn stellar-btn-secondary" data-copy="{{ $esimFeedUrl }}">Copy URL</button>
                </div>
            </article>
        </section>

        @php
            $effectiveRates = collect($rateMatrix);
            $vpnInitial = (float) ($effectiveRates->first(fn ($row) => $row['product'] === 'vpn' && $row['type'] === 'initial')['rate'] ?? 1.00);
            $vpnRecurring = (float) ($effectiveRates->first(fn ($row) => $row['product'] === 'vpn' && $row['type'] === 'recurring')['rate'] ?? 0.60);
            $antivirusInitial = (float) ($effectiveRates->first(fn ($row) => $row['product'] === 'antivirus' && $row['type'] === 'initial')['rate'] ?? 1.00);
            $antivirusRecurring = (float) ($effectiveRates->first(fn ($row) => $row['product'] === 'antivirus' && $row['type'] === 'recurring')['rate'] ?? 0.60);
            $esimRate = (float) ($effectiveRates->first(fn ($row) => $row['product'] === 'esim')['rate'] ?? 0.10);
        @endphp

        <section class="stellar-card stellar-card-pad stellar-section">
            <div class="stellar-section-head">
                <div>
                    <p class="stellar-eyebrow">Commission</p>
                    <h2 class="stellar-section-title">Your rates</h2>
                    <p class="stellar-section-copy">These are the rates applied to new conversions.</p>
                </div>
            </div>
            <div class="stellar-rate-grid">
                <article class="stellar-rate-card">
                    <div><strong>Stellar VPN</strong><span>Program rate</span></div>
                    <div class="stellar-stat-list" style="margin-top:12px">
                        <div class="stellar-stat-row"><span>First payment</span><strong>{{ rtrim(rtrim(number_format($vpnInitial * 100, 2), '0'), '.') }}%</strong></div>
                        <div class="stellar-stat-row"><span>Recurring</span><strong>{{ rtrim(rtrim(number_format($vpnRecurring * 100, 2), '0'), '.') }}%</strong></div>
                    </div>
                </article>
                <article class="stellar-rate-card">
                    <div><strong>Stellar Antivirus</strong><span>Program rate</span></div>
                    <div class="stellar-stat-list" style="margin-top:12px">
                        <div class="stellar-stat-row"><span>First payment</span><strong>{{ rtrim(rtrim(number_format($antivirusInitial * 100, 2), '0'), '.') }}%</strong></div>
                        <div class="stellar-stat-row"><span>Recurring</span><strong>{{ rtrim(rtrim(number_format($antivirusRecurring * 100, 2), '0'), '.') }}%</strong></div>
                    </div>
                </article>
                <article class="stellar-rate-card">
                    <div><strong>Stellar eSIM</strong><span>Your rate</span></div>
                    <div class="stellar-rate-value">{{ rtrim(rtrim(number_format($esimRate * 100, 2), '0'), '.') }}%</div>
                    <div class="stellar-rate-source">Every eSIM sale</div>
                </article>
            </div>
        </section>

        <section class="stellar-card stellar-card-pad stellar-section">
            <div class="stellar-section-head">
                <div>
                    <p class="stellar-eyebrow">Data</p>
                    <h2 class="stellar-section-title">Export your reports</h2>
                    <p class="stellar-section-copy">Download clean CSV files for reconciliation, reporting or your own analytics.</p>
                </div>
            </div>
            <div class="stellar-export-grid">
                <article class="stellar-export-card">
                    <div><strong>Conversions</strong><span>Orders, values, rates, commission and payout status.</span></div>
                    <a href="{{ route('affiliate.sales.export') }}" class="stellar-btn stellar-btn-secondary stellar-btn-small" data-download>Download CSV</a>
                </article>
                <article class="stellar-export-card">
                    <div><strong>Campaigns</strong><span>Links, clicks, conversions, order value and commission.</span></div>
                    <a href="{{ route('affiliate.campaigns.export') }}" class="stellar-btn stellar-btn-secondary stellar-btn-small" data-download>Download CSV</a>
                </article>
                <article class="stellar-export-card">
                    <div><strong>Traffic</strong><span>Tracked clicks, campaign, source, landing URL and referrer.</span></div>
                    <a href="{{ route('affiliate.analytics.traffic.export', ['range' => 'all']) }}" class="stellar-btn stellar-btn-secondary stellar-btn-small" data-download>Download CSV</a>
                </article>
                <article class="stellar-export-card">
                    <div><strong>Payouts</strong><span>Payout amounts, methods, references and status history.</span></div>
                    <a href="{{ route('affiliate.payouts.export') }}" class="stellar-btn stellar-btn-secondary stellar-btn-small" data-download>Download CSV</a>
                </article>
            </div>
        </section>

        <section class="stellar-card stellar-card-pad stellar-section stellar-support-panel">
            <div>
                <p class="stellar-eyebrow">Support</p>
                <h2 class="stellar-section-title">Need help?</h2>
                <p class="stellar-section-copy">Questions about tracking links, commissions, orders or payouts? Contact Stellar Security.</p>
            </div>
            <div class="stellar-actions">
                <a href="mailto:{{ config('affiliate.support_email', 'info@stellarsecurity.com') }}" class="stellar-btn stellar-btn-primary">{{ config('affiliate.support_email', 'info@stellarsecurity.com') }}</a>
            </div>
        </section>
    @endif
@endsection
