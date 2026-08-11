@extends('layouts.affiliate')

@section('title', 'Payouts · Stellar Affiliate')

@section('content')
    @php
        $hasPayoutFilters = $currentStatusFilter || $currentFromFilter || $currentToFilter || $currentPerPage !== 25;
    @endphp

    <section class="stellar-page-header">
        <div>
            <p class="stellar-eyebrow">Earnings</p>
            <h1 class="stellar-page-title">Payouts</h1>
            <p class="stellar-page-copy">See what is pending, ready for payout and already paid.</p>
        </div>
        @if(!($needsAffiliateSetup ?? false))
            <a href="{{ route('affiliate.payouts.export', request()->except('page')) }}" class="stellar-btn stellar-btn-secondary" data-export-range data-export-label="Payouts">Export CSV</a>
        @endif
    </section>

    @if($needsAffiliateSetup ?? false)
        <section class="stellar-card stellar-empty">
            <div>
                <span class="stellar-empty-icon">€</span>
                <h3>Finish setup first</h3>
                <p>Create your affiliate profile and first campaign before tracking payouts.</p>
                <div class="stellar-actions" style="justify-content: center;">
                    <a href="{{ route('affiliate.onboarding') }}" class="stellar-btn stellar-btn-primary">Continue setup</a>
                </div>
            </div>
        </section>
    @else
        <section class="stellar-grid-3">
            <article class="stellar-card stellar-metric">
                <div class="stellar-metric-label">Ready for payout</div>
                <div class="stellar-metric-value">€{{ \App\Support\CommissionMath::display($availableCommission) }}</div>
                <div class="stellar-metric-detail">Approved commission waiting to be paid.</div>
            </article>
            <article class="stellar-card stellar-metric">
                <div class="stellar-metric-label">Pending review</div>
                <div class="stellar-metric-value">€{{ \App\Support\CommissionMath::display($pendingCommission) }}</div>
                <div class="stellar-metric-detail">Commission not approved yet.</div>
            </article>
            <article class="stellar-card stellar-metric">
                <div class="stellar-metric-label">Paid out</div>
                <div class="stellar-metric-value">€{{ \App\Support\CommissionMath::display($paidCommission) }}</div>
                <div class="stellar-metric-detail">Commission marked as paid.</div>
            </article>
        </section>

        <section class="stellar-card stellar-card-pad stellar-section">
            <div class="stellar-section-head">
                <div>
                    <p class="stellar-eyebrow">How payouts work</p>
                    <h2 class="stellar-section-title">Three clear stages</h2>
                </div>
                <a href="mailto:{{ config('affiliate.support_email', 'info@stellarsecurity.com') }}" class="stellar-btn stellar-btn-secondary stellar-btn-small">Contact support</a>
            </div>
            <div class="stellar-payout-flow">
                <div><span>1</span><strong>Pending review</strong><p>The conversion is recorded and still being reviewed.</p></div>
                <div><span>2</span><strong>Approved</strong><p>The commission is ready to be included in a payout.</p></div>
                <div><span>3</span><strong>Paid out</strong><p>The commission has been paid.</p></div>
            </div>
            @if($lastPayout)
                <p class="stellar-field-help" style="margin-top:16px;">Last completed payout: {{ $lastPayout->currency ?: 'EUR' }} {{ \App\Support\CommissionMath::display($lastPayout->amount) }} on {{ $lastPayout->paid_at?->format('M j, Y') ?: $lastPayout->updated_at?->format('M j, Y') }}.</p>
            @endif
        </section>

        <section class="stellar-card stellar-card-pad stellar-section">
            <div class="stellar-section-head">
                <div>
                    <h2 class="stellar-section-title">Payout history</h2>
                    <p class="stellar-section-copy">Filter transfers by status or date, then export the same view.</p>
                </div>
                <a href="{{ route('affiliate.payouts.export', request()->except('page')) }}" class="stellar-btn stellar-btn-secondary stellar-btn-small" data-export-range data-export-label="Payouts">Export current view</a>
            </div>

            <form method="GET" class="stellar-filterbar stellar-filterbar-wide">
                <div class="stellar-field">
                    <label for="payout-status" class="stellar-label">Status</label>
                    <select id="payout-status" name="status" class="stellar-select">
                        <option value="">All statuses</option>
                        <option value="pending" {{ $currentStatusFilter === 'pending' ? 'selected' : '' }}>Pending</option>
                        <option value="processing" {{ $currentStatusFilter === 'processing' ? 'selected' : '' }}>Processing</option>
                        <option value="paid" {{ $currentStatusFilter === 'paid' ? 'selected' : '' }}>Paid</option>
                        <option value="failed" {{ $currentStatusFilter === 'failed' ? 'selected' : '' }}>Failed</option>
                    </select>
                </div>
                <div class="stellar-field">
                    <label for="payout-from" class="stellar-label">From</label>
                    <input id="payout-from" type="datetime-local" name="from" value="{{ $currentFromFilter }}" class="stellar-input">
                </div>
                <div class="stellar-field">
                    <label for="payout-to" class="stellar-label">To</label>
                    <input id="payout-to" type="datetime-local" name="to" value="{{ $currentToFilter }}" class="stellar-input">
                </div>
                <div class="stellar-field">
                    <label for="payout-per-page" class="stellar-label">Rows</label>
                    <select id="payout-per-page" name="per_page" class="stellar-select">
                        @foreach([25, 50, 100] as $size)
                            <option value="{{ $size }}" {{ $currentPerPage === $size ? 'selected' : '' }}>{{ $size }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="stellar-filter-actions">
                    <button type="submit" class="stellar-btn stellar-btn-primary">Apply filters</button>
                    @if($hasPayoutFilters)
                        <a href="{{ route('affiliate.payouts') }}" class="stellar-btn stellar-btn-secondary">Clear</a>
                    @endif
                </div>
            </form>

            @if($payouts->isEmpty())
                <div class="stellar-empty">
                    <div>
                        <span class="stellar-empty-icon">€</span>
                        <h3>{{ $hasPayoutFilters ? 'No payouts match these filters' : 'No payout transfers yet' }}</h3>
                        <p>{{ $hasPayoutFilters ? 'Clear the filters to return to your complete payout history.' : 'Your payout history will appear here after the first payout is created.' }}</p>
                    </div>
                </div>
            @else
                <div class="stellar-table-wrap">
                    <table class="stellar-table">
                        <thead><tr><th>Created</th><th>Amount</th><th>Method</th><th>Reference</th><th>Paid</th><th>Status</th></tr></thead>
                        <tbody>
                        @foreach($payouts as $payout)
                            @php
                                $status = $payout->status ?: 'unknown';
                                $statusClass = match($status) {
                                    'paid' => 'is-success',
                                    'pending', 'processing' => 'is-warning',
                                    'failed' => 'is-danger',
                                    default => '',
                                };
                            @endphp
                            <tr>
                                <td>{{ $payout->created_at?->format('M j, Y · H:i') }}</td>
                                <td class="strong">{{ $payout->currency ?: 'EUR' }} {{ \App\Support\CommissionMath::display($payout->amount) }}</td>
                                <td>{{ ucfirst($payout->method_type ?: '—') }}</td>
                                <td><span class="stellar-code" title="{{ $payout->external_reference ?: '—' }}">{{ $payout->external_reference ?: '—' }}</span></td>
                                <td>{{ $payout->paid_at?->format('M j, Y') ?: '—' }}</td>
                                <td><span class="stellar-badge {{ $statusClass }}">{{ ucfirst($status) }}</span></td>
                            </tr>
                        @endforeach
                        </tbody>
                    </table>
                </div>
                <div class="stellar-pagination">{{ $payouts->onEachSide(1)->links() }}</div>
            @endif
        </section>
    @endif
@endsection
