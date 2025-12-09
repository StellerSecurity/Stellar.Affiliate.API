@extends('layouts.affiliate')

@section('title', 'Affiliate Analytics · Stellar')

@section('content')
    {{-- Top metric row --}}
    <section class="grid gap-3 md:grid-cols-4 mb-4">
        {{-- Clicks --}}
        <div class="rounded-3xl bg-gradient-to-br from-blue-600 via-blue-500 to-sky-400 p-4 shadow-glass">
            <p class="text-[11px] font-medium text-blue-100/90">Clicks (last 30 days)</p>
            <p class="mt-1 text-2xl font-semibold text-white">
                {{ number_format($clicksLast30) }}
            </p>
            <p class="mt-2 text-[10px] text-blue-50/80">
                Every tracked hit on any affiliate link.
            </p>
        </div>

        {{-- Sessions --}}
        <div class="rounded-3xl bg-slate-900/90 border border-slate-800 p-4">
            <p class="text-[11px] font-medium text-slate-300">Sessions (last 30 days)</p>
            <p class="mt-1 text-2xl font-semibold text-slate-100">
                {{ number_format($sessionsLast30) }}
            </p>
            <p class="mt-2 text-[10px] text-slate-400">
                180-day cookies created from those clicks.
            </p>
        </div>

        {{-- Sales --}}
        <div class="rounded-3xl bg-slate-900/90 border border-slate-800 p-4">
            <p class="text-[11px] font-medium text-slate-300">Sales (last 30 days)</p>
            <div class="mt-1 flex items-baseline justify-between gap-2">
                <p class="text-2xl font-semibold text-emerald-400">
                    {{ number_format($salesLast30) }}
                </p>
                <span class="inline-flex items-center gap-1 rounded-full bg-emerald-500/10 px-2 py-0.5 text-[10px] text-emerald-300 border border-emerald-500/30">
                    <span class="h-1.5 w-1.5 rounded-full bg-emerald-400"></span>
                    High intent
                </span>
            </div>
            <p class="mt-2 text-[10px] text-slate-400">
                Completed orders with commission attached.
            </p>
        </div>

        {{-- CR + EPC --}}
        <div class="rounded-3xl bg-slate-900/90 border border-slate-800 p-4">
            <p class="text-[11px] font-medium text-slate-300">Performance (30 days)</p>
            <div class="mt-2 flex flex-col gap-1.5 text-[10px]">
                <div class="flex items-center justify-between">
                    <span class="text-slate-400">Conversion rate</span>
                    <span class="font-semibold text-slate-100">{{ $conversionRate }}%</span>
                </div>
                <div class="flex items-center justify-between">
                    <span class="text-slate-400">Revenue</span>
                    <span class="font-semibold text-slate-100">
                        €{{ number_format($revenueLast30, 2) }}
                    </span>
                </div>
                <div class="flex items-center justify-between">
                    <span class="text-slate-400">EPC (per click)</span>
                    <span class="font-semibold text-slate-100">
                        €{{ number_format($epc, 4) }}
                    </span>
                </div>
            </div>
        </div>
    </section>

    {{-- Middle row: last 7 days + summary pills --}}
    <section class="grid gap-4 lg:grid-cols-[minmax(0,1.5fr)_minmax(0,1fr)] mb-4">
        {{-- Last 7 days breakdown --}}
        <div class="rounded-3xl border border-slate-800 bg-slate-900/70 p-4">
            <div class="flex items-center justify-between gap-3 mb-3">
                <div>
                    <p class="text-[11px] font-semibold text-slate-200">Last 7 days – trend</p>
                    <p class="text-[10px] text-slate-400">
                        Quick view of how traffic turns into sales.
                    </p>
                </div>
                <span class="rounded-full border border-slate-700 bg-slate-950 px-3 py-1 text-[10px] text-slate-300">
                    Chart-ready data
                </span>
            </div>

            <div class="space-y-1.5 text-[10px]">
                @forelse($daily as $row)
                    @php
                        $clicks = $row['clicks'];
                        $sales  = $row['sales'];
                        $crDay  = $clicks > 0 ? round(($sales / max($clicks,1)) * 100, 1) : 0;
                        $maxBar = max($clicks, $sales, 1);
                        $clickWidth = ($clicks / $maxBar) * 100;
                        $saleWidth  = ($sales / $maxBar) * 100;
                    @endphp
                    <div class="flex items-center gap-3 rounded-2xl bg-slate-950/80 border border-slate-900 px-3 py-2">
                        <div class="w-20 shrink-0 text-slate-400">
                            {{ \Illuminate\Support\Carbon::parse($row['day'])->format('d M') }}
                        </div>
                        <div class="flex-1 space-y-1">
                            <div class="flex items-center justify-between">
                                <span class="text-slate-400">Clicks</span>
                                <span class="font-medium text-slate-100">{{ $clicks }}</span>
                            </div>
                            <div class="h-1.5 rounded-full bg-slate-800">
                                <div class="h-1.5 rounded-full bg-gradient-to-r from-blue-500 to-sky-400" style="width: {{ $clickWidth }}%;"></div>
                            </div>
                            <div class="flex items-center justify-between">
                                <span class="text-slate-400">Sales</span>
                                <span class="font-medium text-emerald-300">{{ $sales }}</span>
                            </div>
                            <div class="h-1.5 rounded-full bg-slate-800">
                                <div class="h-1.5 rounded-full bg-gradient-to-r from-emerald-400 to-emerald-500" style="width: {{ $saleWidth }}%;"></div>
                            </div>
                        </div>
                        <div class="w-16 shrink-0 text-right">
                            <span class="inline-flex items-center justify-center rounded-full bg-slate-900 px-2 py-1 text-[9px] text-slate-200 border border-slate-700">
                                {{ $crDay }}%
                            </span>
                        </div>
                    </div>
                @empty
                    <p class="text-center text-slate-500 py-4">
                        No data for the last 7 days yet.
                    </p>
                @endforelse
            </div>
        </div>

        {{-- Performance pills --}}
        <div class="space-y-3">
            <div class="rounded-3xl border border-slate-800 bg-slate-900/70 p-4">
                <p class="text-[11px] font-semibold text-slate-200">Funnel quality</p>
                <div class="mt-3 space-y-2">
                    <div class="flex items-center justify-between text-[10px]">
                        <span class="text-slate-400">Clicks → sessions</span>
                        <span class="font-medium text-slate-100">
                            {{ $clicksLast30 > 0 ? round(($sessionsLast30 / max($clicksLast30,1)) * 100, 1) : 0 }}%
                        </span>
                    </div>
                    <div class="flex items-center justify-between text-[10px]">
                        <span class="text-slate-400">Sessions → sales</span>
                        <span class="font-medium text-slate-100">
                            {{ $sessionsLast30 > 0 ? round(($salesLast30 / max($sessionsLast30,1)) * 100, 1) : 0 }}%
                        </span>
                    </div>
                </div>
            </div>

            <div class="rounded-3xl border border-slate-800 bg-slate-900/70 p-4">
                <p class="text-[11px] font-semibold text-slate-200">Affiliate promise</p>
                <ul class="mt-2 space-y-1.5 text-[10px] text-slate-300">
                    <li>• 100% commission on first order.</li>
                    <li>• 60% recurring on renewals.</li>
                    <li>• 180-day tracking cookie window.</li>
                    <li>• Swiss company, strict privacy laws.</li>
                </ul>
            </div>
        </div>
    </section>

    {{-- Top affiliates --}}
    <section class="rounded-3xl border border-slate-800 bg-slate-900/70 p-4">
        <div class="flex items-center justify-between gap-3 mb-3">
            <div>
                <p class="text-[11px] font-semibold text-slate-200">Top affiliates (last 30 days)</p>
                <p class="text-[10px] text-slate-400">
                    Sorted by total commission earned.
                </p>
            </div>
        </div>

        <div class="overflow-x-auto text-[10px]">
            <table class="min-w-full border-separate border-spacing-y-1">
                <thead class="text-slate-400">
                <tr>
                    <th class="px-3 py-1 text-left font-medium">Affiliate</th>
                    <th class="px-3 py-1 text-right font-medium">Sales</th>
                    <th class="px-3 py-1 text-right font-medium">Commission</th>
                    <th class="px-3 py-1 text-right font-medium">Avg per sale</th>
                </tr>
                </thead>
                <tbody>
                @forelse($topAffiliates as $row)
                    @php
                        $avgPerSale = $row->sales_count > 0
                            ? $row->total_commission / max($row->sales_count, 1)
                            : 0;
                    @endphp
                    <tr class="rounded-2xl bg-slate-950/70 text-slate-200">
                        <td class="px-3 py-2 rounded-l-2xl">
                            <div class="flex items-center gap-2">
                                <div class="flex h-6 w-6 items-center justify-center rounded-full bg-slate-800 text-[10px]">
                                    {{ strtoupper(substr($row->affiliate->public_code ?? 'AF', 0, 2)) }}
                                </div>
                                <div class="leading-none">
                                    <p class="font-medium">
                                        {{ $row->affiliate->public_code ?? 'N/A' }}
                                    </p>
                                    <p class="mt-0.5 text-[9px] text-slate-500">
                                        ID: {{ $row->affiliate->id ?? '-' }}
                                    </p>
                                </div>
                            </div>
                        </td>
                        <td class="px-3 py-2 text-right">
                            {{ $row->sales_count }}
                        </td>
                        <td class="px-3 py-2 text-right text-emerald-400">
                            €{{ number_format($row->total_commission, 2) }}
                        </td>
                        <td class="px-3 py-2 text-right rounded-r-2xl">
                            €{{ number_format($avgPerSale, 2) }}
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="4" class="px-3 py-4 text-center text-slate-500">
                            No affiliate sales in the last 30 days yet.
                        </td>
                    </tr>
                @endforelse
                </tbody>
            </table>
        </div>
    </section>
@endsection
