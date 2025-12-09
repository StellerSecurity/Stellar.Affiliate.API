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
                    Track affiliates, clicks, and payouts in real time.
                </p>
            </div>
        </div>

        <div class="flex items-center gap-4 text-[11px]">
            <div class="hidden md:flex items-center gap-2 rounded-full border border-slate-700 bg-slate-900 px-3 py-1.5">
                <span class="h-2 w-2 rounded-full bg-emerald-400"></span>
                <span class="text-slate-300">Next payout batch: in 14 days</span>
            </div>
            <div class="flex items-center gap-2">
                <div class="text-right hidden sm:block">
                    <p class="text-[11px] font-medium text-slate-200">
                        {{ auth()->user()->email ?? 'affiliate-admin@stellarvpn.org' }}
                    </p>
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
