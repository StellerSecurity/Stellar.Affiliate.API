@php
    $workspaceItems = [
        ['route' => 'affiliate.dashboard', 'label' => 'Dashboard'],
        ['route' => 'affiliate.campaigns.index', 'label' => 'Campaigns'],
        ['route' => 'affiliate.sales', 'label' => 'Conversions'],
        ['route' => 'affiliate.analytics', 'label' => 'Analytics'],
        ['route' => 'affiliate.payouts', 'label' => 'Payouts'],
        ['route' => 'affiliate.settings', 'label' => 'Settings'],
    ];

    $adminItems = [
        ['route' => 'affiliate.admin.dashboard', 'label' => 'Overview'],
        ['route' => 'affiliate.admin.affiliates.index', 'label' => 'Affiliates'],
        ['route' => 'affiliate.admin.commissions.index', 'label' => 'Commissions'],
        ['route' => 'affiliate.admin.rates.index', 'label' => 'Commission rules'],
        ['route' => 'affiliate.admin.campaigns.index', 'label' => 'Campaigns'],
        ['route' => 'affiliate.admin.tracking.index', 'label' => 'Tracking'],
        ['route' => 'affiliate.admin.payouts.index', 'label' => 'Payouts'],
    ];
@endphp
<aside class="stellar-sidebar" data-portal-sidebar>
    @if($portalIsAdmin)
        <p class="stellar-nav-label">Admin center</p>
        <nav class="stellar-nav" aria-label="Affiliate admin navigation">
            @foreach($adminItems as $item)
                <a href="{{ route($item['route']) }}" class="stellar-nav-link {{ request()->routeIs($item['route']) || (str_contains($item['route'], 'affiliates.index') && request()->routeIs('affiliate.admin.affiliates.*')) ? 'is-active' : '' }}">
                    <span class="stellar-nav-dot" aria-hidden="true"></span>
                    <span>{{ $item['label'] }}</span>
                </a>
            @endforeach

            @if(auth()->user()?->canManageAffiliateRoles())
                <a href="{{ route('affiliate.admin.users.index') }}" class="stellar-nav-link {{ request()->routeIs('affiliate.admin.users.*') ? 'is-active' : '' }}">
                    <span class="stellar-nav-dot" aria-hidden="true"></span>
                    <span>Admin roles</span>
                </a>
            @endif
        </nav>

        <div class="stellar-side-card">
            <p class="eyebrow">Access</p>
            <strong>{{ ucwords(str_replace('_', ' ', auth()->user()?->affiliateAdminRole() ?: 'admin')) }}</strong>
            <p>Manage affiliates, rates, commissions and payouts.</p>
            <a class="stellar-side-link" href="mailto:{{ config('affiliate.support_email', 'info@stellarsecurity.com') }}">Contact support</a>
        </div>
    @else
        <p class="stellar-nav-label">Workspace</p>
        <nav class="stellar-nav" aria-label="Affiliate navigation">
            @if(!$portalAffiliate)
                <a href="{{ route('affiliate.dashboard') }}" class="stellar-nav-link {{ request()->routeIs('affiliate.dashboard') ? 'is-active' : '' }}">
                    <span class="stellar-nav-dot" aria-hidden="true"></span>
                    <span>Dashboard</span>
                </a>
                <a href="{{ route('affiliate.onboarding') }}" class="stellar-nav-link {{ request()->routeIs('affiliate.onboarding') ? 'is-active' : '' }}">
                    <span class="stellar-nav-dot" aria-hidden="true"></span>
                    <span>Get started</span>
                </a>
            @else
                @foreach($workspaceItems as $item)
                    @php
                        $active = request()->routeIs($item['route']);
                        if ($item['route'] === 'affiliate.campaigns.index') {
                            $active = $active || request()->routeIs('affiliate.onboarding');
                        }
                        if ($item['route'] === 'affiliate.sales') {
                            $active = $active || request()->routeIs('affiliate.orders.*');
                        }
                    @endphp
                    <a href="{{ route($item['route']) }}" class="stellar-nav-link {{ $active ? 'is-active' : '' }}">
                        <span class="stellar-nav-dot" aria-hidden="true"></span>
                        <span>{{ $item['label'] }}</span>
                    </a>
                @endforeach
            @endif
        </nav>

        <div class="stellar-side-card">
            @if(!$portalAffiliate)
                <p class="eyebrow">Setup</p>
                <strong>Finish your workspace</strong>
                <p>Create your affiliate profile and first campaign. The portal generates the tracking link for you.</p>
                <div class="stellar-actions">
                    <a href="{{ route('affiliate.onboarding') }}" class="stellar-btn stellar-btn-primary stellar-btn-small">Continue setup</a>
                </div>
            @else
                <p class="eyebrow">Affiliate code</p>
                <strong>{{ $portalAffiliate->public_code }}</strong>
                <p>Referral window: up to 180 days.</p>
            @endif
        </div>

        <div class="stellar-side-card stellar-support-mini">
            <p class="eyebrow">Support</p>
            <strong>Need help?</strong>
            <p>Email Stellar Security and we’ll help you with links, commissions or payouts.</p>
            <a class="stellar-side-link" href="mailto:{{ config('affiliate.support_email', 'info@stellarsecurity.com') }}">{{ config('affiliate.support_email', 'info@stellarsecurity.com') }}</a>
        </div>
    @endif
</aside>
