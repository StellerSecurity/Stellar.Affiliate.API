<aside class="hidden w-52 shrink-0 md:block">
    <nav class="space-y-1 text-[11px]">
        <p class="mb-2 text-[10px] font-semibold uppercase tracking-[0.12em] text-slate-500">Overview</p>

        <a href="{{ route('affiliate.affiliates.index') }}"
           class="w-full flex items-center gap-2 rounded-2xl px-3 py-2 text-[11px] font-medium
          {{ request()->routeIs('affiliate.affiliates.index') ? 'bg-slate-900 text-slate-100' : 'text-slate-400 hover:bg-slate-900 hover:text-slate-100' }}">
            <span>👥</span>
            <span>Affiliates</span>
        </a>

        <a href="{{ route('affiliate.dashboard') }}"
           class="w-full flex items-center gap-2 rounded-2xl px-3 py-2 text-[11px] font-semibold
                  {{ request()->routeIs('affiliate.dashboard') ? 'bg-slate-900 text-slate-100' : 'text-slate-400 hover:bg-slate-900 hover:text-slate-100' }}">
            <span>🏠</span>
            <span>Dashboard</span>
        </a>

        <a href="{{ route('affiliate.analytics') }}"
           class="w-full flex items-center gap-2 rounded-2xl px-3 py-2 text-[11px] font-medium
                  {{ request()->routeIs('affiliate.analytics') ? 'bg-slate-900 text-slate-100' : 'text-slate-400 hover:bg-slate-900 hover:text-slate-100' }}">
            <span>📈</span>
            <span>Analytics</span>
        </a>

        <a href="{{ route('affiliate.sales') }}"
           class="w-full flex items-center gap-2 rounded-2xl px-3 py-2 text-[11px] font-medium
                  {{ request()->routeIs('affiliate.sales') ? 'bg-slate-900 text-slate-100' : 'text-slate-400 hover:bg-slate-900 hover:text-slate-100' }}">
            <span>🧾</span>
            <span>Sales</span>
        </a>

        <a href="{{ route('affiliate.payouts') }}"
           class="w-full flex items-center gap-2 rounded-2xl px-3 py-2 text-[11px] font-medium
                  {{ request()->routeIs('affiliate.payouts') ? 'bg-slate-900 text-slate-100' : 'text-slate-400 hover:bg-slate-900 hover:text-slate-100' }}">
            <span>💰</span>
            <span>Payouts</span>
        </a>

        <p class="mt-4 mb-2 text-[10px] font-semibold uppercase tracking-[0.12em] text-slate-500">Account</p>

        <a href="{{ route('affiliate.settings') }}"
           class="w-full flex items-center gap-2 rounded-2xl px-3 py-2 text-[11px] font-medium
                  {{ request()->routeIs('affiliate.settings') ? 'bg-slate-900 text-slate-100' : 'text-slate-400 hover:bg-slate-900 hover:text-slate-100' }}">
            <span>⚙️</span>
            <span>Settings</span>
        </a>
    </nav>
</aside>
