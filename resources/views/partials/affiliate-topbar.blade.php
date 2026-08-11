@php
    $accountLabel = $portalIsAdmin
        ? strtoupper(str_replace('_', ' ', auth()->user()?->affiliateAdminRole() ?: 'ADMIN'))
        : ($portalAffiliate?->public_code ?: 'SETUP');
    $accountName = $portalViewingAffiliate
        ? ($portalAffiliate?->name ?: $portalAffiliate?->public_code ?: 'Affiliate')
        : (auth()->user()?->name ?: 'Affiliate');
    $initials = strtoupper(substr($accountName, 0, 2));
    $homeRoute = $portalIsAdmin ? 'affiliate.admin.dashboard' : 'affiliate.dashboard';
@endphp
<header class="stellar-topbar">
    <div class="stellar-topbar-inner">
        <a href="{{ route($homeRoute) }}" class="stellar-brand" aria-label="Stellar Affiliate home">
            <span class="stellar-brand-mark" aria-hidden="true"></span>
            <span class="stellar-brand-copy">
                <span class="stellar-wordmark">stellar<span class="accent">.</span> affiliate</span>
                <span class="stellar-brand-subtitle">{{ $portalIsAdmin ? 'Administration' : 'Affiliate workspace' }}</span>
            </span>
        </a>

        <div class="stellar-topbar-actions">
            <div class="stellar-account-pill">
                <div class="stellar-account-copy">
                    <strong>{{ $accountName }}</strong>
                    <span>{{ $accountLabel }}</span>
                </div>
                <span class="stellar-avatar">{{ $initials }}</span>
            </div>

            @if($portalViewingAffiliate)
                <form method="POST" action="{{ route('affiliate.impersonation.stop') }}">
                    @csrf
                    <button type="submit" class="stellar-btn stellar-btn-secondary stellar-btn-small">Exit view</button>
                </form>
            @else
                <form method="POST" action="{{ route('affiliate.logout') }}">
                    @csrf
                    <button type="submit" class="stellar-btn stellar-btn-secondary stellar-btn-small" aria-label="Log out">Log out</button>
                </form>
            @endif

            <button type="button" class="stellar-mobile-menu" data-portal-menu aria-expanded="false" aria-label="Open navigation">
                <svg width="18" height="18" viewBox="0 0 24 24" fill="none" aria-hidden="true"><path d="M4 7h16M4 12h16M4 17h16" stroke="currentColor" stroke-width="1.7" stroke-linecap="round"/></svg>
            </button>
        </div>
    </div>
</header>
