@extends('layouts.affiliate')

@section('title', 'Admin overview · Stellar Affiliate')

@section('content')
    <section class="stellar-page-header">
        <div>
            <p class="stellar-eyebrow">Admin center</p>
            <h1 class="stellar-page-title">Affiliate program overview</h1>
            <p class="stellar-page-copy">Affiliates, commissions, tracking and payouts in one place.</p>
        </div>
        <span class="stellar-kicker">{{ ucwords(str_replace('_', ' ', $adminRole ?? 'admin')) }}</span>
    </section>

    <section class="stellar-grid-4 stellar-metrics">
        <article class="stellar-card stellar-metric"><div class="stellar-metric-label">Affiliates</div><div class="stellar-metric-value">{{ number_format($totalAffiliates) }}</div><div class="stellar-metric-detail">{{ number_format($activeAffiliates) }} active.</div></article>
        <article class="stellar-card stellar-metric"><div class="stellar-metric-label">Clicks · 30 days</div><div class="stellar-metric-value">{{ number_format($clicksLast30) }}</div><div class="stellar-metric-detail">Tracked visits across all affiliates.</div></article>
        <article class="stellar-card stellar-metric"><div class="stellar-metric-label">Conversions · 30 days</div><div class="stellar-metric-value">{{ number_format($conversionsLast30) }}</div><div class="stellar-metric-detail">{{ number_format($conversionRate, 2) }}% click-to-conversion rate.</div></article>
        <article class="stellar-card stellar-metric"><div class="stellar-metric-label">Paid commission</div><div class="stellar-metric-value">€{{ \App\Support\CommissionMath::display($paidCommission) }}</div><div class="stellar-metric-detail">Total commissions paid out.</div></article>
    </section>

    <section class="stellar-grid-3 stellar-section">
        <article class="stellar-card stellar-card-pad"><p class="stellar-eyebrow">Queue</p><h2 class="stellar-section-title">Pending commission</h2><div class="stellar-admin-big-number">€{{ \App\Support\CommissionMath::display($pendingCommission) }}</div><a class="stellar-text-link" href="{{ route('affiliate.admin.commissions.index', ['status' => 'pending']) }}">Review pending →</a></article>
        <article class="stellar-card stellar-card-pad"><p class="stellar-eyebrow">Ready</p><h2 class="stellar-section-title">Approved commission</h2><div class="stellar-admin-big-number">€{{ \App\Support\CommissionMath::display($approvedCommission) }}</div><a class="stellar-text-link" href="{{ route('affiliate.admin.commissions.index', ['status' => 'approved']) }}">Open approved →</a></article>
        <article class="stellar-card stellar-card-pad"><p class="stellar-eyebrow">Rules</p><h2 class="stellar-section-title">Commission rates</h2><p class="stellar-section-copy">VPN and Antivirus: {{ rtrim(rtrim(number_format($vpnInitialRate * 100, 2), '0'), '.') }}% first payment, {{ rtrim(rtrim(number_format($vpnRecurringRate * 100, 2), '0'), '.') }}% recurring. eSIM rates are set per affiliate.</p><a class="stellar-text-link" href="{{ route('affiliate.admin.rates.index') }}">Manage rates →</a></article>
    </section>

    <section class="stellar-grid-2 stellar-section">
        <article class="stellar-card stellar-card-pad">
            <div class="stellar-section-head"><div><h2 class="stellar-section-title">Top affiliates · 30 days</h2><p class="stellar-section-copy">Ranked by commission total.</p></div><a class="stellar-btn stellar-btn-secondary stellar-btn-small" href="{{ route('affiliate.admin.affiliates.index') }}">All affiliates</a></div>
            @if($topAffiliates->isEmpty())
                <div class="stellar-empty stellar-order-empty"><div><h3>No conversion data yet</h3><p>Affiliate performance will appear after the first conversion.</p></div></div>
            @else
                <div class="stellar-stat-list">
                    @foreach($topAffiliates as $row)
                        @if($row->affiliate)<a class="stellar-stat-row stellar-row-link" href="{{ route('affiliate.admin.affiliates.show', $row->affiliate) }}"><span>{{ $row->affiliate->name ?: $row->affiliate->public_code }} · {{ number_format($row->conversions_count) }} conversions</span><strong>€{{ \App\Support\CommissionMath::display($row->commission_total) }}</strong></a>@else<div class="stellar-stat-row"><span>Unknown affiliate · {{ number_format($row->conversions_count) }} conversions</span><strong>€{{ \App\Support\CommissionMath::display($row->commission_total) }}</strong></div>@endif
                    @endforeach
                </div>
            @endif
        </article>

        <article class="stellar-card stellar-card-pad">
            <div class="stellar-section-head"><div><h2 class="stellar-section-title">Product mix · 30 days</h2><p class="stellar-section-copy">Commission by product.</p></div></div>
            @if($productPerformance->isEmpty())
                <div class="stellar-empty stellar-order-empty"><div><h3>No products yet</h3><p>Product performance will appear after the first conversion.</p></div></div>
            @else
                <div class="stellar-stat-list">
                    @foreach($productPerformance as $row)
                        <div class="stellar-stat-row"><span>{{ $row->product ? config('affiliate.products.'.$row->product.'.label', ucfirst($row->product)) : 'Unassigned' }} · {{ number_format($row->conversions_count) }} conversions</span><strong>€{{ \App\Support\CommissionMath::display($row->commission_total) }}</strong></div>
                    @endforeach
                </div>
            @endif
        </article>
    </section>

    <section class="stellar-card stellar-card-pad stellar-section">
        <div class="stellar-section-head"><div><h2 class="stellar-section-title">Latest commissions</h2><p class="stellar-section-copy">Latest conversions and order details.</p></div><a class="stellar-btn stellar-btn-secondary stellar-btn-small" href="{{ route('affiliate.admin.commissions.index') }}">Open ledger</a></div>
        <div class="stellar-table-wrap">
            <table class="stellar-table">
                <thead><tr><th>Date</th><th>Affiliate</th><th>Product</th><th>Order</th><th>Rate</th><th>Commission</th><th>Status</th></tr></thead>
                <tbody>
                @forelse($recentCommissions as $commission)
                    <tr>
                        <td>{{ $commission->created_at?->format('M j, Y · H:i') }}</td>
                        <td>@if($commission->affiliate)<a class="stellar-text-link" href="{{ route('affiliate.admin.affiliates.show', $commission->affiliate) }}">{{ $commission->affiliate->public_code }}</a>@else<span>—</span>@endif</td>
                        <td>{{ $commission->product ? config('affiliate.products.'.$commission->product.'.label', ucfirst($commission->product)) : 'Unassigned' }}</td>
                        <td><a class="stellar-order-link" href="{{ route('affiliate.orders.show', $commission->id) }}"><span class="stellar-code">{{ $commission->getRawOriginal('order_id') }}</span><span>View</span></a></td>
                        <td>{{ rtrim(rtrim(number_format((float) $commission->rate * 100, 2), '0'), '.') }}%</td>
                        <td class="strong">{{ $commission->currency }} {{ \App\Support\CommissionMath::display($commission->amount) }}</td>
                        <td><span class="stellar-badge {{ $commission->status === 'paid_out' || $commission->status === 'approved' ? 'is-success' : ($commission->status === 'rejected' ? 'is-danger' : 'is-warning') }}">{{ $commission->status === 'paid_out' ? 'Paid out' : ucfirst($commission->status) }}</span></td>
                    </tr>
                @empty
                    <tr><td colspan="7">No commissions yet.</td></tr>
                @endforelse
                </tbody>
            </table>
        </div>
    </section>
@endsection
