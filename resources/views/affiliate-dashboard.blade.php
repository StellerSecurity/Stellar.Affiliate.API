@extends('layouts.affiliate')

@section('title', 'Dashboard · Stellar Affiliate')

@section('content')
    @php
        $dashboardIsAdmin = (bool) ($admin ?? false);
        $setupReady = $dashboardIsAdmin || ($currentAffiliate && $campaignCount > 0);
    @endphp

    @if($needsAffiliateSetup)
        <section class="stellar-hero">
            <div class="stellar-hero-grid">
                <div>
                    <p class="stellar-eyebrow">Welcome to Stellar Affiliate</p>
                    <h1 class="stellar-hero-title">Turn your first referral into a <span class="accent-text">trackable campaign.</span></h1>
                    <p class="stellar-hero-copy">Your account is ready. Finish the guided setup and we will generate your affiliate profile, campaign and copy-ready tracking link.</p>
                    <div class="stellar-actions">
                        <a href="{{ route('affiliate.onboarding') }}" class="stellar-btn stellar-btn-primary">Start guided setup</a>
                    </div>
                </div>
                <div class="stellar-hero-panel">
                    <span class="stellar-hero-panel-label">Setup</span>
                    <div class="stellar-hero-panel-value">1 of 4 steps complete</div>
                    <div class="stellar-progress" style="margin-top: 12px;"><span style="width: 25%"></span></div>
                    <p class="stellar-field-help" style="margin-top: 10px;">Complete setup to start tracking referrals.</p>
                </div>
            </div>
        </section>
    @else
        <section class="stellar-page-header">
            <div>
                <p class="stellar-eyebrow">{{ $dashboardIsAdmin ? 'Program overview' : 'Affiliate dashboard' }}</p>
                <h1 class="stellar-page-title">{{ $dashboardIsAdmin ? 'Affiliate performance at a glance.' : 'Everything that matters, in one place.' }}</h1>
                <p class="stellar-page-copy">
                    {{ $dashboardIsAdmin ? 'Clicks, conversions, commissions and payouts across all affiliates.' : 'Track clicks, conversions, commissions and payouts.' }}
                </p>
            </div>
            @if(!$dashboardIsAdmin && $currentAffiliate)
                <span class="stellar-kicker">{{ $currentAffiliate->public_code }}</span>
            @endif
        </section>

        @if(!$setupReady)
            <section class="stellar-hero" style="min-height: 180px;">
                <div class="stellar-hero-grid">
                    <div>
                        <p class="stellar-eyebrow">One step left</p>
                        <h2 class="stellar-hero-title" style="font-size: clamp(28px, 3.5vw, 40px);">Create your first campaign and <span class="accent-text">get your link.</span></h2>
                        <p class="stellar-hero-copy">Your affiliate profile is active. A campaign gives your traffic a clear source and produces the URL you can share.</p>
                        <div class="stellar-actions">
                            <a href="{{ route('affiliate.onboarding') }}" class="stellar-btn stellar-btn-primary">Finish setup</a>
                        </div>
                    </div>
                    <div class="stellar-hero-panel">
                        <span class="stellar-hero-panel-label">Progress</span>
                        <div class="stellar-hero-panel-value">Profile ready</div>
                        <div class="stellar-progress" style="margin-top: 12px;"><span style="width: 50%"></span></div>
                    </div>
                </div>
            </section>
        @elseif(!$dashboardIsAdmin && $quickTrackingUrl && $currentAffiliate?->status === 'active')
            <section class="stellar-hero" style="min-height: 188px;">
                <div class="stellar-hero-grid">
                    <div>
                        <p class="stellar-eyebrow">Ready to share</p>
                        <h2 class="stellar-hero-title" style="font-size: clamp(28px, 3.5vw, 40px);">Your tracking link is <span class="accent-text">one tap away.</span></h2>
                        <p class="stellar-hero-copy">Primary campaign: <strong>{{ $primaryCampaign?->name }}</strong>. Copy the complete link and use it wherever you promote Stellar.</p>
                        <div class="stellar-actions">
                            <button type="button" class="stellar-btn stellar-btn-primary" data-copy="{{ $quickTrackingUrl }}">Copy tracking link</button>
                            <a href="{{ route('affiliate.campaigns.index') }}" class="stellar-btn stellar-btn-secondary">Manage campaigns</a>
                        </div>
                    </div>
                    <div class="stellar-hero-panel">
                        <span class="stellar-hero-panel-label">Active campaigns</span>
                        <div class="stellar-hero-panel-value">{{ number_format($campaignCount) }}</div>
                        <div class="stellar-link-box" style="margin-top: 12px;">
                            <div class="stellar-link-value" title="{{ $quickTrackingUrl }}">{{ $quickTrackingUrl }}</div>
                            <button type="button" class="stellar-btn stellar-btn-secondary stellar-btn-small" data-copy="{{ $quickTrackingUrl }}">Copy</button>
                        </div>
                    </div>
                </div>
            </section>
        @elseif(!$dashboardIsAdmin && $quickTrackingUrl)
            <section class="stellar-hero" style="min-height: 180px;">
                <div class="stellar-hero-grid">
                    <div>
                        <p class="stellar-eyebrow">Campaign ready</p>
                        <h2 class="stellar-hero-title" style="font-size: clamp(28px, 3.5vw, 40px);">Your setup is <span class="accent-text">complete.</span></h2>
                        <p class="stellar-hero-copy">Your tracking link activates when your affiliate account is active.</p>
                        <div class="stellar-actions">
                            <a href="mailto:{{ config('affiliate.support_email', 'info@stellarsecurity.com') }}" class="stellar-btn stellar-btn-primary">Contact support</a>
                            <a href="{{ route('affiliate.campaigns.index') }}" class="stellar-btn stellar-btn-secondary">View campaign</a>
                        </div>
                    </div>
                    <div class="stellar-hero-panel">
                        <span class="stellar-hero-panel-label">Account status</span>
                        <div class="stellar-hero-panel-value">{{ $currentAffiliate?->status === 'pending' ? 'Pending approval' : 'Inactive' }}</div>
                    </div>
                </div>
            </section>
        @endif

        <section class="stellar-grid-4 stellar-metrics">
            <article class="stellar-card stellar-metric">
                <span class="stellar-metric-icon">
                    <svg viewBox="0 0 24 24" fill="none"><path d="M5 7.5h14v10H5v-10Zm3 3h8m-8 3h5" stroke="currentColor" stroke-width="1.6" stroke-linecap="round"/></svg>
                </span>
                <div class="stellar-metric-label">Commission total</div>
                <div class="stellar-metric-value">€{{ \App\Support\CommissionMath::display($totalEarnings) }}</div>
                <div class="stellar-metric-detail">Pending, approved and paid commissions.</div>
            </article>

            <article class="stellar-card stellar-metric">
                <span class="stellar-metric-icon">
                    <svg viewBox="0 0 24 24" fill="none"><path d="m9 8 3-3 3 3m-3-3v9m-5 5h10a2 2 0 0 0 2-2v-5M5 12v5a2 2 0 0 0 2 2" stroke="currentColor" stroke-width="1.6" stroke-linecap="round" stroke-linejoin="round"/></svg>
                </span>
                <div class="stellar-metric-label">Tracked clicks</div>
                <div class="stellar-metric-value">{{ number_format($totalClicks) }}</div>
                <div class="stellar-metric-detail">{{ number_format($clicksLast30) }} in the last 30 days.</div>
            </article>

            <article class="stellar-card stellar-metric">
                <span class="stellar-metric-icon">
                    <svg viewBox="0 0 24 24" fill="none"><path d="M6 12.5 10 16l8-9" stroke="currentColor" stroke-width="1.9" stroke-linecap="round" stroke-linejoin="round"/><path d="M20 12a8 8 0 1 1-4.1-7" stroke="currentColor" stroke-width="1.5" stroke-linecap="round"/></svg>
                </span>
                <div class="stellar-metric-label">Conversions</div>
                <div class="stellar-metric-value">{{ number_format($totalConversions) }}</div>
                <div class="stellar-metric-detail">{{ number_format($salesLast30) }} in the last 30 days.</div>
            </article>

            <article class="stellar-card stellar-metric">
                <span class="stellar-metric-icon">
                    <svg viewBox="0 0 24 24" fill="none"><path d="M5 18 10 13l3 3 6-8" stroke="currentColor" stroke-width="1.7" stroke-linecap="round" stroke-linejoin="round"/><path d="M15 8h4v4" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round"/></svg>
                </span>
                @if($dashboardIsAdmin)
                    <div class="stellar-metric-label">Affiliates</div>
                    <div class="stellar-metric-value">{{ number_format($totalAffiliates) }}</div>
                    <div class="stellar-metric-detail">{{ number_format($campaignCount) }} campaign{{ $campaignCount === 1 ? '' : 's' }} in the program.</div>
                @else
                    <div class="stellar-metric-label">Conversion rate</div>
                    <div class="stellar-metric-value">{{ number_format($conversionRate, 2) }}%</div>
                    <div class="stellar-metric-detail">Conversions divided by tracked clicks.</div>
                @endif
            </article>
        </section>

        <section class="stellar-grid-2 stellar-section">
            <article class="stellar-card stellar-card-pad">
                <div class="stellar-section-head">
                    <div>
                        <h2 class="stellar-section-title">Commission status</h2>
                        <p class="stellar-section-copy">See what is pending, approved and already paid.</p>
                    </div>
                </div>
                <div class="stellar-stat-list">
                    <div class="stellar-stat-row"><span>Pending review</span><strong>€{{ \App\Support\CommissionMath::display($pendingCommission) }}</strong></div>
                    <div class="stellar-stat-row"><span>Approved</span><strong>€{{ \App\Support\CommissionMath::display($approvedCommission) }}</strong></div>
                    <div class="stellar-stat-row"><span>Paid out</span><strong>€{{ \App\Support\CommissionMath::display($paidCommission) }}</strong></div>
                    <div class="stellar-stat-row"><span>Tracked sessions</span><strong>{{ number_format($totalSessions) }}</strong></div>
                    <div class="stellar-stat-row"><span>{{ $dashboardIsAdmin ? 'Campaigns' : 'Your campaigns' }}</span><strong>{{ number_format($campaignCount) }}</strong></div>
                </div>
                <div class="stellar-actions">
                    <a href="{{ route('affiliate.payouts') }}" class="stellar-btn stellar-btn-secondary stellar-btn-small">View payouts</a>
                </div>
            </article>

            <article class="stellar-card stellar-card-pad">
                <div class="stellar-section-head">
                    <div>
                        <h2 class="stellar-section-title">Last 30 days</h2>
                        <p class="stellar-section-copy">Your performance over the last 30 days.</p>
                    </div>
                </div>
                <div class="stellar-stat-list">
                    <div class="stellar-stat-row"><span>Clicks</span><strong>{{ number_format($clicksLast30) }}</strong></div>
                    <div class="stellar-stat-row"><span>Conversions</span><strong>{{ number_format($salesLast30) }}</strong></div>
                    <div class="stellar-stat-row"><span>Conversion rate</span><strong>{{ number_format($conversionRate, 2) }}%</strong></div>
                    <div class="stellar-stat-row"><span>Referral window</span><strong>180 days</strong></div>
                </div>
                <div class="stellar-actions">
                    <a href="{{ route('affiliate.analytics') }}" class="stellar-btn stellar-btn-secondary stellar-btn-small">Open analytics</a>
                </div>
            </article>
        </section>

        @if(!$dashboardIsAdmin)
            @php
                $esimFeed = (string) config('affiliate.resources.esim_feed_url');
            @endphp
            <section class="stellar-card stellar-card-pad stellar-section stellar-resource-card">
                <div class="stellar-resource-main">
                    <div>
                        <p class="stellar-eyebrow">Affiliate resource</p>
                        <h2 class="stellar-section-title">Stellar eSIM product feed</h2>
                        <p class="stellar-section-copy">Live eSIM product feed for your site or storefront.</p>
                    </div>
                    <div class="stellar-actions">
                        <a class="stellar-btn stellar-btn-primary" href="{{ $esimFeed }}" target="_blank" rel="noopener noreferrer">Open feed</a>
                        <button type="button" class="stellar-btn stellar-btn-secondary" data-copy="{{ $esimFeed }}">Copy URL</button>
                    </div>
                </div>
                <div class="stellar-resource-url">{{ $esimFeed }}</div>
            </section>
        @endif

        <section class="stellar-card stellar-card-pad stellar-section">
            <div class="stellar-section-head">
                <div>
                    <h2 class="stellar-section-title">Recent conversions</h2>
                    <p class="stellar-section-copy">Your latest conversions and commission status.</p>
                </div>
                <a href="{{ route('affiliate.sales') }}" class="stellar-btn stellar-btn-secondary stellar-btn-small">View all</a>
            </div>

            @if($latestSales->isEmpty())
                <div class="stellar-empty">
                    <div>
                        <span class="stellar-empty-icon">↗</span>
                        <h3>No conversions yet</h3>
                        <p>Share a campaign link. Your first conversion will appear here automatically.</p>
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
                            @if($dashboardIsAdmin)<th>Affiliate</th>@endif
                            <th>Order ID</th>
                            <th>Product</th>
                            <th>Type</th>
                            <th>Commission</th>
                            <th>Status</th>
                        </tr>
                        </thead>
                        <tbody>
                        @foreach($latestSales as $sale)
                            @php
                                $orderId = $sale->getRawOriginal('order_id') ?: '-';
                                $status = $sale->status ?: 'unknown';
                                $statusClass = match($status) {
                                    'approved', 'paid_out' => 'is-success',
                                    'pending' => 'is-warning',
                                    'rejected' => 'is-danger',
                                    default => '',
                                };
                                $statusLabel = match($status) { 'paid_out' => 'Paid out', 'pending' => 'Pending review', default => ucfirst($status) };
                            @endphp
                            <tr>
                                <td>{{ $sale->created_at?->format('M j, Y · H:i') }}</td>
                                @if($dashboardIsAdmin)<td class="strong">{{ $sale->affiliate?->public_code ?: '—' }}</td>@endif
                                <td>
                                    @if($orderId !== '-')
                                        <a href="{{ route('affiliate.orders.show', ['commission' => $sale->id]) }}" class="stellar-order-link" title="View order {{ $orderId }}">
                                            <span class="stellar-code">{{ $orderId }}</span>
                                            <span>View</span>
                                        </a>
                                    @else
                                        <span class="stellar-code">—</span>
                                    @endif
                                </td>
                                <td>{{ app(\App\Services\AffiliateCommissionPolicy::class)->productLabel($sale->product) }}</td>
                                <td>{{ $sale->type === 'initial' ? 'First payment' : ($sale->type === 'recurring' ? 'Recurring' : '—') }}</td>
                                <td class="strong">{{ $sale->currency ?: 'EUR' }} {{ \App\Support\CommissionMath::display($sale->amount) }}</td>
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
