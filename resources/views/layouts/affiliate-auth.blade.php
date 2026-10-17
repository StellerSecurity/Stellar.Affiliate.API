<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="color-scheme" content="light">
    <meta name="referrer" content="no-referrer">
    <title>@yield('title') · Stellar Affiliate</title>
    <link rel="stylesheet" href="{{ asset('css/stellar-tailwind.css') }}">
    <link rel="stylesheet" href="{{ asset('css/stellar-affiliate.css') }}">
</head>
<body class="stellar-auth-body">
<div>
    <main class="stellar-auth-shell">
        <section class="stellar-auth-story">
            <div class="stellar-auth-story-inner">
                <a href="{{ route('affiliate.login') }}" class="stellar-brand">
                    <span class="stellar-brand-mark" aria-hidden="true"></span>
                    <span class="stellar-brand-copy">
                        <span class="stellar-wordmark">stellar<span class="accent">.</span> affiliate</span>
                        <span class="stellar-brand-subtitle">Private affiliate workspace</span>
                    </span>
                </a>

                <div>
                    <h1>Affiliate growth, <span>clearly tracked.</span></h1>
                    <p>Campaigns, conversions with Order IDs, commissions and payouts in one clear workspace.</p>
                    <div class="stellar-auth-points">
                        <span class="stellar-auth-point">180-day referral window</span>
                        <span class="stellar-auth-point">Order ID visibility</span>
                        <span class="stellar-auth-point">Swiss-built ecosystem</span>
                    </div>
                </div>
            </div>
        </section>

        <section class="stellar-auth-form">
            <a href="{{ route('affiliate.login') }}" class="stellar-brand">
                <span class="stellar-brand-mark" aria-hidden="true"></span>
                <span class="stellar-wordmark">stellar<span class="accent">.</span></span>
            </a>

            <h2>@yield('title')</h2>
            <p>@yield('description')</p>

            @if ($errors->any())
                <div class="stellar-flash is-error" role="alert">{{ $errors->first() }}</div>
            @endif

            @if (session('status'))
                <div class="stellar-flash" role="status">{{ session('status') }}</div>
            @endif

            @yield('form')
        </section>
    </main>
    <p class="stellar-auth-footer">© Stellar Security · Affiliate access · <a href="mailto:{{ config('affiliate.support_email', 'info@stellarsecurity.com') }}">Support</a></p>
</div>
</body>
</html>
