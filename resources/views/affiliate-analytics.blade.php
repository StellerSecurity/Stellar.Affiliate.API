<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8" />
  <title>Stellar Affiliate Analytics</title>
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
          <p class="text-[11px] text-slate-400">Deep analytics on your Stellar affiliate performance.</p>
        </div>
      </div>

      <div class="flex items-center gap-4 text-[11px]">
        <div class="hidden md:flex items-center gap-2 rounded-full border border-slate-700 bg-slate-900 px-3 py-1.5">
          <span class="h-2 w-2 rounded-full bg-emerald-400"></span>
          <span class="text-slate-300">Tracking: 180-day cookie window</span>
        </div>
        <div class="flex items-center gap-2">
          <div class="text-right hidden sm:block">
            <p class="text-[11px] font-medium text-slate-200">affiliate@stellarvpn.org</p>
            <p class="text-[10px] text-slate-500">Analytics view</p>
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
        <a href="stellar-affiliate-portal.html" class="flex items-center gap-2 rounded-2xl px-3 py-2 text-[11px] font-medium text-slate-400 hover:bg-slate-900 hover:text-slate-100">
          <span>🏠</span>
          <span>Dashboard</span>
        </a>
        <button class="w-full flex items-center gap-2 rounded-2xl bg-slate-900 px-3 py-2 text-[11px] font-semibold text-slate-100">
          <span>📈</span>
          <span>Analytics</span>
        </button>
        <button class="w-full flex items-center gap-2 rounded-2xl px-3 py-2 text-[11px] font-medium text-slate-400 hover:bg-slate-900 hover:text-slate-100">
          <span>🧾</span>
          <span>Sales</span>
        </button>
        <button class="w-full flex items-center gap-2 rounded-2xl px-3 py-2 text-[11px] font-medium text-slate-400 hover:bg-slate-900 hover:text-slate-100">
          <span>💰</span>
          <span>Payouts</span>
        </button>

        <p class="mt-4 mb-2 text-[10px] font-semibold uppercase tracking-[0.12em] text-slate-500">Assets</p>
        <button class="w-full flex items-center gap-2 rounded-2xl px-3 py-2 text-[11px] font-medium text-slate-400 hover:bg-slate-900 hover:text-slate-100">
          <span>🎨</span>
          <span>Creatives</span>
        </button>
        <button class="w-full flex items-center gap-2 rounded-2xl px-3 py-2 text-[11px] font-medium text-slate-400 hover:bg-slate-900 hover:text-slate-100">
          <span>🔗</span>
          <span>Your link</span>
        </button>

        <p class="mt-4 mb-2 text-[10px] font-semibold uppercase tracking-[0.12em] text-slate-500">Account</p>
        <button class="w-full flex items-center gap-2 rounded-2xl px-3 py-2 text-[11px] font-medium text-slate-400 hover:bg-slate-900 hover:text-slate-100">
          <span>⚙️</span>
          <span>Settings</span>
        </button>
      </nav>
    </aside>

    <!-- Main content -->
    <main class="flex-1 space-y-4">
      <!-- Header + filters -->
      <section class="flex flex-wrap items-center justify-between gap-3">
        <div>
          <p class="text-[11px] font-semibold text-slate-200">Analytics</p>
          <p class="mt-1 text-[10px] text-slate-400">
            Understand how your traffic converts – and where to push harder.
          </p>
        </div>

        <div class="flex flex-wrap items-center gap-2 text-[10px]">
          <select class="rounded-full border border-slate-700 bg-slate-950 px-3 py-1 text-[10px] text-slate-300">
            <option>Last 30 days</option>
            <option>Last 90 days</option>
            <option>This year</option>
            <option>Custom range</option>
          </select>
          <select class="rounded-full border border-slate-700 bg-slate-950 px-3 py-1 text-[10px] text-slate-300">
            <option>All products</option>
            <option>Stellar VPN</option>
            <option>Stellar Antivirus</option>
            <option>Stellar Bundle</option>
          </select>
          <select class="rounded-full border border-slate-700 bg-slate-950 px-3 py-1 text-[10px] text-slate-300">
            <option>All sources</option>
            <option>YouTube</option>
            <option>TikTok</option>
            <option>Instagram</option>
            <option>Blog</option>
            <option>Ads</option>
          </select>
        </div>
      </section>

      <!-- KPIs row -->
      <section class="grid gap-3 md:grid-cols-4">
        <div class="rounded-3xl bg-gradient-to-br from-stellar-blue via-stellar-blueDark to-sky-500/90 p-4 shadow-glass border border-blue-500/60">
          <p class="text-[11px] font-medium text-blue-100">Total revenue generated</p>
          <p class="mt-1 text-2xl font-semibold text-white">€62,140</p>
          <p class="mt-2 text-[10px] text-blue-100/80">
            Combined value you brought to Stellar.
          </p>
        </div>
        <div class="rounded-3xl bg-slate-900/70 border border-slate-800 p-4">
          <p class="text-[11px] font-medium text-slate-300">Earnings (selected period)</p>
          <p class="mt-1 text-xl font-semibold">€2,140</p>
          <p class="mt-2 text-[10px] text-emerald-400 flex items-center gap-1">
            <span>▲</span><span>+18% vs previous period</span>
          </p>
        </div>
        <div class="rounded-3xl bg-slate-900/70 border border-slate-800 p-4">
          <p class="text-[11px] font-medium text-slate-300">Clicks → Signups</p>
          <p class="mt-1 text-xl font-semibold">8,420 → 392</p>
          <p class="mt-2 text-[10px] text-slate-400">4.7% signup rate</p>
        </div>
        <div class="rounded-3xl bg-slate-900/70 border border-slate-800 p-4">
          <p class="text-[11px] font-medium text-slate-300">Active recurring users</p>
          <p class="mt-1 text-xl font-semibold">351</p>
          <p class="mt-2 text-[10px] text-slate-400">60% recurring on each renewal</p>
        </div>
      </section>

      <!-- Charts row: Earnings + Conversion funnel -->
      <section class="grid gap-4 lg:grid-cols-[minmax(0,1.6fr)_minmax(0,1fr)]">
        <!-- Earnings chart -->
        <div class="rounded-3xl border border-slate-800 bg-slate-900/60 p-4">
          <div class="flex items-center justify-between gap-3">
            <div>
              <p class="text-[11px] font-semibold text-slate-200">Earnings over time</p>
              <p class="mt-1 text-[10px] text-slate-400">
                Visualize how your commissions grow each day.
              </p>
            </div>
            <select class="rounded-full border border-slate-700 bg-slate-950 px-3 py-1 text-[10px] text-slate-300">
              <option>Daily</option>
              <option>Weekly</option>
              <option>Monthly</option>
            </select>
          </div>
          <div class="mt-4 h-44 rounded-2xl bg-gradient-to-b from-slate-800/70 to-slate-950 border border-slate-800 flex items-center justify-center text-[10px] text-slate-500">
            (Line chart placeholder – plug into your chart library)
          </div>
        </div>

        <!-- Conversion funnel -->
        <div class="rounded-3xl border border-slate-800 bg-slate-900/60 p-4">
          <p class="text-[11px] font-semibold text-slate-200">Conversion funnel</p>
          <p class="mt-1 text-[10px] text-slate-400">
            From click to paid subscriber.
          </p>
          <div class="mt-4 space-y-3 text-[10px]">
            <div class="flex items-center gap-3">
              <div class="h-2 flex-1 rounded-full bg-slate-800">
                <div class="h-2 rounded-full bg-gradient-to-r from-stellar-blue to-sky-500" style="width: 100%;"></div>
              </div>
              <div class="w-24 text-right">
                <p class="font-semibold text-slate-100">Clicks</p>
                <p class="text-slate-400">8,420</p>
              </div>
            </div>
            <div class="flex items-center gap-3">
              <div class="h-2 flex-1 rounded-full bg-slate-800">
                <div class="h-2 rounded-full bg-gradient-to-r from-stellar-blue to-sky-500" style="width: 48%;"></div>
              </div>
              <div class="w-24 text-right">
                <p class="font-semibold text-slate-100">Landing views</p>
                <p class="text-slate-400">4,020</p>
              </div>
            </div>
            <div class="flex items-center gap-3">
              <div class="h-2 flex-1 rounded-full bg-slate-800">
                <div class="h-2 rounded-full bg-gradient-to-r from-stellar-blue to-sky-500" style="width: 26%;"></div>
              </div>
              <div class="w-24 text-right">
                <p class="font-semibold text-slate-100">Checkout</p>
                <p class="text-slate-400">2,180</p>
              </div>
            </div>
            <div class="flex items-center gap-3">
              <div class="h-2 flex-1 rounded-full bg-slate-800">
                <div class="h-2 rounded-full bg-gradient-to-r from-emerald-400 to-emerald-500" style="width: 10%;"></div>
              </div>
              <div class="w-24 text-right">
                <p class="font-semibold text-emerald-400">Paid</p>
                <p class="text-slate-400">842</p>
              </div>
            </div>
          </div>
        </div>
      </section>

      <!-- GEO + Device breakdown -->
      <section class="grid gap-4 lg:grid-cols-[minmax(0,1.1fr)_minmax(0,1.1fr)]">
        <!-- GEO -->
        <div class="rounded-3xl border border-slate-800 bg-slate-900/60 p-4">
          <div class="flex items-center justify-between gap-3">
            <p class="text-[11px] font-semibold text-slate-200">Top GEOs</p>
            <button class="text-[10px] text-slate-400 hover:text-slate-100">View all →</button>
          </div>
          <p class="mt-1 text-[10px] text-slate-400">
            Where your most valuable users are coming from.
          </p>

          <div class="mt-3 h-40 rounded-2xl bg-gradient-to-b from-slate-800/70 to-slate-950 border border-slate-800 flex items-center justify-center text-[10px] text-slate-500">
            (Map / GEO heatmap placeholder)
          </div>

          <div class="mt-3 grid gap-2 text-[10px] sm:grid-cols-3">
            <div class="rounded-2xl border border-slate-800 bg-slate-950/70 p-2">
              <p class="font-medium text-slate-200">Germany</p>
              <p class="mt-1 text-slate-400">32% of revenue</p>
            </div>
            <div class="rounded-2xl border border-slate-800 bg-slate-950/70 p-2">
              <p class="font-medium text-slate-200">Denmark</p>
              <p class="mt-1 text-slate-400">18% of revenue</p>
            </div>
            <div class="rounded-2xl border border-slate-800 bg-slate-950/70 p-2">
              <p class="font-medium text-slate-200">Switzerland</p>
              <p class="mt-1 text-slate-400">12% of revenue</p>
            </div>
          </div>
        </div>

        <!-- Device / source breakdown -->
        <div class="rounded-3xl border border-slate-800 bg-slate-900/60 p-4">
          <p class="text-[11px] font-semibold text-slate-200">Traffic breakdown</p>
          <p class="mt-1 text-[10px] text-slate-400">
            Understand which devices and sources perform best.
          </p>

          <div class="mt-3 grid gap-3 text-[10px] sm:grid-cols-2">
            <div class="rounded-2xl border border-slate-800 bg-slate-950/70 p-3">
              <p class="font-medium text-slate-200 mb-1">Devices</p>
              <div class="space-y-1.5">
                <div class="flex items-center justify-between">
                  <span class="text-slate-400">Mobile</span>
                  <span class="text-slate-100 font-medium">68%</span>
                </div>
                <div class="flex items-center justify-between">
                  <span class="text-slate-400">Desktop</span>
                  <span class="text-slate-100 font-medium">29%</span>
                </div>
                <div class="flex items-center justify-between">
                  <span class="text-slate-400">Tablet</span>
                  <span class="text-slate-100 font-medium">3%</span>
                </div>
              </div>
            </div>
            <div class="rounded-2xl border border-slate-800 bg-slate-950/70 p-3">
              <p class="font-medium text-slate-200 mb-1">Top sources</p>
              <div class="space-y-1.5">
                <div class="flex items-center justify-between">
                  <span class="text-slate-400">YouTube</span>
                  <span class="text-slate-100 font-medium">41%</span>
                </div>
                <div class="flex items-center justify-between">
                  <span class="text-slate-400">TikTok</span>
                  <span class="text-slate-100 font-medium">27%</span>
                </div>
                <div class="flex items-center justify-between">
                  <span class="text-slate-400">Instagram</span>
                  <span class="text-slate-100 font-medium">16%</span>
                </div>
                <div class="flex items-center justify-between">
                  <span class="text-slate-400">Blog / SEO</span>
                  <span class="text-slate-100 font-medium">9%</span>
                </div>
              </div>
            </div>
          </div>
        </div>
      </section>

      <!-- Top campaigns table -->
      <section class="rounded-3xl border border-slate-800 bg-slate-900/70 p-4">
        <div class="flex flex-wrap items-center justify-between gap-3">
          <div>
            <p class="text-[11px] font-semibold text-slate-200">Top campaigns</p>
            <p class="mt-1 text-[10px] text-slate-400">Compare how different links and campaigns perform.</p>
          </div>
          <button class="text-[10px] text-slate-400 hover:text-slate-100">
            Create new campaign →
          </button>
        </div>

        <div class="mt-3 overflow-x-auto text-[10px]">
          <table class="min-w-full border-separate border-spacing-y-1">
            <thead class="text-slate-400">
              <tr>
                <th class="px-3 py-1 text-left font-medium">Campaign</th>
                <th class="px-3 py-1 text-left font-medium">Source</th>
                <th class="px-3 py-1 text-right font-medium">Clicks</th>
                <th class="px-3 py-1 text-right font-medium">Signups</th>
                <th class="px-3 py-1 text-right font-medium">Conv. rate</th>
                <th class="px-3 py-1 text-right font-medium">Earnings</th>
              </tr>
            </thead>
            <tbody>
              <tr class="rounded-2xl bg-slate-950/70 text-slate-200">
                <td class="px-3 py-2 rounded-l-2xl">yt_review_nov</td>
                <td class="px-3 py-2">YouTube</td>
                <td class="px-3 py-2 text-right">3,120</td>
                <td class="px-3 py-2 text-right">182</td>
                <td class="px-3 py-2 text-right">5.8%</td>
                <td class="px-3 py-2 rounded-r-2xl text-right text-emerald-400">€980</td>
              </tr>
              <tr class="rounded-2xl bg-slate-950/60 text-slate-200">
                <td class="px-3 py-2 rounded-l-2xl">tt_short_clip</td>
                <td class="px-3 py-2">TikTok</td>
                <td class="px-3 py-2 text-right">1,980</td>
                <td class="px-3 py-2 text-right">96</td>
                <td class="px-3 py-2 text-right">4.8%</td>
                <td class="px-3 py-2 rounded-r-2xl text-right text-emerald-400">€610</td>
              </tr>
              <tr class="rounded-2xl bg-slate-950/60 text-slate-200">
                <td class="px-3 py-2 rounded-l-2xl">blog_comparison</td>
                <td class="px-3 py-2">Blog</td>
                <td class="px-3 py-2 text-right">920</td>
                <td class="px-3 py-2 text-right">44</td>
                <td class="px-3 py-2 text-right">4.7%</td>
                <td class="px-3 py-2 rounded-r-2xl text-right text-emerald-400">€280</td>
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
