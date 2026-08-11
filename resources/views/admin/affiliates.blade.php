@extends('layouts.affiliate')
@section('title', 'Affiliates · Admin')
@section('content')
    <section class="stellar-page-header"><div><p class="stellar-eyebrow">Admin center</p><h1 class="stellar-page-title">Affiliates</h1><p class="stellar-page-copy">Search, inspect and manage every affiliate from one directory.</p></div></section>

    @if(auth()->user()?->canManageAffiliateProgram())
        <section class="stellar-card stellar-card-pad">
            <div class="stellar-section-head"><div><h2 class="stellar-section-title">Create affiliate</h2><p class="stellar-section-copy">Create and manage affiliate accounts.</p></div></div>
            <form method="POST" action="{{ route('affiliate.admin.affiliates.store') }}" class="stellar-filterbar">@csrf
                <div class="stellar-field stellar-filter-grow"><label class="stellar-label" for="new-affiliate-name">Name</label><input id="new-affiliate-name" class="stellar-input" name="name" required placeholder="Affiliate or company name"></div>
                <div class="stellar-field stellar-filter-grow"><label class="stellar-label" for="new-affiliate-email">Email</label><input id="new-affiliate-email" class="stellar-input" type="email" name="email" placeholder="affiliate@example.com"></div>
                <div class="stellar-field"><label class="stellar-label" for="new-affiliate-code">Code <span class="stellar-label-note">optional</span></label><input id="new-affiliate-code" class="stellar-input" name="public_code" placeholder="Auto-generated"></div>
                <div class="stellar-field"><label class="stellar-label" for="new-affiliate-status">Status</label><select id="new-affiliate-status" class="stellar-select" name="status"><option value="active">Active</option><option value="pending">Pending</option><option value="banned">Disabled</option></select></div>
                <button class="stellar-btn stellar-btn-primary" type="submit">Create affiliate</button>
            </form>
        </section>
    @endif

    <section class="stellar-card stellar-card-pad stellar-section">
        <form method="GET" class="stellar-filterbar">
            <div class="stellar-field stellar-filter-grow"><label class="stellar-label" for="affiliate-q">Search</label><input id="affiliate-q" class="stellar-input" name="q" value="{{ $search }}" placeholder="Name, email or affiliate code"></div>
            <div class="stellar-field"><label class="stellar-label" for="affiliate-status">Status</label><select id="affiliate-status" class="stellar-select" name="status"><option value="">All statuses</option>@foreach(['active','pending','banned'] as $option)<option value="{{ $option }}" {{ $statusFilter === $option ? 'selected' : '' }}>{{ $option === 'banned' ? 'Disabled' : ucfirst($option) }}</option>@endforeach</select></div>
            <button class="stellar-btn stellar-btn-primary" type="submit">Filter</button>
        </form>
    </section>

    <section class="stellar-card stellar-card-pad stellar-section">
        <div class="stellar-table-wrap"><table class="stellar-table"><thead><tr><th>Affiliate</th><th>Status</th><th>Campaigns</th><th>Clicks</th><th>Conversions</th><th>Commission total</th><th>eSIM rate</th><th></th></tr></thead><tbody>
            @forelse($affiliates as $affiliate)
                <tr><td><strong>{{ $affiliate->name ?: 'Unnamed affiliate' }}</strong><div class="stellar-cell-sub">{{ $affiliate->public_code }} · {{ $affiliate->email ?: 'No email' }}</div></td><td><span class="stellar-badge {{ $affiliate->status === 'active' ? 'is-success' : ($affiliate->status === 'banned' ? 'is-danger' : 'is-warning') }}">{{ $affiliate->status === 'banned' ? 'Disabled' : ucfirst($affiliate->status) }}</span></td><td>{{ number_format($affiliate->campaigns_count) }}</td><td>{{ number_format($affiliate->clicks_count) }}</td><td>{{ number_format($affiliate->conversions_count) }}</td><td class="strong">€{{ \App\Support\CommissionMath::display($affiliate->earned_commission_total ?? 0) }}</td><td><strong>{{ number_format((float) $affiliate->esim_rate * 100, 2) }}%</strong><div class="stellar-cell-sub">eSIM</div></td><td><div class="stellar-actions stellar-actions-compact"><a class="stellar-btn stellar-btn-secondary stellar-btn-small" href="{{ route('affiliate.admin.affiliates.show', $affiliate) }}">Manage</a>@if(auth()->user()?->affiliateAdminRole() === 'super_admin')<form method="POST" action="{{ route('affiliate.admin.affiliates.view-as', $affiliate) }}">@csrf<button class="stellar-btn stellar-btn-primary stellar-btn-small" type="submit">View</button></form>@endif</div></td></tr>
            @empty
                <tr><td colspan="8">No affiliates match the current filters.</td></tr>
            @endforelse
        </tbody></table></div>
        <div class="stellar-pagination">{{ $affiliates->links() }}</div>
    </section>
@endsection
