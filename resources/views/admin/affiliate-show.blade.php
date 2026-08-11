@extends('layouts.affiliate')
@section('title', ($affiliate->name ?: $affiliate->public_code).' · Admin')
@section('content')
    <section class="stellar-page-header">
        <div><p class="stellar-eyebrow">Affiliate 360</p><h1 class="stellar-page-title">{{ $affiliate->name ?: $affiliate->public_code }}</h1><p class="stellar-page-copy">{{ $affiliate->public_code }} · {{ $affiliate->email ?: 'No email' }} · joined {{ $affiliate->created_at?->format('M j, Y') }}</p></div>
        <div class="stellar-actions">
            @if(auth()->user()?->affiliateAdminRole() === 'super_admin')
                <form method="POST" action="{{ route('affiliate.admin.affiliates.view-as', $affiliate) }}">
                    @csrf
                    <button class="stellar-btn stellar-btn-primary" type="submit">View as affiliate</button>
                </form>
            @endif
            <a class="stellar-btn stellar-btn-secondary" href="{{ route('affiliate.admin.affiliates.index') }}">Back to affiliates</a>
        </div>
    </section>

    <section class="stellar-grid-4 stellar-metrics">
        <article class="stellar-card stellar-metric"><div class="stellar-metric-label">Clicks</div><div class="stellar-metric-value">{{ number_format($affiliate->clicks_count) }}</div><div class="stellar-metric-detail">All-time tracked clicks.</div></article>
        <article class="stellar-card stellar-metric"><div class="stellar-metric-label">Conversions</div><div class="stellar-metric-value">{{ number_format($affiliate->conversions_count) }}</div><div class="stellar-metric-detail">Counted conversions.</div></article>
        <article class="stellar-card stellar-metric"><div class="stellar-metric-label">Commission total</div><div class="stellar-metric-value">€{{ \App\Support\CommissionMath::display($totals['commission']) }}</div><div class="stellar-metric-detail">Pending, approved and paid commissions.</div></article>
        <article class="stellar-card stellar-metric"><div class="stellar-metric-label">Paid out</div><div class="stellar-metric-value">€{{ \App\Support\CommissionMath::display($totals['paid']) }}</div><div class="stellar-metric-detail">Commissions marked paid out.</div></article>
    </section>

    <section class="stellar-card stellar-card-pad stellar-section">
        <div class="stellar-section-head">
            <div>
                <h2 class="stellar-section-title">Export reports</h2>
                <p class="stellar-section-copy">Download this affiliate's data directly from Admin 360.</p>
            </div>
        </div>
        <div class="stellar-export-grid">
            <article class="stellar-export-card">
                <div><strong>Conversions</strong><span>Orders, order value, rate, commission and payout status.</span></div>
                <a href="{{ route('affiliate.admin.affiliates.exports.conversions', $affiliate) }}" class="stellar-btn stellar-btn-secondary stellar-btn-small" data-export-range data-export-label="Conversions">Download CSV</a>
            </article>
            <article class="stellar-export-card">
                <div><strong>Campaigns</strong><span>Performance, order value, commission and tracking links.</span></div>
                <a href="{{ route('affiliate.admin.affiliates.exports.campaigns', $affiliate) }}" class="stellar-btn stellar-btn-secondary stellar-btn-small" data-export-range data-export-label="Campaign performance">Download CSV</a>
            </article>
            <article class="stellar-export-card">
                <div><strong>Tracking</strong><span>Clicks, campaign attribution, landing URLs and referrers.</span></div>
                <a href="{{ route('affiliate.admin.affiliates.exports.tracking', $affiliate) }}" class="stellar-btn stellar-btn-secondary stellar-btn-small" data-export-range data-export-label="Tracking activity">Download CSV</a>
            </article>
            <article class="stellar-export-card">
                <div><strong>Payouts</strong><span>Transfer history, status, references and paid dates.</span></div>
                <a href="{{ route('affiliate.admin.affiliates.exports.payouts', $affiliate) }}" class="stellar-btn stellar-btn-secondary stellar-btn-small" data-export-range data-export-label="Payouts">Download CSV</a>
            </article>
        </div>
    </section>

    <section class="stellar-grid-2 stellar-section">
        <article class="stellar-card stellar-card-pad">
            <div class="stellar-section-head"><div><h2 class="stellar-section-title">Affiliate profile</h2><p class="stellar-section-copy">Update affiliate details and status.</p></div></div>
            <form method="POST" action="{{ route('affiliate.admin.affiliates.update', $affiliate) }}" class="stellar-form-grid">@csrf @method('PATCH')
                <div class="stellar-form-row"><div class="stellar-field"><label class="stellar-label" for="affiliate-edit-name">Name</label><input id="affiliate-edit-name" class="stellar-input" name="name" value="{{ old('name', $affiliate->name) }}" required></div><div class="stellar-field"><label class="stellar-label" for="affiliate-edit-email">Email</label><input id="affiliate-edit-email" class="stellar-input" type="email" name="email" value="{{ old('email', $affiliate->email) }}"></div></div>
                <div class="stellar-form-row"><div class="stellar-field"><label class="stellar-label" for="affiliate-edit-status">Status</label><select id="affiliate-edit-status" class="stellar-select" name="status">@foreach(['active','pending','banned'] as $option)<option value="{{ $option }}" {{ old('status', $affiliate->status) === $option ? 'selected' : '' }}>{{ $option === 'banned' ? 'Disabled' : ucfirst($option) }}</option>@endforeach</select></div><div class="stellar-field"><label class="stellar-label" for="affiliate-edit-currency">Payout currency</label><input id="affiliate-edit-currency" class="stellar-input" name="payout_currency" maxlength="3" value="{{ old('payout_currency', $affiliate->payout_currency ?: 'EUR') }}" required></div></div>
                <div class="stellar-form-row"><div class="stellar-field"><label class="stellar-label" for="affiliate-edit-country">Country</label><input id="affiliate-edit-country" class="stellar-input" name="country" maxlength="2" value="{{ old('country', $affiliate->country) }}" placeholder="CH"></div><div class="stellar-field"><label class="stellar-label" for="affiliate-edit-fallback">Fallback destination</label><input id="affiliate-edit-fallback" class="stellar-input" type="url" name="base_redirect_url" value="{{ old('base_redirect_url', $affiliate->base_redirect_url) }}" placeholder="https://..."></div></div>
                @if(auth()->user()?->canManageAffiliateProgram())<div><button class="stellar-btn stellar-btn-primary" type="submit">Save affiliate</button></div>@else<div class="stellar-field-help">Read only.</div>@endif
            </form>
        </article>

        <article class="stellar-card stellar-card-pad">
            <h2 class="stellar-section-title">Financial state</h2><p class="stellar-section-copy">Commission status totals for this affiliate.</p>
            <div class="stellar-stat-list" style="margin-top: 16px;"><div class="stellar-stat-row"><span>Pending review</span><strong>€{{ \App\Support\CommissionMath::display($totals['pending']) }}</strong></div><div class="stellar-stat-row"><span>Approved</span><strong>€{{ \App\Support\CommissionMath::display($totals['approved']) }}</strong></div><div class="stellar-stat-row"><span>Paid out</span><strong>€{{ \App\Support\CommissionMath::display($totals['paid']) }}</strong></div><div class="stellar-stat-row"><span>Campaigns</span><strong>{{ number_format($affiliate->campaigns_count) }}</strong></div><div class="stellar-stat-row"><span>Sessions</span><strong>{{ number_format($affiliate->sessions_count) }}</strong></div></div>
            <div class="stellar-actions"><a class="stellar-btn stellar-btn-secondary" href="{{ route('affiliate.admin.commissions.index', ['affiliate' => $affiliate->public_code]) }}">Open commissions</a><a class="stellar-btn stellar-btn-secondary" href="{{ route('affiliate.admin.tracking.index', ['affiliate' => $affiliate->public_code]) }}">Open tracking</a></div>
        </article>
    </section>

    @php
        $affiliateRates = collect($rateMatrix);
        $vpnInitial = (float) ($affiliateRates->first(fn ($row) => $row['product'] === 'vpn' && $row['type'] === 'initial')['rate'] ?? 1.00);
        $vpnRecurring = (float) ($affiliateRates->first(fn ($row) => $row['product'] === 'vpn' && $row['type'] === 'recurring')['rate'] ?? 0.60);
        $antivirusInitial = (float) ($affiliateRates->first(fn ($row) => $row['product'] === 'antivirus' && $row['type'] === 'initial')['rate'] ?? 1.00);
        $antivirusRecurring = (float) ($affiliateRates->first(fn ($row) => $row['product'] === 'antivirus' && $row['type'] === 'recurring')['rate'] ?? 0.60);
        $esimRateRow = $affiliateRates->first(fn ($row) => $row['product'] === 'esim');
        $esimRate = (float) ($esimRateRow['rate'] ?? 0.10);
    @endphp

    <section class="stellar-card stellar-card-pad stellar-section">
        <div class="stellar-section-head">
            <div>
                <h2 class="stellar-section-title">Commission rates</h2>
                <p class="stellar-section-copy">VPN and Antivirus are shared program rates. Only eSIM is negotiated per affiliate.</p>
            </div>
            <a class="stellar-btn stellar-btn-secondary stellar-btn-small" href="{{ route('affiliate.admin.rates.index', ['q' => $affiliate->public_code]) }}">Open rate center</a>
        </div>
        <div class="stellar-rate-grid">
            <article class="stellar-rate-card">
                <div><strong>Stellar VPN</strong><span>Shared program rate</span></div>
                <div class="stellar-stat-list" style="margin-top:12px">
                    <div class="stellar-stat-row"><span>Initial</span><strong>{{ rtrim(rtrim(number_format($vpnInitial * 100, 2), '0'), '.') }}%</strong></div>
                    <div class="stellar-stat-row"><span>Recurring</span><strong>{{ rtrim(rtrim(number_format($vpnRecurring * 100, 2), '0'), '.') }}%</strong></div>
                </div>
            </article>
            <article class="stellar-rate-card">
                <div><strong>Stellar Antivirus</strong><span>Shared program rate</span></div>
                <div class="stellar-stat-list" style="margin-top:12px">
                    <div class="stellar-stat-row"><span>Initial</span><strong>{{ rtrim(rtrim(number_format($antivirusInitial * 100, 2), '0'), '.') }}%</strong></div>
                    <div class="stellar-stat-row"><span>Recurring</span><strong>{{ rtrim(rtrim(number_format($antivirusRecurring * 100, 2), '0'), '.') }}%</strong></div>
                </div>
            </article>
            <article class="stellar-rate-card">
                <div><strong>Stellar eSIM</strong><span>Affiliate-specific rate · initial + recurring</span></div>
                <div class="stellar-rate-value">{{ number_format($esimRate * 100, 2) }}%</div>
                <div class="stellar-rate-source">{{ ($esimRateRow['source'] ?? '') === 'affiliate_override' ? 'Custom rate' : 'Default rate' }}</div>
                @if(auth()->user()?->canManageAffiliateProgram())
                    <form method="POST" action="{{ route('affiliate.admin.affiliates.rates.update', $affiliate) }}" class="stellar-inline-form">
                        @csrf
                        @method('PUT')
                        <input type="hidden" name="product" value="esim">
                        <input class="stellar-input stellar-rate-input" type="number" min="0" max="100" step="0.01" name="rate_percent" value="{{ number_format($esimRate * 100, 2, '.', '') }}" aria-label="eSIM commission percent">
                        <button type="submit" class="stellar-btn stellar-btn-primary stellar-btn-small">Save eSIM rate</button>
                    </form>
                    <form method="POST" action="{{ route('affiliate.admin.affiliates.rates.delete', $affiliate) }}" class="stellar-inline-form">
                        @csrf
                        @method('DELETE')
                        <input type="hidden" name="product" value="esim">
                        <button class="stellar-text-button" type="submit">Reset to program default</button>
                    </form>
                @endif
            </article>
        </div>
    </section>

    <section class="stellar-card stellar-card-pad stellar-section">
        <div class="stellar-section-head">
            <div><h2 class="stellar-section-title">Recent commissions</h2><p class="stellar-section-copy">Review and update this affiliate's commissions.</p></div>
            <a class="stellar-btn stellar-btn-secondary stellar-btn-small" href="{{ route('affiliate.admin.commissions.index', ['affiliate' => $affiliate->public_code]) }}">Open full ledger</a>
        </div>
        <div class="stellar-table-wrap">
            <table class="stellar-table">
                <thead><tr><th>Date</th><th>Order</th><th>Product</th><th>Type</th><th>Rate</th><th>Commission</th><th>Status</th><th>Action</th></tr></thead>
                <tbody>
                @forelse($recentCommissions as $commission)
                    <tr>
                        <td>{{ $commission->created_at?->format('M j, Y · H:i') }}</td>
                        <td><a class="stellar-order-link" href="{{ route('affiliate.orders.show', $commission->id) }}"><span class="stellar-code">{{ $commission->getRawOriginal('order_id') }}</span><span>View</span></a></td>
                        <td>{{ app(\App\Services\AffiliateCommissionPolicy::class)->productLabel($commission->product) }}</td>
                        <td>{{ $commission->type === 'initial' ? 'First payment' : ucfirst($commission->type) }}</td>
                        <td>{{ rtrim(rtrim(number_format((float) $commission->rate * 100, 2), '0'), '.') }}%</td>
                        <td class="strong">{{ $commission->currency }} {{ \App\Support\CommissionMath::display($commission->amount) }}</td>
                        <td><span class="stellar-badge {{ in_array($commission->status, ['approved','paid_out'], true) ? 'is-success' : ($commission->status === 'rejected' ? 'is-danger' : 'is-warning') }}">{{ $commission->status === 'paid_out' ? 'Paid out' : ($commission->status === 'pending' ? 'Pending review' : ucfirst($commission->status)) }}</span></td>
                        <td>
                            @if(auth()->user()?->canManageAffiliateCommissions())
                                @if($commission->status === 'paid_out' && auth()->user()?->affiliateAdminRole() !== 'super_admin')
                                    <span class="stellar-cell-sub">Paid commission locked</span>
                                @else
                                    <form method="POST" action="{{ route('affiliate.admin.commissions.status', $commission) }}" class="stellar-inline-form stellar-status-form">
                                        @csrf
                                        @method('PATCH')
                                        <select class="stellar-select stellar-compact-select" name="status" aria-label="Commission status">
                                            @foreach(['pending','approved','paid_out','rejected'] as $option)
                                                <option value="{{ $option }}" {{ $commission->status === $option ? 'selected' : '' }}>{{ $option === 'paid_out' ? 'Paid out' : ($option === 'pending' ? 'Pending review' : ucfirst($option)) }}</option>
                                            @endforeach
                                        </select>
                                        <input class="stellar-input stellar-note-input" name="note" placeholder="Optional note" aria-label="Commission status note">
                                        <button type="submit" class="stellar-btn stellar-btn-secondary stellar-btn-small">Save</button>
                                    </form>
                                @endif
                            @else
                                <span class="stellar-cell-sub">Read only</span>
                            @endif
                        </td>
                    </tr>
                @empty
                    <tr><td colspan="8">No commissions yet.</td></tr>
                @endforelse
                </tbody>
            </table>
        </div>
    </section>

    <section class="stellar-card stellar-card-pad stellar-section">
        <div class="stellar-section-head"><div><h2 class="stellar-section-title">Recent tracking</h2><p class="stellar-section-copy">Latest clicks for this affiliate.</p></div><a class="stellar-btn stellar-btn-secondary stellar-btn-small" href="{{ route('affiliate.admin.tracking.index', ['affiliate' => $affiliate->public_code]) }}">Full tracking</a></div>
        <div class="stellar-table-wrap"><table class="stellar-table"><thead><tr><th>Date</th><th>Campaign</th><th>Source</th><th>Landing</th><th>Referrer</th></tr></thead><tbody>@forelse($recentClicks as $click)<tr><td>{{ $click->created_at?->format('M j, Y · H:i') }}</td><td>{{ $click->campaign?->name ?: '—' }}</td><td>{{ $click->source ?: '—' }}</td><td><span class="stellar-truncate-cell">{{ $click->landing_url ?: '—' }}</span></td><td><span class="stellar-truncate-cell">{{ $click->referrer ?: '—' }}</span></td></tr>@empty<tr><td colspan="5">No tracked clicks yet.</td></tr>@endforelse</tbody></table></div>
    </section>

    <section class="stellar-grid-2 stellar-section">
        <article class="stellar-card stellar-card-pad">
            <h2 class="stellar-section-title">Campaigns</h2>
            <div class="stellar-stat-list" style="margin-top: 14px;">
                @forelse($campaigns as $campaign)
                    @php
                        $campaignProduct = $campaign->product ?: 'esim';
                        $campaignProductLabel = config('affiliate.products.'.$campaignProduct.'.label', ucfirst($campaignProduct));
                        $campaignRateLabel = $campaignProduct === 'esim'
                            ? number_format((float) ($campaign->commission_rate ?? $esimRate) * 100, 2).'%'
                            : rtrim(rtrim(number_format(($campaignProduct === 'antivirus' ? $antivirusInitial : $vpnInitial) * 100, 2), '0'), '.').'% first / '.rtrim(rtrim(number_format(($campaignProduct === 'antivirus' ? $antivirusRecurring : $vpnRecurring) * 100, 2), '0'), '.').'% recurring';
                    @endphp
                    <div class="stellar-stat-row">
                        <span>{{ $campaign->name }} · {{ $campaign->source ?: 'other' }} · {{ $campaignProductLabel }} · {{ $campaignRateLabel }}</span>
                        <strong>{{ number_format($campaign->conversions_count) }} conv. · €{{ \App\Support\CommissionMath::display($campaign->commission_total ?? 0) }}</strong>
                    </div>
                @empty
                    <div class="stellar-section-copy">No campaigns yet.</div>
                @endforelse
            </div>
        </article>
        <article class="stellar-card stellar-card-pad"><h2 class="stellar-section-title">Recent payouts</h2><div class="stellar-stat-list" style="margin-top: 14px;">@forelse($payouts as $payout)<div class="stellar-stat-row"><span>{{ ucfirst($payout->status) }} · {{ $payout->created_at?->format('M j, Y') }}</span><strong>{{ $payout->currency }} {{ \App\Support\CommissionMath::display($payout->amount) }}</strong></div>@empty<div class="stellar-section-copy">No payouts yet.</div>@endforelse</div></article>
    </section>
@endsection
