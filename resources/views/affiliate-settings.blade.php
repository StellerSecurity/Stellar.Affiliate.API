@extends('layouts.affiliate')

@section('title', 'Affiliate Settings · Stellar')

@section('content')

    {{-- Page heading --}}
    <section class="mb-4">
        <h1 class="text-[13px] font-semibold text-slate-100">Affiliate Settings</h1>
        <p class="text-[11px] text-slate-400">
            Configure how tracking behaves, default landing URLs, and upcoming admin features.
        </p>
    </section>

    <section class="rounded-3xl border border-slate-800 bg-slate-900/70 p-6 space-y-6">

        {{-- Default redirect URL --}}
        <div class="space-y-2">
            <p class="text-[11px] font-semibold text-slate-200">Default redirect URL</p>
            <p class="text-[10px] text-slate-400 mb-2">
                This is where users land after clicking an affiliate link, unless a custom link is set by the affiliate.
            </p>

            <form method="POST" action="#">
                @csrf
                <div class="flex flex-col sm:flex-row items-start sm:items-center gap-3">
                    <input
                        type="text"
                        name="default_redirect"
                        value="{{ $defaultRedirect ?? 'https://stellarvpn.org/' }}"
                        class="w-full sm:w-96 rounded-2xl bg-slate-950 border border-slate-700 px-3 py-2 text-[11px] text-slate-200 focus:outline-none focus:ring-1 focus:ring-blue-500"
                    />

                    <button
                        type="submit"
                        class="rounded-2xl bg-gradient-to-r from-blue-600 to-sky-500 px-4 py-2 text-[11px] font-semibold text-white shadow-lg shadow-blue-500/20 hover:opacity-90 transition">
                        Save
                    </button>
                </div>
            </form>
        </div>

        <hr class="border-slate-800 my-6" />

        {{-- Commission Structure --}}
        <div class="space-y-2">
            <p class="text-[11px] font-semibold text-slate-200">Commission structure</p>
            <p class="text-[10px] text-slate-400 mb-2">
                A summary of how affiliates get paid.
            </p>

            <div class="grid md:grid-cols-3 gap-3">

                <div class="rounded-2xl bg-slate-950/70 border border-slate-800 p-4">
                    <p class="text-[11px] font-semibold text-blue-400">Initial commission</p>
                    <p class="text-[22px] font-bold text-blue-300 mt-1">100%</p>
                    <p class="text-[10px] text-slate-400 mt-1">
                        Affiliates earn the full first payment from every customer they refer.
                    </p>
                </div>

                <div class="rounded-2xl bg-slate-950/70 border border-slate-800 p-4">
                    <p class="text-[11px] font-semibold text-emerald-400">Recurring commission</p>
                    <p class="text-[22px] font-bold text-emerald-300 mt-1">60%</p>
                    <p class="text-[10px] text-slate-400 mt-1">
                        On every renewal — forever. True lifetime recurring income.
                    </p>
                </div>

                <div class="rounded-2xl bg-slate-950/70 border border-slate-800 p-4">
                    <p class="text-[11px] font-semibold text-sky-400">Tracking window</p>
                    <p class="text-[22px] font-bold text-sky-300 mt-1">180 days</p>
                    <p class="text-[10px] text-slate-400 mt-1">
                        The longest referral cookie in the industry. Converts like crazy.
                    </p>
                </div>

            </div>
        </div>

        <hr class="border-slate-800 my-6" />

        {{-- API Keys --}}
        <div class="space-y-2">
            <p class="text-[11px] font-semibold text-slate-200">API Keys (coming soon)</p>
            <p class="text-[10px] text-slate-400 mb-2">
                Affiliates will be able to generate API keys to pull stats directly.
            </p>

            <div class="rounded-2xl border border-slate-800 bg-slate-950/60 p-4">
                <p class="text-[10px] text-slate-500 italic">
                    API key management is not enabled yet.
                    When active, affiliates will be able to:
                </p>

                <ul class="mt-2 space-y-1 text-[10px] text-slate-300">
                    <li>• Generate read-only API keys</li>
                    <li>• Pull sales, payouts, sessions, clicks</li>
                    <li>• Build custom dashboards or integrate with CRMs</li>
                </ul>
            </div>
        </div>

    </section>
@endsection
