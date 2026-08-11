@extends('layouts.affiliate')

@section('title', 'Commission rates · Admin')

@section('content')
    @php
        $rateRows = collect($globalRateMatrix);
        $vpnInitial = (float) ($rateRows->first(fn ($row) => $row['product'] === 'vpn' && $row['type'] === 'initial')['rate'] ?? 1.00);
        $vpnRecurring = (float) ($rateRows->first(fn ($row) => $row['product'] === 'vpn' && $row['type'] === 'recurring')['rate'] ?? 0.60);
    @endphp

    <section class="stellar-page-header">
        <div>
            <p class="stellar-eyebrow">Commission engine</p>
            <h1 class="stellar-page-title">Commission rates</h1>
            <p class="stellar-page-copy">Set shared VPN and Antivirus rates and individual eSIM rates.</p>
        </div>
    </section>

    <section class="stellar-grid-3">
        <article class="stellar-card stellar-card-pad">
            <p class="stellar-eyebrow">Shared program rate</p>
            <h2 class="stellar-section-title">VPN + Antivirus</h2>
            <p class="stellar-section-copy">These rates apply equally to every affiliate.</p>
            <div class="stellar-stat-list" style="margin-top:18px">
                <div class="stellar-stat-row"><span>First payment</span><strong>{{ rtrim(rtrim(number_format($vpnInitial * 100, 2), '0'), '.') }}%</strong></div>
                <div class="stellar-stat-row"><span>Recurring payment</span><strong>{{ rtrim(rtrim(number_format($vpnRecurring * 100, 2), '0'), '.') }}%</strong></div>
            </div>
        </article>

        @if(auth()->user()?->canManageAffiliateProgram())
            <article class="stellar-card stellar-card-pad">
                <p class="stellar-eyebrow">Shared initial rate</p>
                <h2 class="stellar-section-title">VPN + Antivirus</h2>
                <form method="POST" action="{{ route('affiliate.admin.rates.update') }}" class="stellar-inline-form" style="margin-top:18px">
                    @csrf
                    @method('PUT')
                    <input type="hidden" name="product" value="vpn">
                    <input type="hidden" name="type" value="initial">
                    <input class="stellar-input stellar-rate-input" type="number" min="0" max="100" step="0.01" name="rate_percent" value="{{ number_format($vpnInitial * 100, 2, '.', '') }}" aria-label="VPN and Antivirus initial commission percent">
                    <button type="submit" class="stellar-btn stellar-btn-primary stellar-btn-small">Update</button>
                </form>
                <p class="stellar-field-help">Updates both VPN and Antivirus together.</p>
            </article>

            <article class="stellar-card stellar-card-pad">
                <p class="stellar-eyebrow">Shared recurring rate</p>
                <h2 class="stellar-section-title">VPN + Antivirus</h2>
                <form method="POST" action="{{ route('affiliate.admin.rates.update') }}" class="stellar-inline-form" style="margin-top:18px">
                    @csrf
                    @method('PUT')
                    <input type="hidden" name="product" value="vpn">
                    <input type="hidden" name="type" value="recurring">
                    <input class="stellar-input stellar-rate-input" type="number" min="0" max="100" step="0.01" name="rate_percent" value="{{ number_format($vpnRecurring * 100, 2, '.', '') }}" aria-label="VPN and Antivirus recurring commission percent">
                    <button type="submit" class="stellar-btn stellar-btn-primary stellar-btn-small">Update</button>
                </form>
                <p class="stellar-field-help">Updates both VPN and Antivirus together.</p>
            </article>
        @endif
    </section>

    <section class="stellar-card stellar-card-pad stellar-section">
        <div class="stellar-section-head">
            <div>
                <p class="stellar-eyebrow">Per affiliate</p>
                <h2 class="stellar-section-title">eSIM commission</h2>
                <p class="stellar-section-copy">Every affiliate has one eSIM percentage. The same rate is used for initial and recurring eSIM orders.</p>
            </div>
        </div>

        <form method="GET" action="{{ route('affiliate.admin.rates.index') }}" class="stellar-filterbar stellar-section-tight">
            <div class="stellar-field stellar-filter-grow">
                <label class="stellar-label" for="rate-search">Search</label>
                <input id="rate-search" class="stellar-input" name="q" value="{{ $search }}" placeholder="Name, email or affiliate code">
            </div>
            <div class="stellar-field">
                <label class="stellar-label" for="rate-status">Status</label>
                <select id="rate-status" class="stellar-select" name="status">
                    <option value="">All statuses</option>
                    @foreach(['active', 'pending', 'banned'] as $option)
                        <option value="{{ $option }}" {{ $statusFilter === $option ? 'selected' : '' }}>{{ $option === 'banned' ? 'Disabled' : ucfirst($option) }}</option>
                    @endforeach
                </select>
            </div>
            <button class="stellar-btn stellar-btn-primary" type="submit">Filter</button>
        </form>

        <div class="stellar-table-wrap stellar-section-tight">
            <table class="stellar-table">
                <thead>
                    <tr>
                        <th>Affiliate</th>
                        <th>Status</th>
                        <th>Conversions</th>
                        <th>Commission total</th>
                        <th>eSIM commission</th>
                        <th>Action</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($affiliates as $affiliate)
                        <tr>
                            <td>
                                <strong>{{ $affiliate->name ?: 'Unnamed affiliate' }}</strong>
                                <div class="stellar-cell-sub">{{ $affiliate->public_code }} · {{ $affiliate->email ?: 'No email' }}</div>
                            </td>
                            <td><span class="stellar-badge {{ $affiliate->status === 'active' ? 'is-success' : ($affiliate->status === 'banned' ? 'is-danger' : 'is-warning') }}">{{ $affiliate->status === 'banned' ? 'Disabled' : ucfirst($affiliate->status) }}</span></td>
                            <td>{{ number_format($affiliate->conversions_count) }}</td>
                            <td class="strong">€{{ \App\Support\CommissionMath::display($affiliate->earned_commission_total ?? 0) }}</td>
                            <td>
                                @if(auth()->user()?->canManageAffiliateProgram())
                                    <form method="POST" action="{{ route('affiliate.admin.affiliates.rates.update', $affiliate) }}" class="stellar-inline-form">
                                        @csrf
                                        @method('PUT')
                                        <input type="hidden" name="product" value="esim">
                                        <input class="stellar-input stellar-rate-input" type="number" min="0" max="100" step="0.01" name="rate_percent" value="{{ number_format((float) $affiliate->esim_rate * 100, 2, '.', '') }}" aria-label="eSIM commission percent for {{ $affiliate->public_code }}">
                                        <button type="submit" class="stellar-btn stellar-btn-primary stellar-btn-small">Save</button>
                                    </form>
                                @else
                                    <strong>{{ number_format((float) $affiliate->esim_rate * 100, 2) }}%</strong>
                                @endif
                                <div class="stellar-cell-sub">{{ $affiliate->esim_rate_source === 'affiliate' ? 'Custom rate' : 'Default rate' }}</div>
                            </td>
                            <td>
                                <div class="stellar-actions" style="margin-top:0">
                                    <a class="stellar-btn stellar-btn-secondary stellar-btn-small" href="{{ route('affiliate.admin.affiliates.show', $affiliate) }}">Manage</a>
                                    @if(auth()->user()?->canManageAffiliateProgram())
                                        <form method="POST" action="{{ route('affiliate.admin.affiliates.rates.delete', $affiliate) }}">
                                            @csrf
                                            @method('DELETE')
                                            <input type="hidden" name="product" value="esim">
                                            <button class="stellar-text-button" type="submit">Reset to {{ rtrim(rtrim(number_format($globalEsimRate * 100, 2), '0'), '.') }}%</button>
                                        </form>
                                    @endif
                                </div>
                            </td>
                        </tr>
                    @empty
                        <tr><td colspan="6">No affiliates match the current filters.</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        <div class="stellar-pagination">{{ $affiliates->links() }}</div>
    </section>

    <section class="stellar-card stellar-card-pad stellar-section">
        <div class="stellar-section-head">
            <div>
                <p class="stellar-eyebrow">Default for new affiliates</p>
                <h2 class="stellar-section-title">eSIM program default</h2>
                <p class="stellar-section-copy">Used for new affiliates and when an eSIM rate is reset.</p>
            </div>
        </div>

        <div class="stellar-rate-card" style="max-width:420px">
            <div><strong>Stellar eSIM</strong><span>Initial + recurring</span></div>
            <div class="stellar-rate-value">{{ number_format($globalEsimRate * 100, 2) }}%</div>
            @if(auth()->user()?->canManageAffiliateProgram())
                <form method="POST" action="{{ route('affiliate.admin.rates.update') }}" class="stellar-inline-form">
                    @csrf
                    @method('PUT')
                    <input type="hidden" name="product" value="esim">
                    <input class="stellar-input stellar-rate-input" type="number" min="0" max="100" step="0.01" name="rate_percent" value="{{ number_format($globalEsimRate * 100, 2, '.', '') }}" aria-label="Default eSIM commission percent">
                    <button type="submit" class="stellar-btn stellar-btn-secondary stellar-btn-small">Update default</button>
                </form>
            @endif
        </div>
    </section>
@endsection
