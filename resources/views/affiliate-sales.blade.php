@extends('layouts.affiliate')

@section('title', 'Conversions · Stellar Affiliate')

@section('content')
    @php
        $salesIsAdmin = (bool) ($isAdmin ?? false);
    @endphp

    <section class="stellar-page-header">
        <div>
            <p class="stellar-eyebrow">Earnings</p>
            <h1 class="stellar-page-title">Conversions</h1>
            <p class="stellar-page-copy">View every conversion, Order ID, commission and status.</p>
        </div>
    </section>

    @if($needsAffiliateSetup ?? false)
        <section class="stellar-card stellar-empty">
            <div>
                <span class="stellar-empty-icon">↗</span>
                <h3>Finish setup to track conversions</h3>
                <p>Complete setup to start tracking conversions.</p>
                <div class="stellar-actions" style="justify-content: center;">
                    <a href="{{ route('affiliate.onboarding') }}" class="stellar-btn stellar-btn-primary">Continue setup</a>
                </div>
            </div>
        </section>
    @else
        <section class="stellar-grid-4">
            <article class="stellar-card stellar-metric">
                <div class="stellar-metric-label">All-time commission</div>
                <div class="stellar-metric-value">€{{ \App\Support\CommissionMath::display($totalCommission) }}</div>
                <div class="stellar-metric-detail">All pending, approved and paid commissions.</div>
            </article>
            <article class="stellar-card stellar-metric">
                <div class="stellar-metric-label">All-time conversions</div>
                <div class="stellar-metric-value">{{ number_format($totalSalesCount) }}</div>
                <div class="stellar-metric-detail">All pending, approved and paid conversions.</div>
            </article>
            <article class="stellar-card stellar-metric">
                <div class="stellar-metric-label">Average commission</div>
                <div class="stellar-metric-value">€{{ \App\Support\CommissionMath::display($avgCommission) }}</div>
                <div class="stellar-metric-detail">Average commission per conversion.</div>
            </article>
            <article class="stellar-card stellar-metric">
                <div class="stellar-metric-label">Last 30 days</div>
                <div class="stellar-metric-value">€{{ \App\Support\CommissionMath::display($last30Commission) }}</div>
                <div class="stellar-metric-detail">{{ number_format($last30Count) }} conversion{{ $last30Count === 1 ? '' : 's' }}.</div>
            </article>
        </section>

        <section class="stellar-card stellar-card-pad stellar-section">
            <div class="stellar-section-head">
                <div>
                    <p class="stellar-eyebrow">Commission status</p>
                    <h2 class="stellar-section-title">Know what each status means</h2>
                </div>
            </div>
            <div class="stellar-status-guide">
                <div><span class="stellar-badge is-warning">Pending review</span><p>Recorded, but not approved yet.</p></div>
                <div><span class="stellar-badge is-success">Approved</span><p>Ready to be included in a payout.</p></div>
                <div><span class="stellar-badge is-success">Paid out</span><p>The commission has been paid.</p></div>
                <div><span class="stellar-badge is-danger">Rejected</span><p>Not included in earnings or payout totals.</p></div>
            </div>
        </section>

        <section class="stellar-card stellar-card-pad stellar-section">
            <div class="stellar-section-head">
                <div>
                    <h2 class="stellar-section-title">Filter conversions</h2>
                    <p class="stellar-section-copy">Narrow down your conversions.</p>
                </div>
            </div>

            <form method="GET" class="stellar-filterbar">
                <div class="stellar-field">
                    <label for="conversion-status" class="stellar-label">Status</label>
                    <select id="conversion-status" name="status" class="stellar-select">
                        <option value="">All statuses</option>
                        <option value="pending" {{ $currentStatusFilter === 'pending' ? 'selected' : '' }}>Pending review</option>
                        <option value="approved" {{ $currentStatusFilter === 'approved' ? 'selected' : '' }}>Approved</option>
                        <option value="paid_out" {{ $currentStatusFilter === 'paid_out' ? 'selected' : '' }}>Paid out</option>
                        <option value="rejected" {{ $currentStatusFilter === 'rejected' ? 'selected' : '' }}>Rejected</option>
                    </select>
                </div>

                <div class="stellar-field">
                    <label for="conversion-type" class="stellar-label">Commission type</label>
                    <select id="conversion-type" name="type" class="stellar-select">
                        <option value="">All types</option>
                        <option value="initial" {{ $currentTypeFilter === 'initial' ? 'selected' : '' }}>First payment</option>
                        <option value="recurring" {{ $currentTypeFilter === 'recurring' ? 'selected' : '' }}>Recurring</option>
                    </select>
                </div>
                <div class="stellar-field">
                    <label for="conversion-product" class="stellar-label">Product</label>
                    <select id="conversion-product" name="product" class="stellar-select">
                        <option value="">All products</option>
                        <option value="vpn" {{ ($currentProductFilter ?? '') === 'vpn' ? 'selected' : '' }}>Stellar VPN</option>
                        <option value="antivirus" {{ ($currentProductFilter ?? '') === 'antivirus' ? 'selected' : '' }}>Stellar Antivirus</option>
                        <option value="esim" {{ ($currentProductFilter ?? '') === 'esim' ? 'selected' : '' }}>Stellar eSIM</option>
                    </select>
                </div>

                @if($salesIsAdmin)
                    <div class="stellar-field">
                        <label for="conversion-affiliate" class="stellar-label">Affiliate code</label>
                        <input id="conversion-affiliate" name="affiliate" value="{{ $currentAffiliateCode }}" class="stellar-input" placeholder="e.g. AFF123">
                    </div>
                @endif

                <button type="submit" class="stellar-btn stellar-btn-primary">Apply filters</button>
                @if($currentStatusFilter || $currentTypeFilter || ($currentProductFilter ?? false) || ($salesIsAdmin && $currentAffiliateCode))
                    <a href="{{ route('affiliate.sales') }}" class="stellar-btn stellar-btn-secondary">Clear</a>
                @endif
            </form>
        </section>

        <section class="stellar-card stellar-card-pad stellar-section">
            <div class="stellar-section-head">
                <div>
                    <h2 class="stellar-section-title">Conversion ledger</h2>
                    <p class="stellar-section-copy">Every conversion with its order, rate and payout status.</p>
                </div>
            </div>

            @if($sales->isEmpty())
                <div class="stellar-empty">
                    <div>
                        <span class="stellar-empty-icon">↗</span>
                        @if($currentStatusFilter || $currentTypeFilter || ($currentProductFilter ?? false) || ($salesIsAdmin && $currentAffiliateCode))
                            <h3>No conversions match these filters</h3>
                            <p>Clear the filters to return to the full conversion ledger.</p>
                            <div class="stellar-actions" style="justify-content: center;">
                                <a href="{{ route('affiliate.sales') }}" class="stellar-btn stellar-btn-primary stellar-btn-small">Clear filters</a>
                            </div>
                        @else
                            <h3>No conversions yet</h3>
                            <p>Share a campaign link. Your conversions will appear here automatically.</p>
                            <div class="stellar-actions" style="justify-content: center;">
                                <a href="{{ route('affiliate.campaigns.index') }}" class="stellar-btn stellar-btn-primary stellar-btn-small">Open campaigns</a>
                            </div>
                        @endif
                    </div>
                </div>
            @else
                <div class="stellar-table-wrap">
                    <table class="stellar-table">
                        <thead>
                        <tr>
                            <th>Date</th>
                            @if($salesIsAdmin)<th>Affiliate</th>@endif
                            <th>Order ID</th>
                            <th>Product</th>
                            <th>Type</th>
                            <th>Rate</th>
                            <th>Commission</th>
                            <th>Status</th>
                        </tr>
                        </thead>
                        <tbody>
                        @foreach($sales as $sale)
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
                                $rate = (float) $sale->rate;
                            @endphp
                            <tr>
                                <td>{{ $sale->created_at?->format('M j, Y · H:i') }}</td>
                                @if($salesIsAdmin)<td class="strong">{{ $sale->affiliate?->public_code ?: '—' }}</td>@endif
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
                                <td>{{ rtrim(rtrim(number_format($rate * 100, 2), '0'), '.') }}%</td>
                                <td class="strong">{{ $sale->currency ?: 'EUR' }} {{ \App\Support\CommissionMath::display($sale->amount) }}</td>
                                <td><span class="stellar-badge {{ $statusClass }}">{{ $statusLabel }}</span></td>
                            </tr>
                        @endforeach
                        </tbody>
                    </table>
                </div>

                <div class="stellar-pagination">{{ $sales->onEachSide(1)->links() }}</div>
            @endif
        </section>
    @endif
@endsection
