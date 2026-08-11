<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="color-scheme" content="light">
    <title>Create account · Stellar Affiliate</title>
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
                        <span class="stellar-brand-subtitle">Guided affiliate setup</span>
                    </span>
                </a>

                <div>
                    <h1>From account to tracking link, <span>guided end to end.</span></h1>
                    <p>Create your login, then follow the guided steps to your first campaign and share-ready tracking link.</p>
                    <div class="stellar-auth-points">
                        <span class="stellar-auth-point">1 · Account</span>
                        <span class="stellar-auth-point">2 · Affiliate profile</span>
                        <span class="stellar-auth-point">3 · Campaign</span>
                        <span class="stellar-auth-point">4 · Copy link</span>
                    </div>
                </div>
            </div>
        </section>

        <section class="stellar-auth-form">
            <a href="{{ route('affiliate.login') }}" class="stellar-brand">
                <span class="stellar-brand-mark" aria-hidden="true"></span>
                <span class="stellar-wordmark">stellar<span class="accent">.</span></span>
            </a>

            <h2>Create your account</h2>
            <p>You will be taken straight into guided setup after registration.</p>

            @if ($errors->any())
                <div class="stellar-flash is-error" role="alert">{{ $errors->first() }}</div>
            @endif

            <form method="POST" action="{{ route('affiliate.register.post') }}" class="stellar-form-grid">
                @csrf

                <div class="stellar-field">
                    <label class="stellar-label" for="name">Name</label>
                    <input id="name" name="name" value="{{ old('name') }}" required autofocus autocomplete="name" maxlength="255" class="stellar-input" placeholder="Your name">
                </div>

                <div class="stellar-field">
                    <label class="stellar-label" for="email">Email</label>
                    <input id="email" type="email" name="email" value="{{ old('email') }}" required autocomplete="email" maxlength="255" class="stellar-input" placeholder="you@example.com">
                </div>

                <div class="stellar-form-row">
                    <div class="stellar-field">
                        <label class="stellar-label" for="password">Password</label>
                        <input id="password" type="password" name="password" required minlength="8" maxlength="255" autocomplete="new-password" class="stellar-input" placeholder="Create a password">
                    </div>
                    <div class="stellar-field">
                        <label class="stellar-label" for="password_confirmation">Confirm password</label>
                        <input id="password_confirmation" type="password" name="password_confirmation" required minlength="8" maxlength="255" autocomplete="new-password" class="stellar-input" placeholder="Repeat your password">
                    </div>
                </div>

                <button type="submit" class="stellar-btn stellar-btn-primary">Create account & continue</button>
            </form>

            <div class="stellar-auth-alt">Already have an account? <a href="{{ route('affiliate.login') }}">Log in</a></div>
        </section>
    </main>
    <p class="stellar-auth-footer">© Stellar Security · Affiliate access · <a href="mailto:{{ config('affiliate.support_email', 'info@stellarsecurity.com') }}">Support</a></p>
</div>
    <script src="{{ asset('js/affiliate-portal.js') }}" defer></script>
</body>
</html>
