<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8" />
    <title>Stellar Affiliate Portal</title>
    <meta name="viewport" content="width=device-width, initial-scale=1" />

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
                    <p class="text-[11px] text-slate-400">
                        Track all affiliates, clicks, and payouts in real time.
                    </p>
                </div>
            </div>

            <div class="flex items-center gap-4 text-[11px]">
                <div class="hidden md:flex items-center gap-2 rounded-full border border-slate-700 bg-slate-900 px-3 py-1.5">
                    <span class="h-2 w-2 rounded-full bg-emerald-400"></span>
                    <span class="text-slate-300">
              Next payout batch: in 14 days
          </span>
                </div>
                <div class="flex items-center gap-2">
                    <div class="text-right hidden sm:block">
                        <p class="text-[11px] font-medium text-slate-200">{{ auth()->user()->email ?? 'admin@stellar' }}</p>
                        <p class="text-[10px] text-slate-500">Affiliate admin</p>
                    </div>
                    <form method="POST" action="{{ route('affiliate.logout') }}">
                        @csrf
                        <button class="flex h-8 items-center justify-center rounded-full bg-slate-800 px-3 text-[10px]">
                            Logout
                        </button>
                    </form>
                </div>
            </div>
        </div>
    </header>

    <div class="mx-auto flex max-w-6xl gap-4 px-4 py-4">
        <!-- Sidebar -->
        <aside class="hidden w-52 shrink-0 md:block">
            <nav class="space-y-1 text-[11px]">
                <p class="mb-2 text-[10px] font-semibold uppercase tracking-[0.12em] text-slate-500">Overview</p>
                <a href="{{ route('affiliate.dashboard') }}" class="w-full flex items-center gap-2 rounded-2xl bg-slate-900 px-3 py-2 text-[11px] font-semibold text-slate-100">
                    <span>🏠</span>
                    <span>Dashboard</span>
                </a>
                <a href="{{ route('affiliate.analytics') }}" class="w-full flex items-center gap-2 rounded-2xl px-3 py-2 text-[11px] font-medium text-slate-400 hover:bg-slate-900 hover:text-slate-100">
                    <span>📈</span>
                    <span>Analytics</span>
                </a>
                <a href="{{ route('affiliate.sales') }}" class="w-full flex items-center gap-2 rounded-2xl px-3 py-2 text-[11px] font-medium text-slate-400 hover:bg-slate-900 hover:text-slate-100">
                    <span>🧾</span>
                    <span>Sales</span>
                </a>
                <a href="{{ route('affiliate.payouts') }}" class="w-full flex items-center gap-2 rounded-2xl px-3 py-2 text-[11px] font-medium text-slate-400 hover:bg-slate-900 hover:text-slate-100">
                    <span>💰</span>
                    <span>Payouts</span>
                </a>

                <p class="mt-4 mb-2 text-[10px] font-semibold uppercase tracking-[0.12em] text-slate-500">Account</p>
                <a href="{{ route('affiliate.settings') }}" class="w-full flex items-center gap-2 rounded-2xl px-3 py-2 text-[11px] font-medium text-slate-400 hover:bg-slate-900 hover:text-slate-100">
                    <span>⚙️</span>
                    <span>Settings</span>
                </a>
            </nav>
        </aside>

        <!-- Main content -->
        <main class="flex-1 space-y-4">
            <!-- Top metrics -->
            <section class="grid gap-3 md:grid-cols-4">
                <div class="rounded-3xl bg-gradient-to-br from-blue-600 via-blue-500 to-sky-400 p-4 shadow-glass">
                    <p class="text-[11px] font-medium text-blue-100">Total earnings (all affiliates)</p>
                    <p class="mt-1 text-2xl font-semibold text-white">
                        €{{ number_format($totalEarnings, 2) }}
                    </p>
                    <p class="mt-2 text-[10px] text-blue-100/80">
                        Based on all approved affiliate commissions.
                    </p>
                </div>
                <div class="rounded-3xl bg-gradient-to-br from-stellar-blue via-stellar-blueDark to-sky-500/90 p-4 border border-blue-500/50">
                    <p class="text-[11px] font-medium text-blue-100">Total affiliates</p>
                    <p class="mt-1 text-2xl font-semibold text-white">
                        {{ number_format($totalAffiliates) }}
                    </p>
                    <p class="mt-2 text-[10px] text-blue-100/80">
                        Active tracking across Stellar VPN, Antivirus, Notes, Secret.
                    </p>
                </div>
                <div class="rounded-3xl bg-slate-900/70 border border-slate-800 p-4">
                    <p class="text-[11px] font-medium text-slate-300">Clicks → Initial sales (30d)</p>
                    <p class="mt-1 text-xl font-semibold">
                        {{ $clicksLast30 }} → {{ $salesLast30 }}
                    </p>
                    <p class="mt-2 text-[10px] text-slate-400">
                        @php
                            $cr = $clicksLast30 > 0 ? round(($salesLast30 / max($clicksLast30,1)) * 100, 1) : 0;
                        @endphp
                        {{ $cr }}% signup rate
                    </p>
                </div>
                <div class="rounded-3xl bg-slate-900/70 border border-slate-800 p-4">
                    <p class="text-[11px] font-medium text-slate-300">Active sessions</p>
                    <p class="mt-1 text-xl font-semibold">{{ number_format($totalSessions) }}</p>
                    <p class="mt-2 text-[10px] text-slate-400">Affiliate sessions with up to 180 days cookie.</p>
                </div>
            </section>

            <!-- Earnings chart placeholder + payout summary -->
            <section class="grid gap-4 lg:grid-cols-[minmax(0,1.6fr)_minmax(0,1fr)]">
                <div class="rounded-3xl border border-slate-800 bg-slate-900/60 p-4">
                    <div class="flex items-center justify-between gap-3">
                        <div>
                            <p class="text-[11px] font-semibold text-slate-200">Earnings (last 30 days)</p>
                            <p class="mt-1 text-[10px] text-slate-400">Initial + recurring commissions.</p>
                        </div>
                        <span class="rounded-full border border-slate-700 bg-slate-950 px-3 py-1 text-[10px] text-slate-300">
              Static chart placeholder
            </span>
                    </div>
                    <div class="mt-4 h-40 rounded-2xl bg-gradient-to-b from-slate-800/70 to-slate-950 border border-slate-800 flex items-center justify-center text-[10px] text-slate-500">
                        (Hook this up to a chart library later – data already lives in AffiliateCommission.)
                    </div>
                </div>

                <div class="space-y-3">
                    <div class="rounded-3xl border border-slate-800 bg-slate-900/60 p-4">
                        <p class="text-[11px] font-semibold text-slate-200">Payout overview</p>
                        <p class="mt-1 text-xl font-semibold text-emerald-400">
                            Available: €{{ number_format($pendingPayouts, 2) }}
                        </p>
                        <p class="mt-1 text-[10px] text-slate-400">
                            Total paid out: €{{ number_format($paidPayouts, 2) }}
                        </p>
                    </div>

                    <div class="rounded-3xl border border-slate-800 bg-slate-900/60 p-4">
                        <p class="text-[11px] font-semibold text-slate-200">System status</p>
                        <p class="mt-2 text-[10px] text-slate-400">
                            {{ number_format($totalClicks) }} total clicks ·
                            {{ number_format($totalSessions) }} sessions ·
                            {{ number_format($totalAffiliates) }} affiliates.
                        </p>
                    </div>
                </div>
            </section>

            <!-- Recent payouts + main link info -->
            <section class="grid gap-4 lg:grid-cols-[minmax(0,1.1fr)_minmax(0,1.1fr)]">
                <!-- Payouts -->
                <div class="rounded-3xl border border-slate-800 bg-slate-900/60 p-4">
                    <div class="flex items-center justify-between gap-3">
                        <p class="text-[11px] font-semibold text-slate-200">Recent payouts</p>
                    </div>

                    <div class="mt-3 rounded-2xl border border-slate-800 bg-slate-950/70 p-3 text-[10px]">
                        @forelse($recentPayouts as $payout)
                            <div class="flex items-center justify-between py-1 border-b border-slate-800 last:border-0">
                                <div>
                                    <p class="text-slate-300">
                                        €{{ number_format($payout->amount, 2) }}
                                        <span class="text-slate-500">
                              · {{ $payout->affiliate->public_code ?? 'N/A' }}
                          </span>
                                    </p>
                                    <p class="text-[10px] text-slate-500">
                                        {{ $payout->created_at }} · {{ ucfirst($payout->status) }}
                                    </p>
                                </div>
                            </div>
                        @empty
                            <p class="text-slate-500">No payouts yet.</p>
                        @endforelse
                    </div>
                </div>

                <!-- Example: current affiliate link (if viewing as one affiliate later) -->
                <div class="rounded-3xl border border-slate-800 bg-slate-900/60 p-4">
                    <div class="flex items-center justify-between gap-3">
                        <p class="text-[11px] font-semibold text-slate-200">
                            Example affiliate link
                        </p>
                    </div>

                    <p class="mt-1 text-[10px] text-slate-400">
                        When logged in as a specific affiliate, you can show **their** link here.
                    </p>

                    <div class="mt-3 flex flex-col gap-2 rounded-2xl bg-slate-950/80 border border-slate-800 px-3 py-3 text-[10px]">
                        <div class="flex flex-col gap-2 sm:flex-row sm:items-center">
                            <div class="flex-1 truncate text-slate-100">
                                @if($currentAffiliate)
                                    https://stellarafi.com/r/{{ $currentAffiliate->public_code }}
                                @else
                                    https://stellarafi.com/r/AFFCODE
                                @endif
                            </div>
                        </div>
                        <p class="text-[10px] text-slate-500">
                            Cookie window: <span class="font-semibold text-slate-100">180 days</span> on every click.
                        </p>
                    </div>
                </div>
            </section>

            <!-- Recent sales table -->
            <section class="rounded-3xl border border-slate-800 bg-slate-900/70 p-4">
                <div class="flex flex-wrap items-center justify-between gap-3">
                    <div>
                        <p class="text-[11px] font-semibold text-slate-200">Recent sales (all affiliates)</p>
                        <p class="mt-1 text-[10px] text-slate-400">Initial + recurring commissions.</p>
                    </div>
                </div>

                <div class="mt-3 overflow-x-auto text-[10px]">
                    <table class="min-w-full border-separate border-spacing-y-1">
                        <thead class="text-slate-400">
                        <tr>
                            <th class="px-3 py-1 text-left font-medium">Date</th>
                            <th class="px-3 py-1 text-left font-medium">Affiliate</th>
                            <th class="px-3 py-1 text-left font-medium">Product</th>
                            <th class="px-3 py-1 text-right font-medium">Amount</th>
                            <th class="px-3 py-1 text-right font-medium">Commission</th>
                            <th class="px-3 py-1 text-left font-medium">Type</th>
                            <th class="px-3 py-1 text-left font-medium">Status</th>
                        </tr>
                        </thead>
                        <tbody>
                        @forelse($latestSales as $sale)
                            <tr class="rounded-2xl bg-slate-950/70 text-slate-200">
                                <td class="px-3 py-2 rounded-l-2xl">{{ $sale->created_at }}</td>
                                <td class="px-3 py-2">
                                    {{ $sale->affiliate->public_code ?? 'N/A' }}
                                </td>
                                <td class="px-3 py-2">
                                    {{ $sale->product ?? 'VPN' }}
                                </td>
                                <td class="px-3 py-2 text-right">
                                    €{{ number_format($sale->order_amount ?? $sale->amount, 2) }}
                                </td>
                                <td class="px-3 py-2 text-right text-emerald-400">
                                    €{{ number_format($sale->amount, 2) }}
                                </td>
                                <td class="px-3 py-2">
                                    {{ $sale->is_initial ? 'Initial' : 'Recurring' }}
                                </td>
                                <td class="px-3 py-2 rounded-r-2xl text-emerald-400">
                                    {{ ucfirst($sale->status ?? 'approved') }}
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="7" class="px-3 py-4 text-center text-slate-500">
                                    No sales yet.
                                </td>
                            </tr>
                        @endforelse
                        </tbody>
                    </table>
                </div>
            </section>

            <footer class="py-4 text-[10px] text-slate-500">
                <div class="flex flex-wrap items-center justify-between gap-2 border-t border-slate-800 pt-3">
                    <span>© Stellar Security · Swiss privacy-first ecosystem.</span>
                    <span>Referral cookie: <span class="font-semibold text-slate-200">180 days</span>.</span>
                </div>
            </footer>
        </main>
    </div>
</div>
</body>
</html>
