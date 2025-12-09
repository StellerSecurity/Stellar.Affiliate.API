<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>@yield('title', 'Stellar Affiliate Portal')</title>
    <meta name="viewport" content="width=device-width, initial-scale=1">

    <script src="https://cdn.tailwindcss.com"></script>
    <script>
        tailwind.config = {
            theme: {
                extend: {
                    colors: {
                        stellar: {
                            blue: '#2563ff',
                            blueDark: '#1b46d8',
                            navy: '#020826'
                        }
                    },
                    borderRadius: {
                        '3xl': '1.75rem',
                        '4xl': '2.25rem'
                    },
                    boxShadow: {
                        'glass': '0 24px 70px rgba(15,23,42,0.45)'
                    }
                }
            }
        }
    </script>
</head>
<body class="bg-slate-950 text-slate-100 antialiased">
<div class="min-h-screen bg-slate-950 flex flex-col">
    @include('partials.affiliate-topbar')

    <div class="flex-1 mx-auto flex w-full max-w-6xl gap-4 px-4 py-4">
        @include('partials.affiliate-sidebar')

        <main class="flex-1 space-y-4">
            @yield('content')
        </main>
    </div>

    <footer class="mx-auto mt-auto w-full max-w-6xl px-4 pb-4 text-[10px] text-slate-500">
        <div class="flex flex-wrap items-center justify-between gap-2 border-t border-slate-800 pt-3">
            <span>© Stellar Security · Swiss privacy-first ecosystem.</span>
            <span>Referral cookie: <span class="font-semibold text-slate-200">180 days</span>.</span>
        </div>
    </footer>
</div>
</body>
</html>
