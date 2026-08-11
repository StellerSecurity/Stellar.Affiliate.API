@extends('layouts.affiliate')

@section('title', 'Analytics · Stellar Affiliate')

@section('content')
    @php
        $analyticsIsAdmin = (bool) ($isAdmin ?? false);
        $maxDaily = max(1, collect($daily)->map(fn($row) => max((int) ($row['clicks'] ?? 0), (int) ($row['sales'] ?? 0)))->max() ?? 1);
    @endphp

    <section class="stellar-page-header">
        <div>
            <p class="stellar-eyebrow">Performance</p>
            <h1 class="stellar-page-title">Analytics</h1>
            <p class="stellar-page-copy">Track clicks, conversions and commission performance.</p>
        </div>
        @if(!($needsAffiliateSetup ?? false))
            <span class="stellar-kicker">Last 30 days</span>
        @endif
    </section>

    @if($needsAffiliateSetup ?? false)
        <section class="stellar-card stellar-empty">
            <div>
                <span class="stellar-empty-icon">⌁</span>
                <h3>Analytics starts after setup</h3>
                <p>Complete setup to start seeing your analytics.</p>
                <div class="stellar-actions" style="justify-content: center;">
                    <a href="{{ route('affiliate.onboarding') }}" class="stellar-btn stellar-btn-primary">Continue setup</a>
                </div>
            </div>
        </section>
    @else
        <section class="stellar-grid-4">
            <article class="stellar-card stellar-metric">
                <div class="stellar-metric-label">Clicks</div>
                <div class="stellar-metric-value">{{ number_format($clicksLast30) }}</div>
                <div class="stellar-metric-detail">Tracked visits in the last 30 days.</div>
            </article>
            <article class="stellar-card stellar-metric">
                <div class="stellar-metric-label">Conversions</div>
                <div class="stellar-metric-value">{{ number_format($salesLast30) }}</div>
                <div class="stellar-metric-detail">Pending, approved and paid conversions.</div>
            </article>
            <article class="stellar-card stellar-metric">
                <div class="stellar-metric-label">Conversion rate</div>
                <div class="stellar-metric-value">{{ number_format($conversionRate, 2) }}%</div>
                <div class="stellar-metric-detail">Conversions divided by tracked clicks.</div>
            </article>
            <article class="stellar-card stellar-metric">
                <div class="stellar-metric-label">Commission per click</div>
                <div class="stellar-metric-value">€{{ number_format($epc, 4) }}</div>
                <div class="stellar-metric-detail">Commission total divided by tracked clicks.</div>
            </article>
        </section>

        <section class="stellar-grid-2 stellar-section">
            <article class="stellar-card stellar-card-pad">
                <div class="stellar-section-head">
                    <div>
                        <h2 class="stellar-section-title">7-day activity</h2>
                        <p class="stellar-section-copy">Clicks and conversions by day.</p>
                    </div>
                </div>

                <div class="stellar-chart">
                    @foreach($daily as $row)
                        @php
                            $clicks = (int) ($row['clicks'] ?? 0);
                            $sales = (int) ($row['sales'] ?? 0);
                            $clickWidth = max(0.8, ($clicks / $maxDaily) * 100);
                            $saleWidth = max(0.8, ($sales / $maxDaily) * 100);
                        @endphp
                        <div class="stellar-chart-row">
                            <span class="stellar-chart-label">{{ \Illuminate\Support\Carbon::parse($row['day'])->format('D, M j') }}</span>
                            <div>
                                <div class="stellar-chart-track"><div class="stellar-chart-bar" style="width: {{ $clickWidth }}%"></div></div>
                                <div class="stellar-chart-sales" style="width: {{ $saleWidth }}%"></div>
                            </div>
                            <span class="stellar-chart-value">{{ $clicks }} / {{ $sales }}</span>
                        </div>
                    @endforeach
                </div>
            </article>

            <article class="stellar-card stellar-card-pad">
                <div class="stellar-section-head">
                    <div>
                        <h2 class="stellar-section-title">30-day snapshot</h2>
                        <p class="stellar-section-copy">Your last 30 days at a glance.</p>
                    </div>
                </div>
                <div class="stellar-stat-list">
                    <div class="stellar-stat-row"><span>Commission total</span><strong>€{{ number_format($revenueLast30, 2) }}</strong></div>
                    <div class="stellar-stat-row"><span>Tracked sessions</span><strong>{{ number_format($sessionsLast30) }}</strong></div>
                    <div class="stellar-stat-row"><span>Conversions</span><strong>{{ number_format($salesLast30) }}</strong></div>
                    <div class="stellar-stat-row"><span>Referral window</span><strong>180 days</strong></div>
                </div>

                @if(!$analyticsIsAdmin)
                    <div class="stellar-detail-box" style="margin-top: 16px;">
                        <p class="stellar-section-copy" style="margin: 0;">Create separate campaigns for different channels to compare performance.</p>
                    </div>
                @endif
            </article>
        </section>

        @if($analyticsIsAdmin && $topAffiliates->isNotEmpty())
            <section class="stellar-card stellar-card-pad stellar-section">
                <div class="stellar-section-head">
                    <div>
                        <h2 class="stellar-section-title">Top affiliates · 30 days</h2>
                        <p class="stellar-section-copy">Ranked by commission generated.</p>
                    </div>
                </div>
                <div class="stellar-table-wrap">
                    <table class="stellar-table">
                        <thead><tr><th>Affiliate</th><th>Conversions</th><th>Commission</th></tr></thead>
                        <tbody>
                        @foreach($topAffiliates as $row)
                            <tr>
                                <td class="strong">{{ $row->affiliate?->public_code ?: '—' }}</td>
                                <td>{{ number_format($row->sales_count) }}</td>
                                <td class="strong">€{{ \App\Support\CommissionMath::display($row->total_commission) }}</td>
                            </tr>
                        @endforeach
                        </tbody>
                    </table>
                </div>
            </section>
        @endif

        <section class="stellar-card stellar-card-pad stellar-section">
            <div class="stellar-section-head">
                <div>
                    <h2 class="stellar-section-title">Recent conversions</h2>
                    <p class="stellar-section-copy">Your latest orders, commission and payout status.</p>
                </div>
                <a href="{{ route('affiliate.sales') }}" class="stellar-btn stellar-btn-secondary stellar-btn-small">All conversions</a>
            </div>

            @if($recentConversions->isEmpty())
                <div class="stellar-empty">
                    <div>
                        <span class="stellar-empty-icon">↗</span>
                        <h3>No conversions yet</h3>
                        <p>Share a campaign link to start building conversion data.</p>
                        <div class="stellar-actions" style="justify-content: center;">
                            <a href="{{ route('affiliate.campaigns.index') }}" class="stellar-btn stellar-btn-primary stellar-btn-small">Open campaigns</a>
                        </div>
                    </div>
                </div>
            @else
                <div class="stellar-table-wrap">
                    <table class="stellar-table">
                        <thead>
                        <tr>
                            <th>Date</th>
                            @if($analyticsIsAdmin)<th>Affiliate</th>@endif
                            <th>Order ID</th>
                            <th>Product</th>
                            <th>Type</th>
                            <th>Commission</th>
                            <th>Status</th>
                        </tr>
                        </thead>
                        <tbody>
                        @foreach($recentConversions as $conversion)
                            @php
                                $orderId = $conversion->getRawOriginal('order_id') ?: '-';
                                $status = $conversion->status ?: 'unknown';
                                $statusClass = match($status) {
                                    'approved', 'paid_out' => 'is-success',
                                    'pending' => 'is-warning',
                                    'rejected' => 'is-danger',
                                    default => '',
                                };
                                $statusLabel = match($status) { 'paid_out' => 'Paid out', 'pending' => 'Pending review', default => ucfirst($status) };
                            @endphp
                            <tr>
                                <td>{{ $conversion->created_at?->format('M j, Y · H:i') }}</td>
                                @if($analyticsIsAdmin)<td class="strong">{{ $conversion->affiliate?->public_code ?: '—' }}</td>@endif
                                <td>
                                    @if($orderId !== '-')
                                        <a href="{{ route('affiliate.orders.show', ['commission' => $conversion->id]) }}" class="stellar-order-link" title="View order {{ $orderId }}">
                                            <span class="stellar-code">{{ $orderId }}</span>
                                            <span>View</span>
                                        </a>
                                    @else
                                        <span class="stellar-code">—</span>
                                    @endif
                                </td>
                                <td>{{ $conversion->product ? config('affiliate.products.'.$conversion->product.'.label', ucfirst($conversion->product)) : 'Unassigned' }}</td>
                                <td>{{ $conversion->type === 'initial' ? 'First payment' : ($conversion->type === 'recurring' ? 'Recurring' : '—') }}</td>
                                <td class="strong">{{ $conversion->currency ?: 'EUR' }} {{ \App\Support\CommissionMath::display($conversion->amount) }}</td>
                                <td><span class="stellar-badge {{ $statusClass }}">{{ $statusLabel }}</span></td>
                            </tr>
                        @endforeach
                        </tbody>
                    </table>
                </div>
            @endif
        </section>
    @endif
@endsection
