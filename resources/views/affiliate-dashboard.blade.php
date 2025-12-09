@extends('layouts.affiliate')

@section('title', 'Affiliate Dashboard · Stellar')

@section('content')
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
            Active tracking across Stellar VPN, Antivirus, Notes, and Secret.
        </p>
    </div>

    <div class="rounded-3xl bg-slate-900/70 border border-slate-800 p-4">
        <p class="text-[11px] font-medium text-slate-300">Clicks → Sales (30d)</p>
        <p class="mt-1 text-xl font-semibold">
            {{ $clicksLast30 }} → {{ $salesLast30 }}
        </p>
        <p class="mt-2 text-[10px] text-slate-400">
            @php
                $cr = $clicksLast30 > 0 ? round(($salesLast30 / max($clicksLast30,1)) * 100, 1) : 0;
            @endphp
            {{ $cr }}% conversion rate
        </p>
    </div>

    <div class="rounded-3xl bg-slate-900/70 border border-slate-800 p-4">
        <p class="text-[11px] font-medium text-slate-300">Active sessions</p>
        <p class="mt-1 text-xl font-semibold">{{ number_format($totalSessions) }}</p>
        <p class="mt-2 text-[10px] text-slate-400">Affiliate sessions with 180-day cookies.</p>
    </div>
</section>

<section class="grid gap-4 lg:grid-cols-[minmax(0,1.6fr)_minmax(0,1fr)]">
    <div class="rounded-3xl border border-slate-800 bg-slate-900/60 p-4">
        <div class="flex items-center justify-between gap-3">
            <div>
                <p class="text-[11px] font-semibold text-slate-200">Earnings (last 30 days)</p>
                <p class="mt-1 text-[10px] text-slate-400">Initial and recurring commissions.</p>
            </div>
            <span class="rounded-full border border-slate-700 bg-slate-950 px-3 py-1 text-[10px] text-slate-300">
                Chart placeholder
            </span>
        </div>
        <div class="mt-4 h-40 rounded-2xl bg-gradient-to-b from-slate-800/70 to-slate-950 border border-slate-800 flex items-center justify-center text-[10px] text-slate-500">
            Connect this block to a chart library later.
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
            <p class="text-[11px] font-semibold text-slate-200">System stats</p>
            <p class="mt-2 text-[10px] text-slate-400">
                {{ number_format($totalClicks) }} total clicks ·
                {{ number_format($totalSessions) }} sessions ·
                {{ number_format($totalAffiliates) }} affiliates.
            </p>
        </div>
    </div>
</section>

<section class="grid gap-4 lg:grid-cols-[minmax(0,1.1fr)_minmax(0,1.1fr)]">
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

    <div class="rounded-3xl border border-slate-800 bg-slate-900/60 p-4">
        <div class="flex items-center justify-between gap-3">
            <p class="text-[11px] font-semibold text-slate-200">Example affiliate link</p>
        </div>

        <p class="mt-1 text-[10px] text-slate-400">
            When logged in as a specific affiliate, you can show their personalized link here.
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

<section class="rounded-3xl border border-slate-800 bg-slate-900/70 p-4">
    <div class="flex flex-wrap items-center justify-between gap-3">
        <div>
            <p class="text-[11px] font-semibold text-slate-200">Recent sales (all affiliates)</p>
            <p class="mt-1 text-[10px] text-slate-400">Initial and recurring commissions.</p>
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
                    <td class="px-3 py-2 rounded-r-2xl text-emerald-400">
                        {{ ucfirst($sale->status ?? 'approved') }}
                    </td>
                </tr>
            @empty
                <tr>
                    <td colspan="6" class="px-3 py-4 text-center text-slate-500">
                        No sales yet.
                    </td>
                </tr>
            @endforelse
            </tbody>
        </table>
    </div>
</section>
@endsection
