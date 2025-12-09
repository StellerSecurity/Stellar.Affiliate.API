@extends('layouts.affiliate')

@section('title', 'Affiliate Sales · Stellar')

@section('content')
    {{-- Summary cards --}}
    <section class="grid gap-3 md:grid-cols-4 mb-4">
        <div class="rounded-3xl bg-gradient-to-br from-blue-600 via-blue-500 to-sky-400 p-4 shadow-glass">
            <p class="text-[11px] font-medium text-blue-100">Total commission (all time)</p>
            <p class="mt-1 text-2xl font-semibold text-white">
                €{{ number_format($totalCommission, 2) }}
            </p>
            <p class="mt-2 text-[10px] text-blue-100/90">
                Sum of all affiliate commissions.
            </p>
        </div>

        <div class="rounded-3xl bg-slate-900/80 border border-slate-800 p-4">
            <p class="text-[11px] font-medium text-slate-300">Total sales (all time)</p>
            <p class="mt-1 text-2xl font-semibold text-slate-100">
                {{ number_format($totalSalesCount) }}
            </p>
            <p class="mt-2 text-[10px] text-slate-400">
                Number of commission rows recorded.
            </p>
        </div>

        <div class="rounded-3xl bg-slate-900/80 border border-slate-800 p-4">
            <p class="text-[11px] font-medium text-slate-300">Average commission</p>
            <p class="mt-1 text-2xl font-semibold text-emerald-400">
                €{{ number_format($avgCommission, 2) }}
            </p>
            <p class="mt-2 text-[10px] text-slate-400">
                Total commission / total sales.
            </p>
        </div>

        <div class="rounded-3xl bg-slate-900/80 border border-slate-800 p-4">
            <p class="text-[11px] font-medium text-slate-300">Last 30 days</p>
            <p class="mt-1 text-[13px] font-semibold text-slate-100">
                €{{ number_format($last30Commission, 2) }} · {{ number_format($last30Count) }} sales
            </p>
            <p class="mt-2 text-[10px] text-slate-400">
                Commissions created in the last 30 days.
            </p>
        </div>
    </section>

    {{-- Filters --}}
    <section class="mb-3 flex flex-wrap items-center justify-between gap-3">
        <div>
            <p class="text-[11px] font-semibold text-slate-200">Sales and commissions</p>
            <p class="text-[10px] text-slate-400">
                Filter by status, product, or affiliate code.
            </p>
        </div>

        <form method="GET" class="flex flex-wrap items-center gap-2 text-[10px]">
            <div class="flex items-center gap-1">
                <label class="text-slate-400">Status:</label>
                <select name="status"
                        onchange="this.form.submit()"
                        class="rounded-full border border-slate-700 bg-slate-900 px-3 py-1 text-[10px] text-slate-100 focus:outline-none focus:ring-1 focus:ring-blue-500">
                    <option value="" {{ $currentStatusFilter ? '' : 'selected' }}>All</option>
                    <option value="pending" {{ $currentStatusFilter === 'pending' ? 'selected' : '' }}>Pending</option>
                    <option value="approved" {{ $currentStatusFilter === 'approved' ? 'selected' : '' }}>Approved</option>
                    <option value="paid" {{ $currentStatusFilter === 'paid' ? 'selected' : '' }}>Paid</option>
                    <option value="rejected" {{ $currentStatusFilter === 'rejected' ? 'selected' : '' }}>Rejected</option>
                </select>
            </div>

            <div class="flex items-center gap-1">
                <label class="text-slate-400">Product:</label>
                <input type="text"
                       name="product"
                       value="{{ $currentProductFilter }}"
                       placeholder="vpn / antivirus / notes"
                       class="rounded-full border border-slate-700 bg-slate-900 px-3 py-1 text-[10px] text-slate-100 focus:outline-none focus:ring-1 focus:ring-blue-500">
            </div>

            <div class="flex items-center gap-1">
                <label class="text-slate-400">Affiliate code:</label>
                <input type="text"
                       name="affiliate"
                       value="{{ $currentAffiliateCode }}"
                       placeholder="AFF123"
                       class="rounded-full border border-slate-700 bg-slate-900 px-3 py-1 text-[10px] text-slate-100 focus:outline-none focus:ring-1 focus:ring-blue-500">
            </div>

            <button type="submit"
                    class="rounded-full border border-slate-700 bg-slate-800 px-3 py-1 text-[10px] text-slate-100">
                Apply
            </button>
        </form>
    </section>

    {{-- Sales table --}}
    <section class="rounded-3xl border border-slate-800 bg-slate-900/70 p-4">
        <div class="overflow-x-auto text-[10px]">
            <table class="min-w-full border-separate border-spacing-y-1">
                <thead class="text-slate-400">
                <tr>
                    <th class="px-3 py-1 text-left font-medium">Date</th>
                    <th class="px-3 py-1 text-left font-medium">Affiliate</th>
                    <th class="px-3 py-1 text-left font-medium">Product</th>
                    <th class="px-3 py-1 text-left font-medium">Order ID</th>
                    <th class="px-3 py-1 text-right font-medium">Order amount</th>
                    <th class="px-3 py-1 text-right font-medium">Commission</th>
                    <th class="px-3 py-1 text-left font-medium">Status</th>
                </tr>
                </thead>
                <tbody>
                @forelse($sales as $sale)
                    @php
                        $orderAmount = $sale->order_amount ?? $sale->amount;
                        $statusColor = match($sale->status) {
                            'approved' => 'text-emerald-400',
                            'paid' => 'text-emerald-400',
                            'pending' => 'text-amber-300',
                            'rejected' => 'text-red-400',
                            default => 'text-slate-300',
                        };
                    @endphp
                    <tr class="rounded-2xl bg-slate-950/70 text-slate-200">
                        <td class="px-3 py-2 rounded-l-2xl">
                            {{ $sale->created_at }}
                        </td>
                        <td class="px-3 py-2">
                            {{ $sale->affiliate->public_code ?? 'N/A' }}
                        </td>
                        <td class="px-3 py-2">
                            {{ $sale->product ?? 'vpn' }}
                        </td>
                        <td class="px-3 py-2">
                            {{ $sale->order_id ?? '-' }}
                        </td>
                        <td class="px-3 py-2 text-right">
                            €{{ number_format($orderAmount, 2) }}
                        </td>
                        <td class="px-3 py-2 text-right text-emerald-400">
                            €{{ number_format($sale->amount, 2) }}
                        </td>
                        <td class="px-3 py-2 rounded-r-2xl">
                            <span class="{{ $statusColor }}">
                                {{ ucfirst($sale->status ?? 'unknown') }}
                            </span>
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="7" class="px-3 py-4 text-center text-slate-500">
                            No sales found.
                        </td>
                    </tr>
                @endforelse
                </tbody>
            </table>
        </div>

        {{-- Pagination --}}
        @if(method_exists($sales, 'links'))
            <div class="mt-3">
                {{ $sales->links('pagination::bootstrap-4') }}
            </div>
        @endif
    </section>
@endsection
