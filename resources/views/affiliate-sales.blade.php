<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8" />
  <title>Stellar Affiliate Sales</title>
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
          <p class="text-[11px] text-slate-400">Detailed view of all sales generated through your Stellar affiliate links.</p>
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
            <p class="text-[10px] text-slate-500">Sales overview</p>
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
        <a href="stellar-affiliate-sales.html" class="flex items-center gap-2 rounded-2xl px-3 py-2 text-[11px] bg-slate-900 text-slate-100 font-semibold">
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
        <a href="stellar-affiliate-settings.html" class="flex items-center gap-2 rounded-2xl px-3 py-2 text-[11px] text-slate-400 hover:bg-slate-900 hover:text-slate-100 font-medium">
          <span>⚙️</span>
          <span>Settings</span>
        </a>
      </nav>
    </aside>

    <!-- Main content -->
    <main class="flex-1 space-y-4">

      <!-- Header + filters -->
      <section class="flex flex-wrap items-center justify-between gap-3">
        <div>
          <p class="text-[11px] font-semibold text-slate-200">Sales</p>
          <p class="mt-1 text-[10px] text-slate-400">
            See every initial and recurring sale driven by your affiliate links.
          </p>
        </div>

        <div class="flex flex-wrap items-center gap-2 text-[10px]">
          <select class="rounded-full border border-slate-700 bg-slate-950 px-3 py-1 text-slate-300">
            <option>All products</option>
            <option>Stellar VPN</option>
            <option>Stellar Antivirus</option>
            <option>Stellar Bundle</option>
          </select>
          <select class="rounded-full border border-slate-700 bg-slate-950 px-3 py-1 text-slate-300">
            <option>All types</option>
            <option>Initial</option>
            <option>Recurring</option>
            <option>Refunded</option>
          </select>
          <select class="rounded-full border border-slate-700 bg-slate-950 px-3 py-1 text-slate-300">
            <option>Last 30 days</option>
            <option>Last 90 days</option>
            <option>This year</option>
          </select>
        </div>
      </section>

      <!-- KPIs -->
      <section class="grid gap-3 md:grid-cols-4">
        <div class="rounded-3xl bg-gradient-to-br from-stellar-blue via-stellar-blueDark to-sky-500/90 p-4 shadow-glass border border-blue-500/60">
          <p class="text-[11px] font-medium text-blue-100">Total sales (selected)</p>
          <p class="mt-1 text-2xl font-semibold text-white">€4,320</p>
          <p class="mt-2 text-[10px] text-blue-100/80">Initial + recurring combined.</p>
        </div>
        <div class="rounded-3xl bg-slate-900/70 border border-slate-800 p-4">
          <p class="text-[11px] font-medium text-slate-300">Initial sales</p>
          <p class="mt-1 text-xl font-semibold">€2,580</p>
          <p class="mt-2 text-[10px] text-slate-400">Paid at 100% commission.</p>
        </div>
        <div class="rounded-3xl bg-slate-900/70 border border-slate-800 p-4">
          <p class="text-[11px] font-medium text-slate-300">Recurring sales</p>
          <p class="mt-1 text-xl font-semibold">€1,740</p>
          <p class="mt-2 text-[10px] text-slate-400">You earn 60% on each renewal.</p>
        </div>
        <div class="rounded-3xl bg-slate-900/70 border border-slate-800 p-4">
          <p class="text-[11px] font-medium text-slate-300">Refund rate</p>
          <p class="mt-1 text-xl font-semibold">1.2%</p>
          <p class="mt-2 text-[10px] text-slate-400">Well below industry average.</p>
        </div>
      </section>

      <!-- Sales table -->
      <section class="rounded-3xl border border-slate-800 bg-slate-900/70 p-4">
        <div class="flex flex-wrap items-center justify-between gap-3">
          <div>
            <p class="text-[11px] font-semibold text-slate-200">Recent sales</p>
            <p class="mt-1 text-[10px] text-slate-400">Initial + recurring invoices created from your referrals.</p>
          </div>
          <div class="flex flex-wrap items-center gap-2 text-[10px]">
            <input type="text" placeholder="Search customer ID or email" class="w-48 rounded-full border border-slate-700 bg-slate-950 px-3 py-1 text-[10px] text-slate-200 placeholder:text-slate-500" />
            <select class="rounded-full border border-slate-700 bg-slate-950 px-3 py-1 text-slate-300">
              <option>Newest first</option>
              <option>Oldest first</option>
              <option>Highest value</option>
            </select>
          </div>
        </div>

        <div class="mt-3 overflow-x-auto text-[10px]">
          <table class="min-w-full border-separate border-spacing-y-1">
            <thead class="text-slate-400">
              <tr>
                <th class="px-3 py-1 text-left font-medium">Date</th>
                <th class="px-3 py-1 text-left font-medium">Product</th>
                <th class="px-3 py-1 text-left font-medium">Plan</th>
                <th class="px-3 py-1 text-left font-medium">Customer</th>
                <th class="px-3 py-1 text-right font-medium">Amount</th>
                <th class="px-3 py-1 text-right font-medium">Your commission</th>
                <th class="px-3 py-1 text-left font-medium">Type</th>
                <th class="px-3 py-1 text-left font-medium">Status</th>
              </tr>
            </thead>
            <tbody>
              <tr class="rounded-2xl bg-slate-950/70 text-slate-200">
                <td class="px-3 py-2 rounded-l-2xl">2025-12-08</td>
                <td class="px-3 py-2">Stellar VPN</td>
                <td class="px-3 py-2">Annual</td>
                <td class="px-3 py-2">#49301</td>
                <td class="px-3 py-2 text-right">€59.99</td>
                <td class="px-3 py-2 text-right text-emerald-400">€59.99</td>
                <td class="px-3 py-2">Initial</td>
                <td class="px-3 py-2 rounded-r-2xl text-emerald-400">Approved</td>
              </tr>
              <tr class="rounded-2xl bg-slate-950/60 text-slate-200">
                <td class="px-3 py-2 rounded-l-2xl">2025-12-08</td>
                <td class="px-3 py-2">Stellar Bundle</td>
                <td class="px-3 py-2">Monthly</td>
                <td class="px-3 py-2">#49288</td>
                <td class="px-3 py-2 text-right">€9.99</td>
                <td class="px-3 py-2 text-right text-emerald-400">€5.99</td>
                <td class="px-3 py-2">Recurring</td>
                <td class="px-3 py-2 rounded-r-2xl text-emerald-400">Approved</td>
              </tr>
              <tr class="rounded-2xl bg-slate-950/60 text-slate-200">
                <td class="px-3 py-2 rounded-l-2xl">2025-12-07</td>
                <td class="px-3 py-2">Stellar Antivirus</td>
                <td class="px-3 py-2">Monthly</td>
                <td class="px-3 py-2">#49270</td>
                <td class="px-3 py-2 text-right">€6.99</td>
                <td class="px-3 py-2 text-right text-emerald-400">€4.19</td>
                <td class="px-3 py-2">Recurring</td>
                <td class="px-3 py-2 rounded-r-2xl text-emerald-400">Approved</td>
              </tr>
              <tr class="rounded-2xl bg-slate-950/50 text-slate-200">
                <td class="px-3 py-2 rounded-l-2xl">2025-12-07</td>
                <td class="px-3 py-2">Stellar VPN</td>
                <td class="px-3 py-2">Monthly</td>
                <td class="px-3 py-2">#49240</td>
                <td class="px-3 py-2 text-right">€4.99</td>
                <td class="px-3 py-2 text-right text-emerald-400">€2.99</td>
                <td class="px-3 py-2">Recurring</td>
                <td class="px-3 py-2 rounded-r-2xl text-amber-400">Pending</td>
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
