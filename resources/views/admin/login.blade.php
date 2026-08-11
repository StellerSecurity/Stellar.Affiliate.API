<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="color-scheme" content="light">
    <title>Log in · Stellar Affiliate</title>
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

            <h2>Welcome back</h2>
            <p>Log in to your affiliate workspace.</p>

            @if ($errors->any())
                <div class="stellar-flash is-error" role="alert">{{ $errors->first() }}</div>
            @endif

            @if (session('status'))
                <div class="stellar-flash" role="status">{{ session('status') }}</div>
            @endif

            <form method="POST" action="{{ route('affiliate.login.post') }}" class="stellar-form-grid">
                @csrf

                <div class="stellar-field">
                    <label class="stellar-label" for="email">Email</label>
                    <input id="email" type="email" name="email" value="{{ old('email') }}" required autofocus autocomplete="email" class="stellar-input" placeholder="you@example.com">
                </div>

                <div class="stellar-field">
                    <label class="stellar-label" for="password">Password</label>
                    <input id="password" type="password" name="password" required maxlength="255" autocomplete="current-password" class="stellar-input" placeholder="Your password">
                </div>

                <label class="stellar-checkbox">
                    <input type="checkbox" name="remember" value="1">
                    Keep me signed in on this device
                </label>

                <button type="submit" class="stellar-btn stellar-btn-primary">Log in</button>
            </form>

            @if ((bool) config('affiliate.self_register_enabled', true))
                <div class="stellar-auth-alt">New affiliate? <a href="{{ route('affiliate.register') }}">Create your account</a></div>
            @endif
        </section>
    </main>
    <p class="stellar-auth-footer">© Stellar Security · Affiliate access · <a href="mailto:{{ config('affiliate.support_email', 'info@stellarsecurity.com') }}">Support</a></p>
</div>
    <script src="{{ asset('js/affiliate-portal.js') }}" defer></script>
</body>
</html>
