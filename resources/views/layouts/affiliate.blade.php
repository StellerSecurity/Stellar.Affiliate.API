@php
    $portalAffiliate = $currentAffiliate ?? request()->attributes->get('affiliate');
    $portalIsAdmin = (bool) ($isAdmin ?? $admin ?? false);
    $portalImpersonation = session('affiliate_impersonation');
    $portalViewingAffiliate = is_array($portalImpersonation) && ! $portalIsAdmin && $portalAffiliate;
@endphp
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="color-scheme" content="light">
    <title>@yield('title', 'Stellar Affiliate')</title>

    <link rel="stylesheet" href="{{ asset('css/stellar-tailwind.css') }}">
    <link rel="stylesheet" href="{{ asset('css/stellar-affiliate.css') }}">
    <script src="{{ asset('js/affiliate-portal.js') }}" defer></script>
</head>
<body class="stellar-body">
<div class="stellar-app">
    @include('partials.affiliate-topbar')

    @if($portalViewingAffiliate)
        <div class="stellar-impersonation-bar" role="status">
            <div class="stellar-impersonation-inner">
                <div>
                    <strong>Viewing as {{ $portalAffiliate->name ?: $portalAffiliate->public_code }}</strong>
                    <span>{{ $portalAffiliate->public_code }} · You are seeing this affiliate's workspace. Changes made here affect this affiliate.</span>
                </div>
                <form method="POST" action="{{ route('affiliate.impersonation.stop') }}">
                    @csrf
                    <button type="submit" class="stellar-btn stellar-btn-secondary stellar-btn-small">Exit affiliate view</button>
                </form>
            </div>
        </div>
    @endif

    <div class="stellar-shell">
        @include('partials.affiliate-sidebar')

        <main class="stellar-main">
            @if (session('status'))
                <div class="stellar-flash" role="status">
                    <span aria-hidden="true">✓</span>
                    <span>{{ session('status') }}</span>
                </div>
            @endif

            @if ($errors->any())
                <div class="stellar-flash is-error" role="alert">
                    <span aria-hidden="true">!</span>
                    <span>{{ $errors->first() }}</span>
                </div>
            @endif

            @if(!$portalIsAdmin && $portalAffiliate && $portalAffiliate->status !== 'active')
                <div class="stellar-flash is-warning" role="status">
                    <span aria-hidden="true">!</span>
                    <span>
                        @if($portalAffiliate->status === 'pending')
                            Your affiliate account is pending approval. Your tracking links activate when the account is approved.
                        @else
                            Your affiliate account is not active. <a href="mailto:{{ config('affiliate.support_email', 'info@stellarsecurity.com') }}">Contact support</a> if you need help.
                        @endif
                    </span>
                </div>
            @endif

            @yield('content')
        </main>
    </div>

    <footer class="stellar-footer">
        <div class="stellar-footer-inner">
            <span>© Stellar Security · Affiliate workspace.</span>
            <span>Need help? <a href="mailto:{{ config('affiliate.support_email', 'info@stellarsecurity.com') }}">{{ config('affiliate.support_email', 'info@stellarsecurity.com') }}</a></span>
        </div>
    </footer>
</div>

<div class="stellar-scrim" data-portal-scrim aria-hidden="true"></div>
@stack('scripts')
</body>
</html>
