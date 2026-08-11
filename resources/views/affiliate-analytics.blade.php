@extends('layouts.affiliate')

@section('title', 'Analytics · Stellar Affiliate')

@section('content')
    @php
        $maxDaily = max(1, collect($daily)->map(fn($row) => max((int) ($row['clicks'] ?? 0), (int) ($row['sales'] ?? 0)))->max() ?? 1);
        $conversionExportQuery = $periodFromInput ? ['from' => $periodFromInput] : [];
    @endphp

    <section class="stellar-page-header">
        <div>
            <p class="stellar-eyebrow">Performance</p>
            <h1 class="stellar-page-title">Analytics</h1>
            <p class="stellar-page-copy">See what drives clicks, orders and commission.</p>
        </div>
        @if(!($needsAffiliateSetup ?? false))
            <div class="stellar-actions">
                <a href="{{ route('affiliate.analytics.traffic.export', ['range' => $currentRange]) }}" class="stellar-btn stellar-btn-secondary" data-download>Export traffic CSV</a>
                <a href="{{ route('affiliate.sales.export', $conversionExportQuery) }}" class="stellar-btn stellar-btn-secondary" data-download>Export conversions</a>
            </div>
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
        <section class="stellar-card stellar-card-pad stellar-period-bar">
            <div>
                <p class="stellar-eyebrow">Reporting period</p>
                <strong>{{ $rangeLabel }}</strong>
            </div>
            <form method="GET" class="stellar-period-options">
                @foreach(['7' => '7 days', '30' => '30 days', '90' => '90 days', 'all' => 'All time'] as $value => $label)
                    <button type="submit" name="range" value="{{ $value }}" class="stellar-period-button {{ $currentRange === $value ? 'is-active' : '' }}">{{ $label }}</button>
                @endforeach
            </form>
        </section>

        <section class="stellar-grid-4 stellar-section">
            <article class="stellar-card stellar-metric">
                <div class="stellar-metric-label">Clicks</div>
                <div class="stellar-metric-value">{{ number_format($clicksPeriod) }}</div>
                <div class="stellar-metric-detail">{{ $rangeLabel }}.</div>
            </article>
            <article class="stellar-card stellar-metric">
                <div class="stellar-metric-label">Conversions</div>
                <div class="stellar-metric-value">{{ number_format($salesPeriod) }}</div>
                <div class="stellar-metric-detail">Pending, approved and paid conversions.</div>
            </article>
            <article class="stellar-card stellar-metric">
                <div class="stellar-metric-label">Order value</div>
                <div class="stellar-metric-value">€{{ number_format((float) $orderValuePeriod, 2, '.', ',') }}</div>
                <div class="stellar-metric-detail">Final order totals after discounts.</div>
            </article>
            <article class="stellar-card stellar-metric">
                <div class="stellar-metric-label">Commission</div>
                <div class="stellar-metric-value">€{{ \App\Support\CommissionMath::display($commissionPeriod) }}</div>
                <div class="stellar-metric-detail">Pending, approved and paid commission.</div>
            </article>
        </section>

        <section class="stellar-grid-2 stellar-section">
            <article class="stellar-card stellar-card-pad">
                <div class="stellar-section-head">
                    <div>
                        <h2 class="stellar-section-title">Activity · {{ $chartLabel }}</h2>
                        <p class="stellar-section-copy">Clicks and conversions by day. Longer periods keep the chart to the latest 30 days.</p>
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
                        <h2 class="stellar-section-title">Performance quality</h2>
                        <p class="stellar-section-copy">The numbers that help you compare placements.</p>
                    </div>
                </div>
                <div class="stellar-stat-list">
                    <div class="stellar-stat-row"><span>Conversion rate</span><strong>{{ number_format($conversionRate, 2) }}%</strong></div>
                    <div class="stellar-stat-row"><span>Commission per click</span><strong>€{{ \App\Support\CommissionMath::display($epc) }}</strong></div>
                    <div class="stellar-stat-row"><span>Average order value</span><strong>€{{ number_format((float) $averageOrderValue, 2, '.', ',') }}</strong></div>
                    <div class="stellar-stat-row"><span>Tracked sessions</span><strong>{{ number_format($sessionsPeriod) }}</strong></div>
                    <div class="stellar-stat-row"><span>Referral window</span><strong>180 days</strong></div>
                </div>
            </article>
        </section>

        <section class="stellar-card stellar-card-pad stellar-section">
            <div class="stellar-section-head">
                <div>
                    <h2 class="stellar-section-title">Campaign performance</h2>
                    <p class="stellar-section-copy">Compare placements using the same reporting period.</p>
                </div>
                <a href="{{ route('affiliate.campaigns.index') }}" class="stellar-btn stellar-btn-secondary stellar-btn-small">Manage campaigns</a>
            </div>

            @if($topCampaigns->isEmpty() && $unattributedClicks === 0 && $unattributedConversions === 0)
                <div class="stellar-empty"><div><h3>No campaign data yet</h3><p>Create a campaign and share its tracking link to start comparing performance.</p></div></div>
            @else
                <div class="stellar-table-wrap">
                    <table class="stellar-table">
                        <thead>
                        <tr>
                            <th>Campaign</th>
                            <th>Product</th>
                            <th>Clicks</th>
                            <th>Conversions</th>
                            <th>Conv. rate</th>
                            <th>Order value</th>
                            <th>Commission</th>
                        </tr>
                        </thead>
                        <tbody>
                        @foreach($topCampaigns as $campaign)
                            @php
                                $campaignClicks = (int) ($campaign->period_clicks_count ?? 0);
                                $campaignConversions = (int) ($campaign->period_conversions_count ?? 0);
                                $campaignRate = $campaignClicks > 0 ? ($campaignConversions / $campaignClicks) * 100 : 0;
                            @endphp
                            <tr>
                                <td><strong>{{ $campaign->name }}</strong><div class="stellar-cell-sub">{{ ucfirst($campaign->source ?: 'other') }}</div></td>
                                <td>{{ app(\App\Services\AffiliateCommissionPolicy::class)->productLabel($campaign->product) }}</td>
                                <td>{{ number_format($campaignClicks) }}</td>
                                <td>{{ number_format($campaignConversions) }}</td>
                                <td>{{ number_format($campaignRate, 2) }}%</td>
                                <td>€{{ number_format((float) ($campaign->period_order_value_total ?? 0), 2, '.', ',') }}</td>
                                <td class="strong">€{{ \App\Support\CommissionMath::display($campaign->period_commission_total ?? 0) }}</td>
                            </tr>
                        @endforeach
                        @if($unattributedClicks > 0 || $unattributedConversions > 0)
                            @php
                                $unattributedRate = $unattributedClicks > 0 ? ($unattributedConversions / $unattributedClicks) * 100 : 0;
                            @endphp
                            <tr>
                                <td><strong>Unattributed</strong><div class="stellar-cell-sub">Traffic without a campaign ID</div></td>
                                <td>—</td>
                                <td>{{ number_format($unattributedClicks) }}</td>
                                <td>{{ number_format($unattributedConversions) }}</td>
                                <td>{{ number_format($unattributedRate, 2) }}%</td>
                                <td>€{{ number_format((float) $unattributedOrderValue, 2, '.', ',') }}</td>
                                <td class="strong">€{{ \App\Support\CommissionMath::display($unattributedCommission) }}</td>
                            </tr>
                        @endif
                        </tbody>
                    </table>
                </div>
            @endif
        </section>

        <section class="stellar-card stellar-card-pad stellar-section">
            <div class="stellar-section-head">
                <div>
                    <h2 class="stellar-section-title">Recent conversions</h2>
                    <p class="stellar-section-copy">Latest orders in {{ strtolower($rangeLabel) }}.</p>
                </div>
                <a href="{{ route('affiliate.sales') }}" class="stellar-btn stellar-btn-secondary stellar-btn-small">All conversions</a>
            </div>

            @if($recentConversions->isEmpty())
                <div class="stellar-empty">
                    <div>
                        <span class="stellar-empty-icon">↗</span>
                        <h3>No conversions in this period</h3>
                        <p>Try a longer reporting period or share a campaign link.</p>
                    </div>
                </div>
            @else
                <div class="stellar-table-wrap">
                    <table class="stellar-table">
                        <thead><tr><th>Date</th><th>Order ID</th><th>Campaign</th><th>Product</th><th>Order value</th><th>Commission</th><th>Status</th></tr></thead>
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
                                <td>
                                    @if($orderId !== '-')
                                        <a href="{{ route('affiliate.orders.show', ['commission' => $conversion->id]) }}" class="stellar-order-link"><span class="stellar-code">{{ $orderId }}</span><span>View</span></a>
                                    @else
                                        —
                                    @endif
                                </td>
                                <td>{{ $conversion->campaign?->name ?: 'Legacy' }}</td>
                                <td>{{ app(\App\Services\AffiliateCommissionPolicy::class)->productLabel($conversion->product) }}</td>
                                <td>{{ $conversion->currency ?: 'EUR' }} {{ $conversion->order_amount !== null ? number_format((float) $conversion->order_amount, 2, '.', ',') : '—' }}</td>
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
