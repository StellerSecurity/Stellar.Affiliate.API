<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Stellar Affiliate Admin Login</title>
    <meta name="viewport" content="width=device-width, initial-scale=1">

    <script src="https://cdn.tailwindcss.com"></script>
</head>
<body class="min-h-screen bg-slate-950 text-slate-100 flex items-center justify-center">
<div class="w-full max-w-sm px-4">
    <div class="rounded-3xl bg-slate-900/80 border border-slate-800 p-6 shadow-xl">
        <div class="flex items-center gap-3 mb-4">
            <div class="flex h-9 w-9 items-center justify-center rounded-2xl bg-slate-900 text-white shadow-md shadow-blue-500/40">
                <span class="text-sm font-semibold">S</span>
            </div>
            <div>
                <p class="text-sm font-semibold">Stellar Affiliate Admin</p>
                <p class="text-[11px] text-slate-400">Secure Swiss backend access.</p>
            </div>
        </div>

        @if ($errors->any())
            <div class="mb-3 rounded-2xl bg-red-500/10 border border-red-500/60 px-3 py-2 text-[11px] text-red-200">
                {{ $errors->first() }}
            </div>
        @endif

        <form method="POST" action="{{ route('admin.login.post') }}" class="space-y-3">
            @csrf

            <div class="space-y-1">
                <label class="text-[11px] text-slate-300" for="email">Email</label>
                <input
                    id="email"
                    type="email"
                    name="email"
                    value="{{ old('email') }}"
                    required
                    autofocus
                    class="w-full rounded-2xl border border-slate-700 bg-slate-950 px-3 py-2 text-[12px] text-slate-100 focus:outline-none focus:ring-1 focus:ring-blue-500"
                >
            </div>

            <div class="space-y-1">
                <label class="text-[11px] text-slate-300" for="password">Password</label>
                <input
                    id="password"
                    type="password"
                    name="password"
                    required
                    class="w-full rounded-2xl border border-slate-700 bg-slate-950 px-3 py-2 text-[12px] text-slate-100 focus:outline-none focus:ring-1 focus:ring-blue-500"
                >
            </div>

            <div class="flex items-center justify-between text-[11px]">
                <label class="inline-flex items-center gap-1 text-slate-400">
                    <input type="checkbox" name="remember" class="h-3 w-3 rounded border-slate-600 bg-slate-900">
                    <span>Remember me</span>
                </label>
                <span class="text-slate-500 text-[10px]">Swiss IPs are monitored.</span>
            </div>

            <button
                type="submit"
                class="mt-2 w-full rounded-2xl bg-slate-100 px-4 py-2 text-[12px] font-semibold text-slate-900 hover:bg-white"
            >
                Log in
            </button>
        </form>
    </div>
    <p class="mt-3 text-center text-[10px] text-slate-500">
        © Stellar Security · Internal use only.
    </p>
</div>
</body>
</html>
