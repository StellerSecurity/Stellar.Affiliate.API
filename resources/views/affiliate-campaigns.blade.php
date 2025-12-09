<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8" />
  <title>Stellar Affiliate Campaigns</title>
  <meta name="viewport" content="width=device-width, initial-scale=1" />

  <!-- Tailwind CDN -->
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

  <style>
    html { scroll-behavior: smooth; }
  </style>
</head>
<body class="bg-slate-950 text-slate-100 antialiased">
<div class="min-h-screen bg-slate-950">
  <!-- Top bar -->
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
          <p class="text-[11px] text-slate-400">Build tracked campaign links with sub-IDs and source tagging.</p>
        </div>
      </div>

      <div class="flex items-center gap-4 text-[11px]">
        <div class="hidden md:flex items-center gap-2 rounded-full border border-slate-700 bg-slate-900 px-3 py-1.5">
          <span class="h-2 w-2 rounded-full bg-emerald-400"></span>
          <span class="text-slate-300">Referral cookie: 180 days</span>
        </div>
        <div class="flex items-center gap-2">
          <div class="text-right hidden sm:block">
            <p class="text-[11px] font-medium text-slate-200">affiliate@stellarvpn.org</p>
            <p class="text-[10px] text-slate-500">Campaign builder</p>
          </div>
          <button class="flex h-8 w-8 items-center justify-center rounded-full bg-slate-800 text-[11px] font-semibold">
            S
          </button>
        </div>
      </div>
    </div>
  </header>

  <div class="mx-auto flex max-w-6xl gap-4 px-4 py-4">
    <!-- Sidebar -->
    <aside class="hidden w-52 shrink-0 md:block">
      <nav class="space-y-1 text-[11px]">
        <p class="mb-2 text-[10px] font-semibold uppercase tracking-[0.12em] text-slate-500">Overview</p>
        <a href="stellar-affiliate-portal.html" class="flex items-center gap-2 rounded-2xl px-3 py-2 text-[11px] text-slate-400 hover:bg-slate-900 hover:text-slate-100 font-medium">
          <span>🏠</span>
          <span>Dashboard</span>
        </a>
        <a href="stellar-affiliate-analytics.html" class="flex items-center gap-2 rounded-2xl px-3 py-2 text-[11px] bg-slate-900 text-slate-100 font-semibold">
          <span>📈</span>
          <span>Analytics</span>
        </a>
        <a href="stellar-affiliate-sales.html" class="flex items-center gap-2 rounded-2xl px-3 py-2 text-[11px] text-slate-400 hover:bg-slate-900 hover:text-slate-100 font-medium">
          <span>🧾</span>
          <span>Sales</span>
        </a>
        <a href="stellar-affiliate-payouts.html" class="flex items-center gap-2 rounded-2xl px-3 py-2 text-[11px] text-slate-400 hover:bg-slate-900 hover:text-slate-100 font-medium">
          <span>💰</span>
          <span>Payouts</span>
        </a>

        <p class="mt-4 mb-2 text-[10px] font-semibold uppercase tracking-[0.12em] text-slate-500">Assets</p>
        <a href="stellar-affiliate-creatives.html" class="flex items-center gap-2 rounded-2xl px-3 py-2 text-[11px] font-medium text-slate-400 hover:bg-slate-900 hover:text-slate-100">
          <span>🎨</span>
          <span>Creatives</span>
        </a>
        <a href="stellar-affiliate-link.html" class="flex items-center gap-2 rounded-2xl px-3 py-2 text-[11px] font-medium text-slate-400 hover:bg-slate-900 hover:text-slate-100">
          <span>🔗</span>
          <span>Your link</span>
        </a>

        <p class="mt-4 mb-2 text-[10px] font-semibold uppercase tracking-[0.12em] text-slate-500">Account</p>
        <a href="stellar-affiliate-settings.html" class="flex items-center gap-2 rounded-2xl px-3 py-2 text-[11px] text-slate-400 hover:bg-slate-900 hover:text-slate-100 font-medium">
          <span>⚙️</span>
          <span>Settings</span>
        </a>
      </nav>
    </aside>

    <!-- Main content -->
    <main class="flex-1 space-y-4">

      <!-- Header -->
      <section class="flex flex-wrap items-center justify-between gap-3">
        <div>
          <p class="text-[11px] font-semibold text-slate-200">Campaign builder</p>
          <p class="mt-1 text-[10px] text-slate-400">
            Create tracking links with sub-IDs for each platform, video or ad.
          </p>
        </div>
        <button class="rounded-full bg-slate-100 px-4 py-1.5 text-[11px] font-semibold text-slate-900 hover:bg-white">
          New campaign
        </button>
      </section>

      <!-- Builder -->
      <section class="rounded-3xl border border-slate-800 bg-slate-900/70 p-4">
        <p class="text-[11px] font-semibold text-slate-200">Create a campaign link</p>
        <p class="mt-1 text-[10px] text-slate-400">
          Start from your base affiliate URL and customize it for a specific video, post or traffic source.
        </p>

        <div class="mt-4 grid gap-4 lg:grid-cols-[minmax(0,1.4fr)_minmax(0,1fr)]">
          <!-- Left form -->
          <div class="space-y-3 text-[10px]">
            <div>
              <label class="mb-1 block font-medium text-slate-200">Base affiliate URL</label>
              <div class="flex flex-col gap-2 rounded-2xl border border-slate-800 bg-slate-950/80 px-3 py-2">
                <div class="flex flex-col gap-2 sm:flex-row sm:items-center">
                  <span class="flex-1 truncate text-slate-100">
                    https://stellarvpn.org/?ref=YOURCODE
                  </span>
                  <button class="rounded-full bg-slate-100 px-3 py-1 text-[10px] font-semibold text-slate-900 hover:bg-white">
                    Copy
                  </button>
                </div>
                <p class="text-[10px] text-slate-500">
                  This URL already tracks you as the affiliate. Campaign parameters are added on top.
                </p>
              </div>
            </div>

            <div class="grid gap-3 sm:grid-cols-2">
              <div>
                <label class="mb-1 block font-medium text-slate-200">Campaign name</label>
                <input type="text" placeholder="yt_review_nov" class="w-full rounded-2xl border border-slate-800 bg-slate-950 px-3 py-2 text-[10px] text-slate-100 placeholder:text-slate-500" />
                <p class="mt-1 text-[10px] text-slate-500">
                  Internal name only you see inside the portal.
                </p>
              </div>
              <div>
                <label class="mb-1 block font-medium text-slate-200">Source</label>
                <select class="w-full rounded-2xl border border-slate-800 bg-slate-950 px-3 py-2 text-[10px] text-slate-100">
                  <option>YouTube</option>
                  <option>TikTok</option>
                  <option>Instagram</option>
                  <option>Blog</option>
                  <option>Ads</option>
                  <option>Other</option>
                </select>
                <p class="mt-1 text-[10px] text-slate-500">
                  Used in analytics to group performance.
                </p>
              </div>
            </div>

            <div class="grid gap-3 sm:grid-cols-3">
              <div>
                <label class="mb-1 block font-medium text-slate-200">Sub ID 1 (optional)</label>
                <input type="text" placeholder="video_id" class="w-full rounded-2xl border border-slate-800 bg-slate-950 px-3 py-2 text-[10px] text-slate-100 placeholder:text-slate-500" />
                <p class="mt-1 text-[10px] text-slate-500">
                  Example: short_code or video slug.
                </p>
              </div>
              <div>
                <label class="mb-1 block font-medium text-slate-200">Sub ID 2 (optional)</label>
                <input type="text" placeholder="placement" class="w-full rounded-2xl border border-slate-800 bg-slate-950 px-3 py-2 text-[10px] text-slate-100 placeholder:text-slate-500" />
                <p class="mt-1 text-[10px] text-slate-500">
                  Example: description, pinned_comment, story.
                </p>
              </div>
              <div>
                <label class="mb-1 block font-medium text-slate-200">Country focus (optional)</label>
                <select class="w-full rounded-2xl border border-slate-800 bg-slate-950 px-3 py-2 text-[10px] text-slate-100">
                  <option>Global</option>
                  <option>Germany</option>
                  <option>Denmark</option>
                  <option>Switzerland</option>
                  <option>EU</option>
                  <option>US</option>
                </select>
              </div>
            </div>

            <div>
              <label class="mb-1 block font-medium text-slate-200">Final campaign URL</label>
              <div class="flex flex-col gap-2 rounded-2xl border border-slate-800 bg-slate-950/80 px-3 py-2">
                <span class="truncate font-mono text-[10px] text-slate-100">
                  https://stellarvpn.org/?ref=YOURCODE&amp;src=youtube&amp;campaign=yt_review_nov&amp;sub1=video_id
                </span>
                <div class="flex flex-wrap items-center gap-2">
                  <button class="rounded-full bg-slate-100 px-3 py-1 text-[10px] font-semibold text-slate-900 hover:bg-white">
                    Copy URL
                  </button>
                  <button class="rounded-full border border-slate-700 px-3 py-1 text-[10px] text-slate-100 hover:bg-slate-900">
                    Generate QR
                  </button>
                  <span class="text-[10px] text-slate-500">
                    Cookie window: <span class="font-semibold text-slate-200">180 days</span>.
                  </span>
                </div>
              </div>
            </div>
          </div>

          <!-- Right: quick tips -->
          <div class="space-y-3 text-[10px]">
            <div class="rounded-2xl border border-slate-800 bg-slate-950/80 p-3">
              <p class="text-[11px] font-semibold text-slate-200">Best practices</p>
              <ul class="mt-2 space-y-1.5 text-slate-400">
                <li>• Use clear campaign names like <span class="font-mono text-slate-100">yt_review_nov</span> or <span class="font-mono text-slate-100">tt_clip_dec</span>.</li>
                <li>• Always set <span class="font-mono text-slate-100">source</span> so analytics can group performance.</li>
                <li>• Use sub IDs for placements, like <span class="font-mono text-slate-100">description</span> vs <span class="font-mono text-slate-100">pinned_comment</span>.</li>
                <li>• Keep using the same pattern to compare across months easily.</li>
              </ul>
            </div>

            <div class="rounded-2xl border border-slate-800 bg-slate-950/80 p-3">
              <p class="text-[11px] font-semibold text-slate-200">Cookie & attribution</p>
              <p class="mt-2 text-slate-400">
                When someone clicks your campaign URL, a <span class="font-semibold text-slate-100">180-day cookie</span> is set.
                Any purchase during that window is attributed to you – even if they come back on another device (when logged in).
              </p>
            </div>
          </div>
        </div>
      </section>

      <!-- Existing campaigns -->
      <section class="rounded-3xl border border-slate-800 bg-slate-900/70 p-4">
        <div class="flex flex-wrap items-center justify-between gap-3">
          <div>
            <p class="text-[11px] font-semibold text-slate-200">Your campaigns</p>
            <p class="mt-1 text-[10px] text-slate-400">Performance per campaign and source.</p>
          </div>
          <div class="flex flex-wrap items-center gap-2 text-[10px]">
            <select class="rounded-full border border-slate-700 bg-slate-950 px-3 py-1 text-slate-300">
              <option>Last 30 days</option>
              <option>Last 90 days</option>
              <option>This year</option>
            </select>
          </div>
        </div>

        <div class="mt-3 overflow-x-auto text-[10px]">
          <table class="min-w-full border-separate border-spacing-y-1">
            <thead class="text-slate-400">
              <tr>
                <th class="px-3 py-1 text-left font-medium">Campaign</th>
                <th class="px-3 py-1 text-left font-medium">Source</th>
                <th class="px-3 py-1 text-right font-medium">Clicks</th>
                <th class="px-3 py-1 text-right font-medium">Signups</th>
                <th class="px-3 py-1 text-right font-medium">Conv. rate</th>
                <th class="px-3 py-1 text-right font-medium">Earnings</th>
              </tr>
            </thead>
            <tbody>
              <tr class="rounded-2xl bg-slate-950/70 text-slate-200">
                <td class="px-3 py-2 rounded-l-2xl">yt_review_nov</td>
                <td class="px-3 py-2">YouTube</td>
                <td class="px-3 py-2 text-right">3,120</td>
                <td class="px-3 py-2 text-right">182</td>
                <td class="px-3 py-2 text-right">5.8%</td>
                <td class="px-3 py-2 rounded-r-2xl text-right text-emerald-400">€980</td>
              </tr>
              <tr class="rounded-2xl bg-slate-950/60 text-slate-200">
                <td class="px-3 py-2 rounded-l-2xl">tt_short_clip</td>
                <td class="px-3 py-2">TikTok</td>
                <td class="px-3 py-2 text-right">1,980</td>
                <td class="px-3 py-2 text-right">96</td>
                <td class="px-3 py-2 text-right">4.8%</td>
                <td class="px-3 py-2 rounded-r-2xl text-right text-emerald-400">€610</td>
              </tr>
              <tr class="rounded-2xl bg-slate-950/60 text-slate-200">
                <td class="px-3 py-2 rounded-l-2xl">blog_comparison</td>
                <td class="px-3 py-2">Blog</td>
                <td class="px-3 py-2 text-right">920</td>
                <td class="px-3 py-2 text-right">44</td>
                <td class="px-3 py-2 text-right">4.7%</td>
                <td class="px-3 py-2 rounded-r-2xl text-right text-emerald-400">€280</td>
              </tr>
            </tbody>
          </table>
        </div>
      </section>

      <!-- Footer -->
      <footer class="py-4 text-[10px] text-slate-500">
        <div class="flex flex-wrap items-center justify-between gap-2 border-t border-slate-800 pt-3">
          <span>© Stellar Security · Swiss privacy-first ecosystem.</span>
          <span>Referral cookie window: <span class="font-semibold text-slate-200">180 days</span>.</span>
        </div>
      </footer>
    </main>
  </div>
</div>
</body>
</html>
