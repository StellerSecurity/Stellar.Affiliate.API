@extends('layouts.affiliate')

@section('title', 'Affiliate Campaigns · Stellar')

@section('content')

    {{-- Heading + search --}}
    <section class="mb-4 flex flex-wrap items-center justify-between gap-3">
        <div>
            <h1 class="text-[13px] font-semibold text-slate-100">Campaigns</h1>
            <p class="text-[11px] text-slate-400">
                Create and manage affiliate campaigns, with copy-ready tracking links.
            </p>
        </div>

        <form method="GET" class="flex items-center gap-2 text-[10px]">
            <input
                type="text"
                name="q"
                value="{{ $search }}"
                placeholder="Search campaigns or affiliate codes..."
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

        {{-- Create campaign form --}}
        <div class="rounded-3xl border border-slate-800 bg-slate-900/80 p-4">
            <p class="text-[11px] font-semibold text-slate-200 mb-2">Create new campaign</p>
            <p class="text-[10px] text-slate-400 mb-3">
                Attach campaigns to affiliates for better analytics and source tracking.
            </p>

            <form method="POST" action="{{ route('affiliate.campaigns.store') }}" class="space-y-3 text-[11px]">
                @csrf

                <div class="space-y-1">
                    <label class="text-slate-300">Affiliate</label>
                    <select
                        name="affiliate_id"
                        class="w-full rounded-2xl bg-slate-950 border border-slate-700 px-3 py-2 text-[11px] text-slate-200 focus:ring-1 focus:ring-blue-500">
                        @foreach($affiliates as $aff)
                            <option value="{{ $aff->id }}">
                                {{ $aff->public_code }}
                            </option>
                        @endforeach
                    </select>
                    @error('affiliate_id')
                    <p class="text-[10px] text-red-400">{{ $message }}</p>
                    @enderror
                </div>

                <div class="space-y-1">
                    <label class="text-slate-300">Campaign name</label>
                    <input
                        type="text"
                        name="name"
                        value="{{ old('name') }}"
                        class="w-full rounded-2xl bg-slate-950 border border-slate-700 px-3 py-2 text-[11px] text-slate-200"
                        placeholder="e.g. yt_review_oct"
                        required
                    />
                    @error('name')
                    <p class="text-[10px] text-red-400">{{ $message }}</p>
                    @enderror
                </div>

                <div class="space-y-1">
                    <label class="text-slate-300">Source (optional)</label>
                    <input
                        type="text"
                        name="source"
                        value="{{ old('source') }}"
                        class="w-full rounded-2xl bg-slate-950 border border-slate-700 px-3 py-2 text-[11px] text-slate-200"
                        placeholder="youtube / instagram / blog"
                    />
                    @error('source')
                    <p class="text-[10px] text-red-400">{{ $message }}</p>
                    @enderror
                </div>

                <div class="space-y-1">
                    <label class="text-slate-300">Sub ID 1 (optional)</label>
                    <input
                        type="text"
                        name="sub_id1"
                        value="{{ old('sub_id1') }}"
                        class="w-full rounded-2xl bg-slate-950 border border-slate-700 px-3 py-2 text-[11px] text-slate-200"
                        placeholder="e.g. video01"
                    />
                    @error('sub_id1')
                    <p class="text-[10px] text-red-400">{{ $message }}</p>
                    @enderror
                </div>

                <div class="space-y-1">
                    <label class="text-slate-300">Sub ID 2 (optional)</label>
                    <input
                        type="text"
                        name="sub_id2"
                        value="{{ old('sub_id2') }}"
                        class="w-full rounded-2xl bg-slate-950 border border-slate-700 px-3 py-2 text-[11px] text-slate-200"
                        placeholder="e.g. influencerXYZ"
                    />
                    @error('sub_id2')
                    <p class="text-[10px] text-red-400">{{ $message }}</p>
                    @enderror
                </div>

                <button
                    type="submit"
                    class="w-full rounded-2xl bg-gradient-to-r from-blue-600 to-sky-500 px-4 py-2 text-[11px] font-semibold text-white shadow-lg shadow-blue-500/20">
                    Create campaign
                </button>
            </form>
        </div>

        {{-- Campaign table --}}
        <div class="rounded-3xl border border-slate-800 bg-slate-900/80 p-4">
            <div class="flex items-center justify-between mb-3">
                <p class="text-[11px] font-semibold text-slate-200">Existing campaigns</p>
                <p class="text-[10px] text-slate-500">
                    {{ $campaigns->total() }} total
                </p>
            </div>

            <div class="overflow-x-auto text-[10px]">
                <table class="min-w-full border-separate border-spacing-y-1">
                    <thead class="text-slate-400">
                    <tr>
                        <th class="px-3 py-1 text-left font-medium">Affiliate</th>
                        <th class="px-3 py-1 text-left font-medium">Name</th>
                        <th class="px-3 py-1 text-left font-medium">Link</th>
                        <th class="px-3 py-1 text-left font-medium">Source</th>
                        <th class="px-3 py-1 text-left font-medium">Sub1</th>
                        <th class="px-3 py-1 text-left font-medium">Sub2</th>
                        <th class="px-3 py-1 text-left font-medium">Created</th>
                    </tr>
                    </thead>
                    <tbody>
                    @forelse($campaigns as $camp)
                        @php
                            // Where this affiliate actually sends traffic after tracking
                            $redirectTarget = $camp->affiliate->base_redirect_url
                                ?: 'https://stellarvpn.org';

                              // Tracking domain (stellarafi.com or whatever you set)
                            $trackingBase = rtrim(config('affiliate.public_base_url', config('app.url')), '/');


                            $code = $camp->affiliate->public_code ?? '';

                            $query = [
                                'src'      => $camp->source,
                                'campaign' => $camp->name,
                                'sub1'     => $camp->sub_id1,
                                'sub2'     => $camp->sub_id2,
                                'product'  => 'DIGITAL Product',
                            ];

                            $filtered = array_filter($query, fn($v) => !is_null($v) && $v !== '');
                            $qs = http_build_query($filtered);
                            $fullUrl = $trackingBase . '/r/' . $code . ($qs ? ('?' . $qs) : '');
                        @endphp

                        <tr class="rounded-2xl bg-slate-950/70 text-slate-200 align-top">
                            <td class="px-3 py-2 rounded-l-2xl">
                                {{ $camp->affiliate->public_code ?? 'N/A' }}
                            </td>
                            <td class="px-3 py-2">
                                {{ $camp->name }}
                            </td>
                            <td class="px-3 py-2 max-w-xs">
                                <div class="space-y-1">
                                    <input
                                        type="text"
                                        readonly
                                        value="{{ $fullUrl }}"
                                        class="w-full truncate rounded-2xl bg-slate-950 border border-slate-700 px-2 py-1 text-[9px] text-slate-200 cursor-text"
                                    />
                                    <p class="text-[9px] text-slate-500">
                                        Redirects to:
                                        <span class="text-slate-300">{{ $redirectTarget }}</span>
                                    </p>
                                </div>
                            </td>
                            <td class="px-3 py-2">
                                {{ $camp->source ?? '-' }}
                            </td>
                            <td class="px-3 py-2">
                                {{ $camp->sub_id1 ?? '-' }}
                            </td>
                            <td class="px-3 py-2">
                                {{ $camp->sub_id2 ?? '-' }}
                            </td>
                            <td class="px-3 py-2 rounded-r-2xl text-slate-400">
                                {{ $camp->created_at }}
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="7" class="px-3 py-4 text-center text-slate-500">
                                No campaigns created yet.
                            </td>
                        </tr>
                    @endforelse
                    </tbody>
                </table>
            </div>

            @if(method_exists($campaigns, 'links'))
                <div class="mt-3">
                    {{ $campaigns->links('pagination::bootstrap-4') }}
                </div>
            @endif
        </div>
    </section>
@endsection
