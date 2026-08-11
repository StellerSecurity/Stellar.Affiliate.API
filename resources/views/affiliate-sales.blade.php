@extends('layouts.affiliate')

@section('title', 'Conversions · Stellar Affiliate')

@section('content')
    @php
        $exportQuery = request()->except('page');
        $scopeLabel = $hasActiveFilters ? 'Filtered' : 'All time';
    @endphp

    <section class="stellar-page-header">
        <div>
            <p class="stellar-eyebrow">Earnings</p>
            <h1 class="stellar-page-title">Conversions</h1>
            <p class="stellar-page-copy">Reconcile every order, order value, rate and commission.</p>
        </div>
        @if(!($needsAffiliateSetup ?? false))
            <div class="stellar-actions">
                <a href="{{ route('affiliate.sales.export', $exportQuery) }}" class="stellar-btn stellar-btn-secondary" data-export-range data-export-label="Conversions">Export CSV</a>
            </div>
        @endif
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
                <div class="stellar-metric-label">{{ $scopeLabel }} commission</div>
                <div class="stellar-metric-value">€{{ \App\Support\CommissionMath::display($matchingCommission) }}</div>
                <div class="stellar-metric-detail">Pending, approved and paid commissions only.</div>
            </article>
            <article class="stellar-card stellar-metric">
                <div class="stellar-metric-label">{{ $scopeLabel }} conversions</div>
                <div class="stellar-metric-value">{{ number_format($matchingCount) }}</div>
                <div class="stellar-metric-detail">Includes rejected rows when that status is visible.</div>
            </article>
            <article class="stellar-card stellar-metric">
                <div class="stellar-metric-label">{{ $scopeLabel }} order value</div>
                <div class="stellar-metric-value">€{{ number_format((float) $matchingOrderValue, 2, '.', ',') }}</div>
                <div class="stellar-metric-detail">Customer order totals after discounts.</div>
            </article>
            <article class="stellar-card stellar-metric">
                <div class="stellar-metric-label">Average commission</div>
                <div class="stellar-metric-value">€{{ \App\Support\CommissionMath::display($avgCommission) }}</div>
                <div class="stellar-metric-detail">Average across non-rejected conversions in this view.</div>
            </article>
        </section>

        <section class="stellar-card stellar-card-pad stellar-section">
            <div class="stellar-section-head">
                <div>
                    <h2 class="stellar-section-title">Find a conversion</h2>
                    <p class="stellar-section-copy">Search by order or campaign, then narrow by date, product or status.</p>
                </div>
            </div>

            <form method="GET" class="stellar-filterbar stellar-filterbar-wide">
                <div class="stellar-field stellar-filter-search">
                    <label for="conversion-search" class="stellar-label">Search</label>
                    <input id="conversion-search" type="search" name="q" value="{{ $currentSearch }}" class="stellar-input" placeholder="Order ID or campaign">
                </div>

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
                    <label for="conversion-type" class="stellar-label">Type</label>
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
                        <option value="esim" {{ $currentProductFilter === 'esim' ? 'selected' : '' }}>Stellar eSIM</option>
                        <option value="vpn" {{ $currentProductFilter === 'vpn' ? 'selected' : '' }}>Stellar VPN</option>
                        <option value="antivirus" {{ $currentProductFilter === 'antivirus' ? 'selected' : '' }}>Stellar Antivirus</option>
                    </select>
                </div>

                <div class="stellar-field">
                    <label for="conversion-from" class="stellar-label">From</label>
                    <input id="conversion-from" type="datetime-local" name="from" value="{{ $currentFromFilter }}" class="stellar-input">
                </div>

                <div class="stellar-field">
                    <label for="conversion-to" class="stellar-label">To</label>
                    <input id="conversion-to" type="datetime-local" name="to" value="{{ $currentToFilter }}" class="stellar-input">
                </div>

                <div class="stellar-field">
                    <label for="conversion-per-page" class="stellar-label">Rows</label>
                    <select id="conversion-per-page" name="per_page" class="stellar-select">
                        @foreach([25, 50, 100] as $size)
                            <option value="{{ $size }}" {{ $currentPerPage === $size ? 'selected' : '' }}>{{ $size }}</option>
                        @endforeach
                    </select>
                </div>

                <div class="stellar-filter-actions">
                    <button type="submit" class="stellar-btn stellar-btn-primary">Apply filters</button>
                    @if($hasActiveFilters || $currentPerPage !== 25)
                        <a href="{{ route('affiliate.sales') }}" class="stellar-btn stellar-btn-secondary">Clear</a>
                    @endif
                </div>
            </form>
        </section>

        <section class="stellar-card stellar-card-pad stellar-section">
            <div class="stellar-section-head">
                <div>
                    <h2 class="stellar-section-title">Conversion ledger</h2>
                    <p class="stellar-section-copy">{{ number_format($matchingCount) }} matching conversion{{ $matchingCount === 1 ? '' : 's' }}.</p>
                </div>
                <a href="{{ route('affiliate.sales.export', $exportQuery) }}" class="stellar-btn stellar-btn-secondary stellar-btn-small" data-export-range data-export-label="Conversions">Export current view</a>
            </div>

            @if($sales->isEmpty())
                <div class="stellar-empty">
                    <div>
                        <span class="stellar-empty-icon">↗</span>
                        @if($hasActiveFilters)
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
                            <th>Order ID</th>
                            <th>Campaign</th>
                            <th>Product</th>
                            <th>Type</th>
                            <th>Order value</th>
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
                                $statusLabel = match($status) {
                                    'paid_out' => 'Paid out',
                                    'pending' => 'Pending review',
                                    default => ucfirst($status),
                                };
                                $rate = (float) $sale->rate;
                            @endphp
                            <tr>
                                <td>{{ $sale->created_at?->format('M j, Y · H:i') }}</td>
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
                                <td>
                                    <strong>{{ $sale->campaign?->name ?: 'Legacy' }}</strong>
                                    @if($sale->campaign?->source)<div class="stellar-cell-sub">{{ ucfirst($sale->campaign->source) }}</div>@endif
                                </td>
                                <td>{{ app(\App\Services\AffiliateCommissionPolicy::class)->productLabel($sale->product) }}</td>
                                <td>{{ $sale->type === 'initial' ? 'First payment' : ($sale->type === 'recurring' ? 'Recurring' : '—') }}</td>
                                <td class="strong">
                                    @if($sale->order_amount !== null)
                                        {{ $sale->currency ?: 'EUR' }} {{ number_format((float) $sale->order_amount, 2, '.', ',') }}
                                    @else
                                        —
                                    @endif
                                </td>
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
