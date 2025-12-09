@extends('layouts.affiliate')

@section('title', 'Affiliate Payouts · Stellar')

@section('content')
    {{-- Top summary cards --}}
    <section class="grid gap-3 md:grid-cols-3 mb-4">
        <div class="rounded-3xl bg-gradient-to-br from-emerald-500 via-emerald-400 to-green-400 p-4 shadow-glass">
            <p class="text-[11px] font-medium text-emerald-100">Total paid out</p>
            <p class="mt-1 text-2xl font-semibold text-white">
                €{{ number_format($totalPaid, 2) }}
            </p>
            <p class="mt-2 text-[10px] text-emerald-50/90">
                All payouts with status "paid".
            </p>
        </div>

        <div class="rounded-3xl bg-slate-900/80 border border-slate-800 p-4">
            <p class="text-[11px] font-medium text-slate-300">Pending payouts</p>
            <p class="mt-1 text-2xl font-semibold text-amber-300">
                €{{ number_format($totalPending, 2) }}
            </p>
            <p class="mt-2 text-[10px] text-slate-400">
                Rows where status is "pending".
            </p>
        </div>

        <div class="rounded-3xl bg-slate-900/80 border border-slate-800 p-4">
            <p class="text-[11px] font-medium text-slate-300">Last paid batch</p>
            @if($lastPayout)
                <p class="mt-1 text-[13px] font-semibold text-slate-100">
                    {{ $lastPayout->created_at }}
                </p>
                <p class="mt-2 text-[10px] text-slate-400">
                    Last payout: €{{ number_format($lastPayout->amount, 2) }}
                    · {{ $lastPayout->affiliate->public_code ?? 'N/A' }}
                </p>
            @else
                <p class="mt-1 text-[13px] font-semibold text-slate-100">
                    No payouts yet.
                </p>
            @endif
        </div>
    </section>

    {{-- Filters --}}
    <section class="mb-3 flex flex-wrap items-center justify-between gap-3">
        <div>
            <p class="text-[11px] font-semibold text-slate-200">Payout history</p>
            <p class="text-[10px] text-slate-400">
                All affiliate payouts with status, method, and reference.
            </p>
        </div>

        <form method="GET" class="flex items-center gap-2 text-[10px]">
            <label class="text-slate-400">Status:</label>
            <select name="status"
                    onchange="this.form.submit()"
                    class="rounded-full border border-slate-700 bg-slate-900 px-3 py-1 text-[10px] text-slate-100 focus:outline-none focus:ring-1 focus:ring-blue-500">
                <option value="" {{ $currentStatusFilter ? '' : 'selected' }}>All</option>
                <option value="pending" {{ $currentStatusFilter === 'pending' ? 'selected' : '' }}>Pending</option>
                <option value="processing" {{ $currentStatusFilter === 'processing' ? 'selected' : '' }}>Processing</option>
                <option value="paid" {{ $currentStatusFilter === 'paid' ? 'selected' : '' }}>Paid</option>
                <option value="failed" {{ $currentStatusFilter === 'failed' ? 'selected' : '' }}>Failed</option>
            </select>
        </form>
    </section>

    {{-- Payout table --}}
    <section class="rounded-3xl border border-slate-800 bg-slate-900/70 p-4">
        <div class="overflow-x-auto text-[10px]">
            <table class="min-w-full border-separate border-spacing-y-1">
                <thead class="text-slate-400">
                <tr>
                    <th class="px-3 py-1 text-left font-medium">Date</th>
                    <th class="px-3 py-1 text-left font-medium">Affiliate</th>
                    <th class="px-3 py-1 text-left font-medium">Method</th>
                    <th class="px-3 py-1 text-left font-medium">Reference</th>
                    <th class="px-3 py-1 text-right font-medium">Amount</th>
                    <th class="px-3 py-1 text-left font-medium">Status</th>
                </tr>
                </thead>
                <tbody>
                @forelse($payouts as $payout)
                    <tr class="rounded-2xl bg-slate-950/70 text-slate-200">
                        <td class="px-3 py-2 rounded-l-2xl">
                            {{ $payout->created_at }}
                        </td>
                        <td class="px-3 py-2">
                            {{ $payout->affiliate->public_code ?? 'N/A' }}
                        </td>
                        <td class="px-3 py-2">
                            {{ $payout->method ?? 'Bank' }}
                        </td>
                        <td class="px-3 py-2">
                            {{ $payout->reference ?? '-' }}
                        </td>
                        <td class="px-3 py-2 text-right">
                            €{{ number_format($payout->amount, 2) }}
                        </td>
                        <td class="px-3 py-2 rounded-r-2xl">
                            @php
                                $statusColor = match($payout->status) {
                                    'paid' => 'text-emerald-400',
                                    'pending' => 'text-amber-300',
                                    'processing' => 'text-sky-300',
                                    'failed' => 'text-red-400',
                                    default => 'text-slate-300',
                                };
                            @endphp
                            <span class="{{ $statusColor }}">
                                {{ ucfirst($payout->status ?? 'unknown') }}
                            </span>
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="6" class="px-3 py-4 text-center text-slate-500">
                            No payouts found.
                        </td>
                    </tr>
                @endforelse
                </tbody>
            </table>
        </div>

        {{-- Pagination --}}
        @if(method_exists($payouts, 'links'))
            <div class="mt-3">
                {{ $payouts->links('pagination::bootstrap-4') }}
            </div>
        @endif
    </section>
@endsection
