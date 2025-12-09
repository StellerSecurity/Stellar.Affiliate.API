<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8" />
  <title>Stellar Affiliate Settings</title>
  <meta name="viewport" content="width=device-width, initial-scale=1" />

  <!-- Tailwind CDN -->
  <script src="https://cdn.tailwindcss.com"></script>
  <script>
    tailwind.config = {
      theme: {
        extend: {
          colors: {
            stellar: {
              blue: '#2563ff',
              blueDark: '#1b46d8',
              navy: '#020826'
            }
          },
          borderRadius: {
            '3xl': '1.75rem',
            '4xl': '2.25rem'
          },
          boxShadow: {
            'glass': '0 24px 70px rgba(15,23,42,0.45)'
          }
        }
      }
    }
  </script>

  <style>
    html { scroll-behavior: smooth; }
  </style>
</head>
<body class="bg-slate-950 text-slate-100 antialiased">
<div class="min-h-screen bg-slate-950">
  <!-- Top bar -->
  <header class="border-b border-slate-800 bg-slate-950/80 backdrop-blur">
    <div class="mx-auto flex max-w-6xl items-center justify-between px-4 py-3">
      <div class="flex items-center gap-3">
        <div class="flex h-9 w-9 items-center justify-center rounded-2xl bg-slate-900 text-white shadow-md shadow-blue-500/40">
          <svg class="h-5 w-5" viewBox="0 0 24 24" fill="none">
            <path d="M12 3L5 6v6c0 4.243 2.686 8.167 7 9 4.314-.833 7-4.757 7-9V6l-7-3z" class="fill-blue-500"></path>
            <path d="M10.25 12.75 11.5 14l2.5-3" stroke="white" stroke-width="1.6" stroke-linecap="round" stroke-linejoin="round"></path>
          </svg>
        </div>
        <div class="leading-tight">
          <div class="flex items-center gap-2">
            <span class="text-sm font-semibold tracking-tight">Stellar Affiliate Portal</span>
            <span class="inline-flex items-center gap-1 rounded-full bg-slate-900 px-2.5 py-0.5 text-[11px] font-medium text-slate-200 border border-slate-700">
              <span class="text-xs">🇨🇭</span>
              <span>Swiss Privacy</span>
            </span>
          </div>
          <p class="text-[11px] text-slate-400">Profile, security, payout preferences and legal information.</p>
        </div>
      </div>

      <div class="flex items-center gap-4 text-[11px]">
        <div class="hidden md:flex items-center gap-2 rounded-full border border-slate-700 bg-slate-900 px-3 py-1.5">
          <span class="h-2 w-2 rounded-full bg-emerald-400"></span>
          <span class="text-slate-300">Referral cookie: 180 days</span>
        </div>
        <div class="flex items-center gap-2">
          <div class="text-right hidden sm:block">
            <p class="text-[11px] font-medium text-slate-200">affiliate@stellarvpn.org</p>
            <p class="text-[10px] text-slate-500">Settings</p>
          </div>
          <button class="flex h-8 w-8 items-center justify-center rounded-full bg-slate-800 text-[11px] font-semibold">
            S
          </button>
        </div>
      </div>
    </div>
  </header>

  <div class="mx-auto flex max-w-6xl gap-4 px-4 py-4">
    <!-- Sidebar -->
    <aside class="hidden w-52 shrink-0 md:block">
      <nav class="space-y-1 text-[11px]">
        <p class="mb-2 text-[10px] font-semibold uppercase tracking-[0.12em] text-slate-500">Overview</p>
        <a href="stellar-affiliate-portal.html" class="flex items-center gap-2 rounded-2xl px-3 py-2 text-[11px] text-slate-400 hover:bg-slate-900 hover:text-slate-100 font-medium">
          <span>🏠</span>
          <span>Dashboard</span>
        </a>
        <a href="stellar-affiliate-analytics.html" class="flex items-center gap-2 rounded-2xl px-3 py-2 text-[11px] text-slate-400 hover:bg-slate-900 hover:text-slate-100 font-medium">
          <span>📈</span>
          <span>Analytics</span>
        </a>
        <a href="stellar-affiliate-sales.html" class="flex items-center gap-2 rounded-2xl px-3 py-2 text-[11px] text-slate-400 hover:bg-slate-900 hover:text-slate-100 font-medium">
          <span>🧾</span>
          <span>Sales</span>
        </a>
        <a href="stellar-affiliate-payouts.html" class="flex items-center gap-2 rounded-2xl px-3 py-2 text-[11px] text-slate-400 hover:bg-slate-900 hover:text-slate-100 font-medium">
          <span>💰</span>
          <span>Payouts</span>
        </a>

        <p class="mt-4 mb-2 text-[10px] font-semibold uppercase tracking-[0.12em] text-slate-500">Assets</p>
        <a href="stellar-affiliate-creatives.html" class="flex items-center gap-2 rounded-2xl px-3 py-2 text-[11px] font-medium text-slate-400 hover:bg-slate-900 hover:text-slate-100">
          <span>🎨</span>
          <span>Creatives</span>
        </a>
        <a href="stellar-affiliate-link.html" class="flex items-center gap-2 rounded-2xl px-3 py-2 text-[11px] font-medium text-slate-400 hover:bg-slate-900 hover:text-slate-100">
          <span>🔗</span>
          <span>Your link</span>
        </a>

        <p class="mt-4 mb-2 text-[10px] font-semibold uppercase tracking-[0.12em] text-slate-500">Account</p>
        <a href="stellar-affiliate-settings.html" class="flex items-center gap-2 rounded-2xl px-3 py-2 text-[11px] bg-slate-900 text-slate-100 font-semibold">
          <span>⚙️</span>
          <span>Settings</span>
        </a>
      </nav>
    </aside>

    <!-- Main content -->
    <main class="flex-1 space-y-4">

      <!-- Header -->
      <section class="flex flex-wrap items-center justify-between gap-3">
        <div>
          <p class="text-[11px] font-semibold text-slate-200">Settings</p>
          <p class="mt-1 text-[10px] text-slate-400">
            Manage your profile, security and payout details.
          </p>
        </div>
      </section>

      <!-- Layout -->
      <section class="grid gap-4 lg:grid-cols-[minmax(0,1.2fr)_minmax(0,1fr)]">
        <!-- Left: forms -->
        <div class="space-y-4 text-[10px]">
          <!-- Profile -->
          <div class="rounded-3xl border border-slate-800 bg-slate-900/70 p-4">
            <p class="text-[11px] font-semibold text-slate-200">Profile</p>
            <p class="mt-1 text-[10px] text-slate-400">Basic information used on invoices and payouts.</p>

            <div class="mt-3 grid gap-3 sm:grid-cols-2">
              <div>
                <label class="mb-1 block font-medium text-slate-200">Full name</label>
                <input type="text" value="Stellar Affiliate" class="w-full rounded-2xl border border-slate-800 bg-slate-950 px-3 py-2 text-[10px] text-slate-100" />
              </div>
              <div>
                <label class="mb-1 block font-medium text-slate-200">Email</label>
                <input type="email" value="affiliate@stellarvpn.org" class="w-full rounded-2xl border border-slate-800 bg-slate-950 px-3 py-2 text-[10px] text-slate-500" />
                <p class="mt-1 text-[10px] text-slate-500">Email is used for login and notifications.</p>
              </div>
            </div>

            <div class="mt-3 grid gap-3 sm:grid-cols-2">
              <div>
                <label class="mb-1 block font-medium text-slate-200">Country</label>
                <select class="w-full rounded-2xl border border-slate-800 bg-slate-950 px-3 py-2 text-[10px] text-slate-100">
                  <option>Switzerland</option>
                  <option>Denmark</option>
                  <option>Germany</option>
                  <option>EU (other)</option>
                  <option>Outside EU</option>
                </select>
              </div>
              <div>
                <label class="mb-1 block font-medium text-slate-200">Preferred currency</label>
                <select class="w-full rounded-2xl border border-slate-800 bg-slate-950 px-3 py-2 text-[10px] text-slate-100">
                  <option>EUR</option>
                  <option>CHF</option>
                  <option>USD</option>
                </select>
              </div>
            </div>

            <button class="mt-3 rounded-full bg-slate-100 px-4 py-1.5 text-[11px] font-semibold text-slate-900 hover:bg-white">
              Save profile
            </button>
          </div>

          <!-- Security -->
          <div class="rounded-3xl border border-slate-800 bg-slate-900/70 p-4">
            <p class="text-[11px] font-semibold text-slate-200">Security</p>
            <p class="mt-1 text-[10px] text-slate-400">
              Protect access to your Stellar affiliate account.
            </p>

            <div class="mt-3 grid gap-3 sm:grid-cols-2">
              <div>
                <label class="mb-1 block font-medium text-slate-200">Password</label>
                <input type="password" value="••••••••••" class="w-full rounded-2xl border border-slate-800 bg-slate-950 px-3 py-2 text-[10px] text-slate-100" />
                <p class="mt-1 text-[10px] text-slate-500">
                  Choose a strong, unique password.
                </p>
              </div>
              <div>
                <label class="mb-1 block font-medium text-slate-200">Two-factor authentication</label>
                <div class="flex items-center justify-between rounded-2xl border border-slate-800 bg-slate-950 px-3 py-2">
                  <div>
                    <p class="text-slate-200">App-based 2FA</p>
                    <p class="text-[10px] text-slate-500">Use an authenticator app for extra security.</p>
                  </div>
                  <button class="rounded-full bg-slate-100 px-3 py-1 text-[10px] font-semibold text-slate-900 hover:bg-white">
                    Enable
                  </button>
                </div>
              </div>
            </div>

            <div class="mt-3 rounded-2xl border border-slate-800 bg-slate-950/80 p-3">
              <p class="text-[11px] font-semibold text-slate-200">API key</p>
              <p class="mt-1 text-[10px] text-slate-400">
                Use this key to pull your stats programmatically (read-only).
              </p>
              <div class="mt-2 flex flex-col gap-2">
                <span class="truncate font-mono text-[10px] text-slate-100">
                  sk_live_aff_9f2e9c3d8b2f4a7c...
                </span>
                <div class="flex flex-wrap items-center gap-2">
                  <button class="rounded-full bg-slate-100 px-3 py-1 text-[10px] font-semibold text-slate-900 hover:bg-white">
                    Copy key
                  </button>
                  <button class="rounded-full border border-red-500/60 bg-red-500/10 px-3 py-1 text-[10px] font-semibold text-red-300 hover:bg-red-500/20">
                    Regenerate
                  </button>
                  <span class="text-[10px] text-slate-500">
                    Keep this private. Only share with your own systems.
                  </span>
                </div>
              </div>
            </div>
          </div>
        </div>

        <!-- Right: notifications & legal -->
        <div class="space-y-4 text-[10px]">
          <!-- Notifications -->
          <div class="rounded-3xl border border-slate-800 bg-slate-900/70 p-4">
            <p class="text-[11px] font-semibold text-slate-200">Email notifications</p>
            <p class="mt-1 text-[10px] text-slate-400">
              Choose what Stellar should email you about.
            </p>

            <div class="mt-3 space-y-2">
              <label class="flex items-start gap-2">
                <input type="checkbox" checked class="mt-0.5 h-3 w-3 border-slate-600 bg-slate-900 text-emerald-500" />
                <span>
                  <span class="block font-medium text-slate-200">New sale</span>
                  <span class="block text-slate-500">Get an email every time you make a sale.</span>
                </span>
              </label>
              <label class="flex items-start gap-2">
                <input type="checkbox" checked class="mt-0.5 h-3 w-3 border-slate-600 bg-slate-900 text-emerald-500" />
                <span>
                  <span class="block font-medium text-slate-200">Recurring renewal</span>
                  <span class="block text-slate-500">Notify when users renew and generate recurring commission.</span>
                </span>
              </label>
              <label class="flex items-start gap-2">
                <input type="checkbox" class="mt-0.5 h-3 w-3 border-slate-600 bg-slate-900 text-emerald-500" />
                <span>
                  <span class="block font-medium text-slate-200">Payout processed</span>
                  <span class="block text-slate-500">Get notified when a payout hits your bank or wallet.</span>
                </span>
              </label>
              <label class="flex items-start gap-2">
                <input type="checkbox" class="mt-0.5 h-3 w-3 border-slate-600 bg-slate-900 text-emerald-500" />
                <span>
                  <span class="block font-medium text-slate-200">Product & program updates</span>
                  <span class="block text-slate-500">Occasional updates on new features and offers.</span>
                </span>
              </label>
            </div>

            <button class="mt-3 rounded-full bg-slate-100 px-4 py-1.5 text-[11px] font-semibold text-slate-900 hover:bg-white">
              Save notification settings
            </button>
          </div>

          <!-- Legal / account -->
          <div class="rounded-3xl border border-slate-800 bg-slate-900/70 p-4">
            <p class="text-[11px] font-semibold text-slate-200">Account & legal</p>
            <p class="mt-1 text-[10px] text-slate-400">
              Tax-related info and account status.
            </p>

            <div class="mt-3 space-y-2">
              <div class="rounded-2xl border border-slate-800 bg-slate-950/80 p-3">
                <p class="font-medium text-slate-200">Tax information</p>
                <p class="mt-1 text-slate-500">
                  Depending on your country, Stellar may need basic tax details before higher payout limits.
                </p>
                <button class="mt-2 rounded-full border border-slate-700 bg-slate-950 px-3 py-1 text-[10px] text-slate-100 hover:bg-slate-900">
                  Complete tax profile
                </button>
              </div>

              <div class="rounded-2xl border border-red-500/40 bg-red-500/5 p-3">
                <p class="font-medium text-red-300">Danger zone</p>
                <p class="mt-1 text-slate-400">
                  If you close your account, all future recurring commissions will stop.
                </p>
                <button class="mt-2 rounded-full border border-red-500/60 bg-red-500/10 px-3 py-1 text-[10px] font-semibold text-red-200 hover:bg-red-500/20">
                  Close account
                </button>
              </div>
            </div>
          </div>
        </div>
      </section>

      <!-- Footer -->
      <footer class="py-4 text-[10px] text-slate-500">
        <div class="flex flex-wrap items-center justify-between gap-2 border-t border-slate-800 pt-3">
          <span>© Stellar Security · Swiss privacy-first ecosystem.</span>
          <span>Referral cookie window: <span class="font-semibold text-slate-200">180 days</span>.</span>
        </div>
      </footer>
    </main>
  </div>
</div>
</body>
</html>
