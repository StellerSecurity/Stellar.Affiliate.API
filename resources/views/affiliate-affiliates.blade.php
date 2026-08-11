@extends('layouts.affiliate')

@section('title', 'Affiliates · Stellar Affiliate')

@section('content')
    @php
        $affiliateAdmin = (bool) ($isAdmin ?? false);
    @endphp

    <section class="stellar-page-header">
        <div>
            <p class="stellar-eyebrow">{{ $affiliateAdmin ? 'Admin' : 'Profile' }}</p>
            <h1 class="stellar-page-title">{{ $affiliateAdmin ? 'Affiliates' : 'Your affiliate profile' }}</h1>
            <p class="stellar-page-copy">{{ $affiliateAdmin ? 'Create and manage affiliate accounts.' : 'Your affiliate account and referral details.' }}</p>
        </div>

        @if($affiliateAdmin)
            <form method="GET" class="stellar-filterbar" role="search">
                <div class="stellar-field">
                    <label for="affiliate-search" class="stellar-label">Search</label>
                    <input id="affiliate-search" type="search" name="q" value="{{ $search }}" class="stellar-input" placeholder="Name, email or code">
                </div>
                <button type="submit" class="stellar-btn stellar-btn-secondary">Search</button>
            </form>
        @endif
    </section>

    @if(!$affiliateAdmin && !$currentAffiliate)
        <section class="stellar-card stellar-empty">
            <div>
                <span class="stellar-empty-icon">+</span>
                <h3>Create your affiliate profile</h3>
                <p>Complete setup to create your affiliate code and first campaign.</p>
                <div class="stellar-actions" style="justify-content: center;">
                    <a href="{{ route('affiliate.onboarding') }}" class="stellar-btn stellar-btn-primary">Continue setup</a>
                </div>
            </div>
        </section>
    @elseif(!$affiliateAdmin)
        <section class="stellar-grid-2">
            <article class="stellar-card stellar-card-pad">
                <p class="stellar-eyebrow">Identity</p>
                <div class="stellar-stat-list">
                    <div class="stellar-stat-row"><span>Name</span><strong>{{ $currentAffiliate->name ?: '—' }}</strong></div>
                    <div class="stellar-stat-row"><span>Email</span><strong>{{ $currentAffiliate->email ?: '—' }}</strong></div>
                    <div class="stellar-stat-row"><span>Affiliate code</span><strong>{{ $currentAffiliate->public_code }}</strong></div>
                    <div class="stellar-stat-row"><span>Status</span><strong>{{ $currentAffiliate->status === 'banned' ? 'Disabled' : ucfirst($currentAffiliate->status ?: 'unknown') }}</strong></div>
                    <div class="stellar-stat-row"><span>Created</span><strong>{{ $currentAffiliate->created_at?->format('M j, Y') }}</strong></div>
                </div>
            </article>
            <article class="stellar-card stellar-card-pad">
                <p class="stellar-eyebrow">Next action</p>
                <h2 class="stellar-section-title">Use campaign links for promotion</h2>
                <p class="stellar-section-copy">Use campaigns to compare channels and placements.</p>
                <div class="stellar-actions">
                    <a href="{{ route('affiliate.campaigns.index') }}" class="stellar-btn stellar-btn-primary">Open campaigns</a>
                </div>
            </article>
        </section>
    @else
        <section class="stellar-grid-2">
            <article class="stellar-card stellar-card-pad">
                <p class="stellar-eyebrow">New affiliate</p>
                <h2 class="stellar-section-title">Create affiliate identity</h2>
                <p class="stellar-section-copy">Add the affiliate details and create the account.</p>

                <form method="POST" action="{{ route('affiliate.affiliates.store') }}" class="stellar-form-grid" style="margin-top: 18px;">
                    @csrf
                    <div class="stellar-field">
                        <label for="admin-affiliate-name" class="stellar-label">Name</label>
                        <input id="admin-affiliate-name" name="name" value="{{ old('name') }}" class="stellar-input" maxlength="255" required placeholder="Affiliate name">
                    </div>
                    <div class="stellar-field">
                        <label for="admin-affiliate-email" class="stellar-label">Email <span class="stellar-label-note">optional</span></label>
                        <input id="admin-affiliate-email" type="email" name="email" value="{{ old('email') }}" class="stellar-input" maxlength="255" placeholder="affiliate@example.com">
                    </div>
                    <div class="stellar-field">
                        <label for="admin-affiliate-code" class="stellar-label">Public code <span class="stellar-label-note">optional</span></label>
                        <input id="admin-affiliate-code" name="public_code" value="{{ old('public_code') }}" class="stellar-input" maxlength="50" placeholder="Auto-generated if empty">
                    </div>
                    <div class="stellar-field">
                        <label for="admin-affiliate-redirect" class="stellar-label">Fallback destination <span class="stellar-label-note">optional</span></label>
                        <input id="admin-affiliate-redirect" type="url" name="base_redirect_url" value="{{ old('base_redirect_url') }}" class="stellar-input" maxlength="2048" placeholder="https://...">
                    </div>
                    <label class="stellar-checkbox"><input type="checkbox" name="is_active" value="1" {{ old('is_active', '1') ? 'checked' : '' }}> Create as active</label>
                    <button type="submit" class="stellar-btn stellar-btn-primary">Create affiliate</button>
                </form>
            </article>

            <article class="stellar-card stellar-card-pad">
                <p class="stellar-eyebrow">Admin notes</p>
                <div class="stellar-checklist">
                    <div class="stellar-check-item is-done"><span class="stellar-check-dot">1</span><div><strong>Automatic user linking</strong><span>If the email matches a portal user, the affiliate is linked to that user ID.</span></div></div>
                    <div class="stellar-check-item"><span class="stellar-check-dot">2</span><div><strong>Automatic public code</strong><span>Leave Public code empty to avoid manual code mistakes.</span></div></div>
                    <div class="stellar-check-item"><span class="stellar-check-dot">3</span><div><strong>Validated destination</strong><span>Custom destinations must use a valid URL.</span></div></div>
                </div>
            </article>
        </section>

        <section class="stellar-card stellar-card-pad stellar-section">
            <div class="stellar-section-head">
                <div>
                    <h2 class="stellar-section-title">Affiliate directory</h2>
                    <p class="stellar-section-copy">{{ $affiliates->total() }} affiliate{{ $affiliates->total() === 1 ? '' : 's' }}.</p>
                </div>
            </div>

            @if($affiliates->isEmpty())
                <div class="stellar-empty"><div><span class="stellar-empty-icon">+</span><h3>No affiliates found</h3><p>Create the first affiliate above or clear the search.</p></div></div>
            @else
                <div class="stellar-table-wrap">
                    <table class="stellar-table">
                        <thead><tr><th>Affiliate</th><th>Code</th><th>Destination</th><th>Status</th><th>Created</th></tr></thead>
                        <tbody>
                        @foreach($affiliates as $affiliate)
                            <tr>
                                <td><span class="strong">{{ $affiliate->name ?: 'Unnamed' }}</span><br><span style="color: var(--stellar-muted);">{{ $affiliate->email ?: 'No email' }}</span></td>
                                <td><span class="stellar-code">{{ $affiliate->public_code }}</span></td>
                                <td><span class="stellar-code" title="{{ $affiliate->base_redirect_url ?: 'Not set' }}">{{ $affiliate->base_redirect_url ?: 'Not set' }}</span></td>
                                <td><span class="stellar-badge {{ $affiliate->status === 'active' ? 'is-success' : ($affiliate->status === 'banned' ? 'is-danger' : 'is-warning') }}">{{ $affiliate->status === 'banned' ? 'Disabled' : ucfirst($affiliate->status ?: 'unknown') }}</span></td>
                                <td>{{ $affiliate->created_at?->format('M j, Y') }}</td>
                            </tr>
                        @endforeach
                        </tbody>
                    </table>
                </div>
                <div class="stellar-pagination">{{ $affiliates->onEachSide(1)->links() }}</div>
            @endif
        </section>
    @endif
@endsection
