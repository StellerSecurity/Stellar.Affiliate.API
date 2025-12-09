<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8" />
  <title>Stellar Affiliate Payouts</title>
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
          <p class="text-[11px] text-slate-400">Balance, payout schedule and full payout history in one place.</p>
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
            <p class="text-[10px] text-slate-500">Payout center</p>
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
        <a href="stellar-affiliate-payouts.html" class="flex items-center gap-2 rounded-2xl px-3 py-2 text-[11px] bg-slate-900 text-slate-100 font-semibold">
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
        <a href="stellar-affiliate-settings.html" class="flex items-center gap-2 rounded-2xl px-3 py-2 text-[11px] text-slate-400 hover:bg-slate-900 hover:text-slate-100 font-medium">
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
          <p class="text-[11px] font-semibold text-slate-200">Payouts</p>
          <p class="mt-1 text-[10px] text-slate-400">
            See what you've been paid, what's pending, and configure how you get paid.
          </p>
        </div>
        <button class="rounded-full bg-slate-100 px-4 py-1.5 text-[11px] font-semibold text-slate-900 hover:bg-white">
          Request manual payout
        </button>
      </section>

      <!-- Balance summary -->
      <section class="grid gap-3 md:grid-cols-4">
        <div class="rounded-3xl bg-gradient-to-br from-emerald-500 via-emerald-400 to-teal-400 p-4 shadow-glass border border-emerald-300/70">
          <p class="text-[11px] font-medium text-emerald-50">Available balance</p>
          <p class="mt-1 text-2xl font-semibold text-white">€820</p>
          <p class="mt-2 text-[10px] text-emerald-50/90">
            Can be included in the next payout cycle.
          </p>
        </div>
        <div class="rounded-3xl bg-slate-900/70 border border-slate-800 p-4">
          <p class="text-[11px] font-medium text-slate-300">Next scheduled payout</p>
          <p class="mt-1 text-xl font-semibold">€1,120</p>
          <p class="mt-2 text-[10px] text-slate-400">In 14 days · Automatic</p>
        </div>
        <div class="rounded-3xl bg-slate-900/70 border border-slate-800 p-4">
          <p class="text-[11px] font-medium text-slate-300">Total paid out</p>
          <p class="mt-1 text-xl font-semibold">€11,610</p>
          <p class="mt-2 text-[10px] text-slate-400">Across all time.</p>
        </div>
        <div class="rounded-3xl bg-slate-900/70 border border-slate-800 p-4">
          <p class="text-[11px] font-medium text-slate-300">Payout method</p>
          <p class="mt-1 text-sm font-semibold text-slate-100">Bank transfer (EUR)</p>
          <p class="mt-1 text-[10px] text-slate-400">IBAN ending in ···· 4821</p>
        </div>
      </section>

      <!-- Payout methods + schedule -->
      <section class="grid gap-4 lg:grid-cols-[minmax(0,1.2fr)_minmax(0,1fr)]">
        <!-- Methods -->
        <div class="rounded-3xl border border-slate-800 bg-slate-900/70 p-4">
          <div class="flex items-center justify-between gap-3">
            <p class="text-[11px] font-semibold text-slate-200">Payout methods</p>
            <button class="text-[10px] text-slate-400 hover:text-slate-100">Edit methods →</button>
          </div>

          <div class="mt-3 grid gap-3 text-[10px] sm:grid-cols-2">
            <div class="rounded-2xl border border-slate-800 bg-slate-950/70 p-3">
              <p class="font-medium text-slate-200 mb-1">Bank transfer</p>
              <p class="text-slate-400">Primary · EUR</p>
              <p class="mt-1 text-slate-500">IBAN: DE•••• 4821</p>
              <span class="mt-2 inline-flex rounded-full bg-emerald-500/15 px-2 py-0.5 text-[10px] font-medium text-emerald-300 border border-emerald-500/40">
                Default payout method
              </span>
            </div>
            <div class="rounded-2xl border border-slate-800 bg-slate-950/70 p-3">
              <p class="font-medium text-slate-200 mb-1">Crypto wallet</p>
              <p class="text-slate-400">USDT · TRC20</p>
              <p class="mt-1 text-slate-500">Wallet ending ····9aF2</p>
              <span class="mt-2 inline-flex rounded-full bg-slate-800 px-2 py-0.5 text-[10px] font-medium text-slate-300 border border-slate-700">
                Optional · Manual payouts only
              </span>
            </div>
          </div>
        </div>

        <!-- Schedule -->
        <div class="rounded-3xl border border-slate-800 bg-slate-900/70 p-4">
          <p class="text-[11px] font-semibold text-slate-200">Payout schedule</p>
          <p class="mt-1 text-[10px] text-slate-400">
            Configure how often Stellar should send you money automatically.
          </p>

          <div class="mt-3 space-y-2 text-[10px]">
            <label class="flex items-center justify-between rounded-2xl border border-slate-800 bg-slate-950/70 px-3 py-2">
              <div>
                <p class="font-medium text-slate-200">Every 14 days (default)</p>
                <p class="text-slate-400">Recommended for most affiliates.</p>
              </div>
              <input type="radio" name="payout_schedule" checked class="h-4 w-4 border-slate-600 bg-slate-900 text-emerald-500" />
            </label>
            <label class="flex items-center justify-between rounded-2xl border border-slate-800 bg-slate-950/70 px-3 py-2">
              <div>
                <p class="font-medium text-slate-200">Every 30 days</p>
                <p class="text-slate-400">Fewer transfers, larger amounts.</p>
              </div>
              <input type="radio" name="payout_schedule" class="h-4 w-4 border-slate-600 bg-slate-900 text-emerald-500" />
            </label>
            <label class="flex items-center justify-between rounded-2xl border border-slate-800 bg-slate-950/70 px-3 py-2">
              <div>
                <p class="font-medium text-slate-200">Manual only</p>
                <p class="text-slate-400">You decide when to trigger payouts.</p>
              </div>
              <input type="radio" name="payout_schedule" class="h-4 w-4 border-slate-600 bg-slate-900 text-emerald-500" />
            </label>
          </div>
        </div>
      </section>

      <!-- Payout history -->
      <section class="rounded-3xl border border-slate-800 bg-slate-900/70 p-4">
        <div class="flex flex-wrap items-center justify-between gap-3">
          <div>
            <p class="text-[11px] font-semibold text-slate-200">Payout history</p>
            <p class="mt-1 text-[10px] text-slate-400">All payouts sent to you by Stellar.</p>
          </div>
          <div class="flex flex-wrap items-center gap-2 text-[10px]">
            <select class="rounded-full border border-slate-700 bg-slate-950 px-3 py-1 text-slate-300">
              <option>Last 12 months</option>
              <option>All time</option>
            </select>
          </div>
        </div>

        <div class="mt-3 overflow-x-auto text-[10px]">
          <table class="min-w-full border-separate border-spacing-y-1">
            <thead class="text-slate-400">
              <tr>
                <th class="px-3 py-1 text-left font-medium">Date</th>
                <th class="px-3 py-1 text-left font-medium">Method</th>
                <th class="px-3 py-1 text-right font-medium">Amount</th>
                <th class="px-3 py-1 text-left font-medium">Status</th>
                <th class="px-3 py-1 text-left font-medium">Reference</th>
              </tr>
            </thead>
            <tbody>
              <tr class="rounded-2xl bg-slate-950/70 text-slate-200">
                <td class="px-3 py-2 rounded-l-2xl">2025-11-30</td>
                <td class="px-3 py-2">Bank transfer (EUR)</td>
                <td class="px-3 py-2 text-right">€1,020</td>
                <td class="px-3 py-2 text-emerald-400">Paid</td>
                <td class="px-3 py-2 rounded-r-2xl text-slate-400">PAYOUT-2025-11-30-1020</td>
              </tr>
              <tr class="rounded-2xl bg-slate-950/60 text-slate-200">
                <td class="px-3 py-2 rounded-l-2xl">2025-11-16</td>
                <td class="px-3 py-2">Bank transfer (EUR)</td>
                <td class="px-3 py-2 text-right">€860</td>
                <td class="px-3 py-2 text-emerald-400">Paid</td>
                <td class="px-3 py-2 rounded-r-2xl text-slate-400">PAYOUT-2025-11-16-860</td>
              </tr>
              <tr class="rounded-2xl bg-slate-950/60 text-slate-200">
                <td class="px-3 py-2 rounded-l-2xl">2025-11-02</td>
                <td class="px-3 py-2">USDT (TRC20)</td>
                <td class="px-3 py-2 text-right">€740</td>
                <td class="px-3 py-2 text-emerald-400">Paid</td>
                <td class="px-3 py-2 rounded-r-2xl text-slate-400">PAYOUT-2025-11-02-740</td>
              </tr>
            </tbody>
          </table>
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
