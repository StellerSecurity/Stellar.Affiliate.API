@extends('layouts.affiliate')

@section('title', 'Affiliates · Stellar')

@section('content')
    {{-- Heading + search --}}
    <section class="mb-4 flex flex-wrap items-center justify-between gap-3">
        <div>
            <h1 class="text-[13px] font-semibold text-slate-100">Affiliates</h1>
            <p class="text-[11px] text-slate-400">
                Create and manage affiliates, public codes, and default redirect URLs.
            </p>
        </div>

        <form method="GET" class="flex items-center gap-2 text-[10px]">
            <input
                type="text"
                name="q"
                value="{{ $search }}"
                placeholder="Search by name, email or code..."
                class="rounded-full border border-slate-700 bg-slate-900 px-3 py-1.5 text-[10px] text-slate-100 focus:outline-none focus:ring-1 focus:ring-blue-500"
            />
            <button
                type="submit"
                class="rounded-full bg-slate-800 px-3 py-1.5 text-[10px] text-slate-100 border border-slate-700">
                Search
            </button>
        </form>
    </section>

    @if(session('status'))
        <div class="mb-4 rounded-2xl border border-emerald-500/40 bg-emerald-500/10 px-3 py-2 text-[10px] text-emerald-200">
            {{ session('status') }}
        </div>
    @endif

    <section class="grid gap-4 lg:grid-cols-[minmax(0,0.9fr)_minmax(0,1.3fr)]">
        {{-- Create affiliate form --}}
        <div class="rounded-3xl border border-slate-800 bg-slate-900/80 p-4">
            <p class="text-[11px] font-semibold text-slate-200 mb-2">Create new affiliate</p>
            <p class="text-[10px] text-slate-400 mb-3">
                Generate a public tracking code and optional default redirect URL.
            </p>

            <form method="POST" action="{{ route('affiliate.affiliates.store') }}" class="space-y-3 text-[11px]">
                @csrf

                <div class="space-y-1">
                    <label class="text-slate-300">Name</label>
                    <input
                        type="text"
                        name="name"
                        value="{{ old('name') }}"
                        class="w-full rounded-2xl bg-slate-950 border border-slate-700 px-3 py-2 text-[11px] text-slate-200 focus:outline-none focus:ring-1 focus:ring-blue-500"
                        required
                    />
                    @error('name')
                    <p class="text-[10px] text-red-400">{{ $message }}</p>
                    @enderror
                </div>

                <div class="space-y-1">
                    <label class="text-slate-300">Email (optional, used for linking)</label>
                    <input
                        type="email"
                        name="email"
                        value="{{ old('email') }}"
                        placeholder="publisher@example.com"
                        class="w-full rounded-2xl bg-slate-950 border border-slate-700 px-3 py-2 text-[11px] text-slate-200 focus:outline-none focus:ring-1 focus:ring-blue-500"
                    />
                    @error('email')
                    <p class="text-[10px] text-red-400">{{ $message }}</p>
                    @enderror
                </div>

                <div class="space-y-1">
                    <label class="text-slate-300">Public code (optional)</label>
                    <input
                        type="text"
                        name="public_code"
                        value="{{ old('public_code') }}"
                        placeholder="e.g. YT_BRO123 (auto-generated if empty)"
                        class="w-full rounded-2xl bg-slate-950 border border-slate-700 px-3 py-2 text-[11px] text-slate-200 focus:outline-none focus:ring-1 focus:ring-blue-500"
                    />
                    @error('public_code')
                    <p class="text-[10px] text-red-400">{{ $message }}</p>
                    @enderror
                </div>

                <div class="space-y-1">
                    <label class="text-slate-300">Default redirect URL (optional)</label>
                    <input
                        type="text"
                        name="base_redirect_url"
                        value="{{ old('base_redirect_url') }}"
                        placeholder="https://..."
                        class="w-full rounded-2xl bg-slate-950 border border-slate-700 px-3 py-2 text-[11px] text-slate-200 focus:outline-none focus:ring-1 focus:ring-blue-500"
                    />
                    @error('base_redirect_url')
                    <p class="text-[10px] text-red-400">{{ $message }}</p>
                    @enderror
                </div>

                <div class="flex items-center justify-between mt-2">
                    <label class="inline-flex items-center gap-2 text-[11px] text-slate-300">
                        <input
                            type="checkbox"
                            name="is_active"
                            value="1"
                            class="h-3 w-3 rounded border-slate-600 bg-slate-900 text-blue-500"
                            {{ old('is_active', '1') ? 'checked' : '' }}
                        />
                        Active affiliate
                    </label>
                </div>

                <div class="pt-2">
                    <button
                        type="submit"
                        class="w-full rounded-2xl bg-gradient-to-r from-blue-600 to-sky-500 px-4 py-2 text-[11px] font-semibold text-white shadow-lg shadow-blue-500/20 hover:opacity-90 transition">
                        Create affiliate
                    </button>
                </div>
            </form>
        </div>

        {{-- Affiliates table --}}
        <div class="rounded-3xl border border-slate-800 bg-slate-900/80 p-4">
            <div class="flex items-center justify-between mb-3">
                <p class="text-[11px] font-semibold text-slate-200">Existing affiliates</p>
                <p class="text-[10px] text-slate-500">
                    {{ $affiliates->total() }} total
                </p>
            </div>

            <div class="overflow-x-auto text-[10px]">
                <table class="min-w-full border-separate border-spacing-y-1">
                    <thead class="text-slate-400">
                    <tr>
                        <th class="px-3 py-1 text-left font-medium">Affiliate</th>
                        <th class="px-3 py-1 text-left font-medium">Code</th>
                        <th class="px-3 py-1 text-left font-medium">Default redirect</th>
                        <th class="px-3 py-1 text-left font-medium">Active</th>
                        <th class="px-3 py-1 text-left font-medium">Created</th>
                    </tr>
                    </thead>
                    <tbody>
                    @forelse($affiliates as $affiliate)
                        <tr class="rounded-2xl bg-slate-950/70 text-slate-200">
                            <td class="px-3 py-2 rounded-l-2xl">
                                <div class="flex items-center gap-2">
                                    <div class="flex h-6 w-6 items-center justify-center rounded-full bg-slate-800 text-[10px]">
                                        {{ strtoupper(substr($affiliate->name ?? $affiliate->public_code ?? 'AF', 0, 2)) }}
                                    </div>
                                    <div class="leading-none">
                                        <p class="font-medium">
                                            {{ $affiliate->name ?? 'Unknown' }}
                                        </p>
                                        <p class="text-[10px] text-slate-400">{{ $affiliate->email ?? '' }}</p>
                                    </div>
                                </div>
                            </td>
                            <td class="px-3 py-2">
                                <span class="inline-flex items-center rounded-full bg-slate-900 px-2 py-1 border border-slate-700">
                                    {{ $affiliate->public_code }}
                                </span>
                            </td>
                            <td class="px-3 py-2 max-w-xs truncate">
                                {{ $affiliate->base_redirect_url ?? 'Uses global default' }}
                            </td>
                            <td class="px-3 py-2">
                                @if(($affiliate->status ?? 'active') === 'active')
                                    <span class="inline-flex items-center gap-1 rounded-full bg-emerald-500/10 px-2 py-0.5 text-emerald-300 border border-emerald-500/40">
                                        <span class="h-1.5 w-1.5 rounded-full bg-emerald-400"></span>
                                        Active
                                    </span>
                                @else
                                    <span class="inline-flex items-center gap-1 rounded-full bg-slate-800 px-2 py-0.5 text-slate-300 border border-slate-700">
                                        Inactive
                                    </span>
                                @endif
                            </td>
                            <td class="px-3 py-2 rounded-r-2xl text-slate-400">
                                {{ $affiliate->created_at }}
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="5" class="px-3 py-4 text-center text-slate-500">
                                No affiliates yet. Create the first one on the left.
                            </td>
                        </tr>
                    @endforelse
                    </tbody>
                </table>
            </div>

            @if(method_exists($affiliates, 'links'))
                <div class="mt-3">
                    {{ $affiliates->links('pagination::bootstrap-4') }}
                </div>
            @endif
        </div>
    </section>
@endsection
