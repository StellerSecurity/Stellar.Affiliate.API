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

<dialog class="stellar-export-dialog" data-export-dialog aria-labelledby="stellar-export-title">
    <form method="dialog" class="stellar-export-panel" data-export-form>
        <div class="stellar-export-head">
            <div>
                <p class="stellar-eyebrow">Export report</p>
                <h2 id="stellar-export-title" class="stellar-section-title" data-export-title>Choose a date range</h2>
                <p class="stellar-section-copy" data-export-copy>Leave both fields empty to export all time.</p>
            </div>
            <button type="button" class="stellar-icon-button" data-export-close data-no-loading aria-label="Close export dialog">×</button>
        </div>

        <div class="stellar-export-presets" aria-label="Quick date ranges">
            <button type="button" class="stellar-chip" data-export-preset="7" data-no-loading>Last 7 days</button>
            <button type="button" class="stellar-chip" data-export-preset="30" data-no-loading>Last 30 days</button>
            <button type="button" class="stellar-chip" data-export-preset="90" data-no-loading>Last 90 days</button>
            <button type="button" class="stellar-chip" data-export-preset="all" data-no-loading>All time</button>
        </div>

        <div class="stellar-export-grid">
            <div class="stellar-field">
                <label for="stellar-export-from" class="stellar-label">From</label>
                <input id="stellar-export-from" type="datetime-local" class="stellar-input" data-export-from>
            </div>
            <div class="stellar-field">
                <label for="stellar-export-to" class="stellar-label">To</label>
                <input id="stellar-export-to" type="datetime-local" class="stellar-input" data-export-to>
            </div>
        </div>

        <p class="stellar-export-note" data-export-note></p>

        <div class="stellar-actions stellar-export-actions">
            <button type="button" class="stellar-btn stellar-btn-secondary" data-export-close data-no-loading>Cancel</button>
            <button type="submit" class="stellar-btn stellar-btn-primary">Export CSV</button>
        </div>
    </form>
</dialog>

<div class="stellar-scrim" data-portal-scrim aria-hidden="true"></div>
@stack('scripts')
</body>
</html>
