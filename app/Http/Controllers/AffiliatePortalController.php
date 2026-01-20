<?php

namespace App\Http\Controllers;

use App\Models\Affiliate;
use App\Models\AffiliateCampaign;
use App\Models\AffiliateClick;
use App\Models\AffiliateSession;
use App\Models\AffiliateCommission;
use App\Models\Payout as AffiliatePayout;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class AffiliatePortalController extends Controller
{
    private function resolvedAffiliate(Request $request): ?Affiliate
    {
        $affiliate = $request->attributes->get('affiliate');

        if ($affiliate instanceof Affiliate) {
            return $affiliate;
        }

        $user = $request->user();
        if (! $user) {
            return null;
        }

        // Try link by external_user_id
        $affiliate = Affiliate::query()
            ->where('external_user_id', $user->id)
            ->first();

        if ($affiliate) {
            $request->attributes->set('affiliate', $affiliate);
            return $affiliate;
        }

        // Fallback: match by email only if not linked yet, then auto-link
        $affiliate = Affiliate::query()
            ->whereNull('external_user_id')
            ->where('email', $user->email)
            ->first();

        if ($affiliate) {
            $affiliate->external_user_id = (int) $user->id;
            $affiliate->save();

            $request->attributes->set('affiliate', $affiliate);
            return $affiliate;
        }

        return null;
    }

    private function isAdmin(Request $request): bool
    {
        $user = $request->user();
        if (! $user) {
            return false;
        }

        // Simple admin allowlist via env
        $emails = array_filter(array_map('trim', explode(',', (string) env('AFFILIATE_ADMIN_EMAILS', ''))));

        return in_array((string) $user->email, $emails, true);
    }

    /**
     * If no affiliate and not admin -> NO DATA (zeros/empty lists) + show CTA in UI.
     */
    public function dashboard(Request $request)
    {
        $currentAffiliate = $this->resolvedAffiliate($request);
        $admin = $this->isAdmin($request);

        $needsAffiliateSetup = false;

        if ($currentAffiliate) {
            $aid = (int) $currentAffiliate->id;

            $totalAffiliates = 1;
            $totalClicks = AffiliateClick::where('affiliate_id', $aid)->count();
            $totalSessions = AffiliateSession::where('affiliate_id', $aid)->count();
            $totalEarnings = AffiliateCommission::where('affiliate_id', $aid)->sum('amount');

            $clicksLast30 = AffiliateClick::where('affiliate_id', $aid)
                ->where('created_at', '>=', now()->subDays(30))
                ->count();

            $salesLast30 = AffiliateCommission::where('affiliate_id', $aid)
                ->where('created_at', '>=', now()->subDays(30))
                ->count();

            $pendingPayouts = AffiliatePayout::where('affiliate_id', $aid)
                ->where('status', 'pending')
                ->sum('amount');

            $paidPayouts = AffiliatePayout::where('affiliate_id', $aid)
                ->where('status', 'paid')
                ->sum('amount');

            $latestAffiliates = collect([$currentAffiliate]);

            $latestSales = AffiliateCommission::with('affiliate')
                ->where('affiliate_id', $aid)
                ->latest()
                ->take(10)
                ->get();

            $recentPayouts = AffiliatePayout::with('affiliate')
                ->where('affiliate_id', $aid)
                ->latest()
                ->take(5)
                ->get();

            return view('affiliate-dashboard', compact(
                'totalAffiliates',
                'totalClicks',
                'totalSessions',
                'totalEarnings',
                'clicksLast30',
                'salesLast30',
                'pendingPayouts',
                'paidPayouts',
                'latestAffiliates',
                'latestSales',
                'recentPayouts',
                'currentAffiliate',
                'needsAffiliateSetup'
            ));
        }

        if ($admin) {
            // Admin: system-wide
            $totalAffiliates  = Affiliate::count();
            $totalClicks      = AffiliateClick::count();
            $totalSessions    = AffiliateSession::count();
            $totalEarnings    = AffiliateCommission::sum('amount');

            $clicksLast30     = AffiliateClick::where('created_at', '>=', now()->subDays(30))->count();
            $salesLast30      = AffiliateCommission::where('created_at', '>=', now()->subDays(30))->count();

            $pendingPayouts   = AffiliatePayout::where('status', 'pending')->sum('amount');
            $paidPayouts      = AffiliatePayout::where('status', 'paid')->sum('amount');

            $latestAffiliates = Affiliate::latest()->take(5)->get();

            $latestSales      = AffiliateCommission::with('affiliate')
                ->latest()
                ->take(10)
                ->get();

            $recentPayouts    = AffiliatePayout::with('affiliate')
                ->latest()
                ->take(5)
                ->get();

            return view('affiliate-dashboard', compact(
                'totalAffiliates',
                'totalClicks',
                'totalSessions',
                'totalEarnings',
                'clicksLast30',
                'salesLast30',
                'pendingPayouts',
                'paidPayouts',
                'latestAffiliates',
                'latestSales',
                'recentPayouts',
                'currentAffiliate',
                'needsAffiliateSetup'
            ));
        }

        // Non-affiliate user: show NOTHING (privacy)
        $needsAffiliateSetup = true;

        $totalAffiliates = 0;
        $totalClicks = 0;
        $totalSessions = 0;
        $totalEarnings = 0;

        $clicksLast30 = 0;
        $salesLast30 = 0;

        $pendingPayouts = 0;
        $paidPayouts = 0;

        $latestAffiliates = collect();
        $latestSales = collect();
        $recentPayouts = collect();

        return view('affiliate-dashboard', compact(
            'totalAffiliates',
            'totalClicks',
            'totalSessions',
            'totalEarnings',
            'clicksLast30',
            'salesLast30',
            'pendingPayouts',
            'paidPayouts',
            'latestAffiliates',
            'latestSales',
            'recentPayouts',
            'currentAffiliate',
            'needsAffiliateSetup'
        ));
    }

    /**
     * Affiliates page:
     * - Admin sees all
     * - Normal user sees only their affiliate (or empty)
     */
    public function affiliatesIndex(Request $request)
    {
        $currentAffiliate = $this->resolvedAffiliate($request);
        $admin = $this->isAdmin($request);

        $search = $request->query('q');

        $query = Affiliate::query()->orderByDesc('created_at');

        if (! $admin) {
            // Non-admin: only own affiliate or none
            if ($currentAffiliate) {
                $query->where('id', (int) $currentAffiliate->id);
            } else {
                // Return empty result set safely
                $query->whereRaw('1 = 0');
            }
        }

        if ($search) {
            $query->where(function ($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                    ->orWhere('email', 'like', "%{$search}%")
                    ->orWhere('public_code', 'like', "%{$search}%");
            });
        }

        $affiliates = $query->paginate(25)->withQueryString();

        return view('affiliate-affiliates', [
            'affiliates' => $affiliates,
            'search'     => $search,
            'currentAffiliate' => $currentAffiliate,
            'isAdmin' => $admin,
        ]);
    }

    /**
     * Create affiliate:
     * - Normal user creates ONE affiliate linked to themselves (external_user_id = auth user id)
     * - Admin can create unlinked affiliate by email (optional)
     */
    public function affiliatesStore(Request $request)
    {
        $admin = $this->isAdmin($request);
        $user = $request->user();

        if (! $user) {
            abort(401, 'Not authenticated');
        }

        // If non-admin already has an affiliate, stop.
        $existing = Affiliate::where('external_user_id', $user->id)->first();
        if (! $admin && $existing) {
            return redirect()
                ->route('affiliate.affiliates.index')
                ->with('status', 'Affiliate already exists for this user.');
        }

        $data = $request->validate([
            'name'              => ['required', 'string', 'max:255'],
            'email'             => [$admin ? 'nullable' : 'nullable', 'email', 'max:255'],
            'public_code'       => ['nullable', 'string', 'max:50', 'unique:affiliates,public_code'],
            'base_redirect_url' => ['nullable', 'string', 'max:2048'],
            'is_active'         => ['nullable', 'boolean'],
        ]);

        if (empty($data['public_code'])) {
            $data['public_code'] = strtoupper(Str::random(8));
        }

        $isActive = $request->boolean('is_active', true);

        $affiliate = new Affiliate();
        $affiliate->name = $data['name'];

        // Email rules:
        // - If normal user: always prefer auth user email
        // - If admin: allow provided email (for later linking)
        if (! $admin) {
            $affiliate->email = (string) $user->email;
            $affiliate->external_user_id = (int) $user->id;
        } else {
            $affiliate->email = $data['email'] ?? null;

            // If admin provides an email that matches a user, link it
            if (! empty($affiliate->email)) {
                $linkedUserId = \App\Models\User::where('email', $affiliate->email)->value('id');
                $affiliate->external_user_id = $linkedUserId ? (int) $linkedUserId : null;
            }
        }

        $affiliate->public_code = $data['public_code'];
        $affiliate->base_redirect_url = $data['base_redirect_url'] ?? null;
        $affiliate->status = $isActive ? 'active' : 'banned';

        $affiliate->save();

        // Attach to request for this session navigation
        $request->attributes->set('affiliate', $affiliate);

        return redirect()
            ->route('affiliate.dashboard')
            ->with('status', 'Affiliate created.');
    }

    public function campaignsIndex(Request $request)
    {
        $currentAffiliate = $this->resolvedAffiliate($request);
        $admin = $this->isAdmin($request);

        $search = $request->query('q');

        $query = AffiliateCampaign::with('affiliate')->orderByDesc('created_at');

        if ($currentAffiliate) {
            $query->where('affiliate_id', (int) $currentAffiliate->id);
        } elseif (! $admin) {
            // No affiliate and not admin => empty
            $query->whereRaw('1 = 0');
        }

        if ($search) {
            $query->where(function ($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                    ->orWhere('source', 'like', "%{$search}%")
                    ->orWhereHas('affiliate', function ($qa) use ($search) {
                        $qa->where('public_code', 'like', "%{$search}%");
                    });
            });
        }

        $campaigns = $query->paginate(25)->withQueryString();

        // Dropdown affiliates
        if ($admin) {
            $affiliates = Affiliate::orderBy('public_code')->get(['id', 'public_code']);
        } elseif ($currentAffiliate) {
            $affiliates = Affiliate::where('id', (int) $currentAffiliate->id)->get(['id', 'public_code']);
        } else {
            $affiliates = collect();
        }

        return view('affiliate-campaigns', [
            'campaigns'  => $campaigns,
            'affiliates' => $affiliates,
            'search'     => $search,
            'currentAffiliate' => $currentAffiliate,
            'isAdmin' => $admin,
        ]);
    }

    public function campaignsStore(Request $request)
    {
        $currentAffiliate = $this->resolvedAffiliate($request);
        $admin = $this->isAdmin($request);

        if (! $currentAffiliate && ! $admin) {
            return redirect()
                ->route('affiliate.affiliates.index')
                ->with('status', 'Create your affiliate first.');
        }

        $rules = [
            'affiliate_id' => $admin ? ['required', 'exists:affiliates,id'] : ['nullable'],
            'name'         => ['required', 'string', 'max:255'],
            'source'       => ['nullable', 'string', 'max:50'],
            'sub_id1'      => ['nullable', 'string', 'max:255'],
            'sub_id2'      => ['nullable', 'string', 'max:255'],
        ];

        $data = $request->validate($rules);

        if ($currentAffiliate) {
            $data['affiliate_id'] = (int) $currentAffiliate->id;
        }

        AffiliateCampaign::create($data);

        return redirect()
            ->route('affiliate.campaigns.index')
            ->with('status', 'Campaign created successfully!');
    }

    public function analytics(Request $request)
    {
        $currentAffiliate = $this->resolvedAffiliate($request);
        $admin = $this->isAdmin($request);
        $from = now()->subDays(30);

        // If no affiliate and not admin => empty analytics
        if (! $currentAffiliate && ! $admin) {
            return view('affiliate-analytics', [
                'clicksLast30' => 0,
                'sessionsLast30' => 0,
                'salesLast30' => 0,
                'revenueLast30' => 0,
                'conversionRate' => 0,
                'epc' => 0,
                'topAffiliates' => collect(),
                'daily' => [],
                'currentAffiliate' => null,
                'isAdmin' => false,
                'needsAffiliateSetup' => true,
            ]);
        }

        $clicksQ = AffiliateClick::query()->where('created_at', '>=', $from);
        $sessionsQ = AffiliateSession::query()->where('created_at', '>=', $from);
        $salesQ = AffiliateCommission::query()->where('created_at', '>=', $from);

        if ($currentAffiliate) {
            $aid = (int) $currentAffiliate->id;
            $clicksQ->where('affiliate_id', $aid);
            $sessionsQ->where('affiliate_id', $aid);
            $salesQ->where('affiliate_id', $aid);
        }

        $clicksLast30   = $clicksQ->count();
        $sessionsLast30 = $sessionsQ->count();
        $salesLast30    = $salesQ->count();
        $revenueLast30  = (clone $salesQ)->sum('amount');

        $conversionRate = $clicksLast30 > 0 ? round(($salesLast30 / max($clicksLast30, 1)) * 100, 2) : 0;
        $epc            = $clicksLast30 > 0 ? round($revenueLast30 / max($clicksLast30, 1), 4) : 0;

        $topAffiliatesQ = AffiliateCommission::with('affiliate')
            ->select(
                'affiliate_id',
                DB::raw('COUNT(*) as sales_count'),
                DB::raw('SUM(amount) as total_commission')
            )
            ->where('created_at', '>=', $from);

        if ($currentAffiliate) {
            $topAffiliatesQ->where('affiliate_id', (int) $currentAffiliate->id);
        }

        $topAffiliates = $topAffiliatesQ
            ->groupBy('affiliate_id')
            ->orderByDesc('total_commission')
            ->limit(10)
            ->get();

        $from7 = now()->subDays(7)->startOfDay();

        $dailyClicksQ = AffiliateClick::select(
            DB::raw('DATE(created_at) as day'),
            DB::raw('COUNT(*) as clicks')
        )->where('created_at', '>=', $from7);

        $dailySalesQ = AffiliateCommission::select(
            DB::raw('DATE(created_at) as day'),
            DB::raw('COUNT(*) as sales')
        )->where('created_at', '>=', $from7);

        if ($currentAffiliate) {
            $aid = (int) $currentAffiliate->id;
            $dailyClicksQ->where('affiliate_id', $aid);
            $dailySalesQ->where('affiliate_id', $aid);
        }

        $dailyClicks = $dailyClicksQ
            ->groupBy(DB::raw('DATE(created_at)'))
            ->orderBy('day')
            ->get()
            ->keyBy('day');

        $dailySales = $dailySalesQ
            ->groupBy(DB::raw('DATE(created_at)'))
            ->orderBy('day')
            ->get()
            ->keyBy('day');

        $daily = [];
        $cursor = $from7->copy();
        while ($cursor->lte(now())) {
            $key = $cursor->toDateString();
            $daily[] = [
                'day'    => $key,
                'clicks' => $dailyClicks[$key]->clicks ?? 0,
                'sales'  => $dailySales[$key]->sales ?? 0,
            ];
            $cursor->addDay();
        }

        return view('affiliate-analytics', [
            'clicksLast30'   => $clicksLast30,
            'sessionsLast30' => $sessionsLast30,
            'salesLast30'    => $salesLast30,
            'revenueLast30'  => $revenueLast30,
            'conversionRate' => $conversionRate,
            'epc'            => $epc,
            'topAffiliates'  => $topAffiliates,
            'daily'          => $daily,
            'currentAffiliate' => $currentAffiliate,
            'isAdmin' => $admin,
            'needsAffiliateSetup' => false,
        ]);
    }

    public function payouts(Request $request)
    {
        $currentAffiliate = $this->resolvedAffiliate($request);
        $admin = $this->isAdmin($request);

        if (! $currentAffiliate && ! $admin) {
            return view('affiliate-payouts', [
                'payouts' => collect(),
                'totalPaid' => 0,
                'totalPending' => 0,
                'lastPayout' => null,
                'currentStatusFilter' => null,
                'currentAffiliate' => null,
                'isAdmin' => false,
                'needsAffiliateSetup' => true,
            ]);
        }

        $status = $request->query('status');

        $query = AffiliatePayout::with('affiliate')->orderByDesc('created_at');

        if ($currentAffiliate) {
            $query->where('affiliate_id', (int) $currentAffiliate->id);
        }

        if ($status && in_array($status, ['pending', 'processing', 'paid', 'failed'], true)) {
            $query->where('status', $status);
        }

        $payouts = $query->paginate(25)->withQueryString();

        $totalPaidQ = AffiliatePayout::where('status', 'paid');
        $totalPendingQ = AffiliatePayout::where('status', 'pending');

        if ($currentAffiliate) {
            $aid = (int) $currentAffiliate->id;
            $totalPaidQ->where('affiliate_id', $aid);
            $totalPendingQ->where('affiliate_id', $aid);
        }

        $totalPaid = $totalPaidQ->sum('amount');
        $totalPending = $totalPendingQ->sum('amount');

        $lastPayoutQ = AffiliatePayout::where('status', 'paid')->orderByDesc('created_at');
        if ($currentAffiliate) {
            $lastPayoutQ->where('affiliate_id', (int) $currentAffiliate->id);
        }
        $lastPayout = $lastPayoutQ->first();

        return view('affiliate-payouts', [
            'payouts'              => $payouts,
            'totalPaid'            => $totalPaid,
            'totalPending'         => $totalPending,
            'lastPayout'           => $lastPayout,
            'currentStatusFilter'  => $status,
            'currentAffiliate' => $currentAffiliate,
            'isAdmin' => $admin,
            'needsAffiliateSetup' => false,
        ]);
    }

    public function sales(Request $request)
    {
        $currentAffiliate = $this->resolvedAffiliate($request);
        $admin = $this->isAdmin($request);

        if (! $currentAffiliate && ! $admin) {
            return view('affiliate-sales', [
                'sales' => collect(),
                'totalCommission' => 0,
                'totalSalesCount' => 0,
                'avgCommission' => 0,
                'last30Commission' => 0,
                'last30Count' => 0,
                'currentStatusFilter' => null,
                'currentProductFilter' => null,
                'currentAffiliateCode' => null,
                'currentAffiliate' => null,
                'isAdmin' => false,
                'needsAffiliateSetup' => true,
            ]);
        }

        $status  = $request->query('status');
        $product = $request->query('product');
        $affiliateCode = $request->query('affiliate'); // admin-only filter

        $query = AffiliateCommission::with('affiliate')->orderByDesc('created_at');

        if ($currentAffiliate) {
            $query->where('affiliate_id', (int) $currentAffiliate->id);
        } elseif ($admin && $affiliateCode) {
            $query->whereHas('affiliate', function ($q) use ($affiliateCode) {
                $q->where('public_code', $affiliateCode);
            });
        }

        if ($status && in_array($status, ['pending', 'approved', 'rejected', 'paid'], true)) {
            $query->where('status', $status);
        }

        if ($product) {
            $query->where('product', $product);
        }

        $sales = $query->paginate(25)->withQueryString();

        $totalsQ = AffiliateCommission::query();
        $last30Q = AffiliateCommission::where('created_at', '>=', now()->subDays(30));

        if ($currentAffiliate) {
            $aid = (int) $currentAffiliate->id;
            $totalsQ->where('affiliate_id', $aid);
            $last30Q->where('affiliate_id', $aid);
        } elseif ($admin && $affiliateCode) {
            $totalsQ->whereHas('affiliate', fn ($q) => $q->where('public_code', $affiliateCode));
            $last30Q->whereHas('affiliate', fn ($q) => $q->where('public_code', $affiliateCode));
        }

        $totalCommission = $totalsQ->sum('amount');
        $totalSalesCount = (clone $totalsQ)->count();
        $avgCommission   = $totalSalesCount > 0 ? $totalCommission / $totalSalesCount : 0;

        $last30Commission = $last30Q->sum('amount');
        $last30Count      = (clone $last30Q)->count();

        return view('affiliate-sales', [
            'sales'                => $sales,
            'totalCommission'      => $totalCommission,
            'totalSalesCount'      => $totalSalesCount,
            'avgCommission'        => $avgCommission,
            'last30Commission'     => $last30Commission,
            'last30Count'          => $last30Count,
            'currentStatusFilter'  => $status,
            'currentProductFilter' => $product,
            'currentAffiliateCode' => $affiliateCode,
            'currentAffiliate' => $currentAffiliate,
            'isAdmin' => $admin,
            'needsAffiliateSetup' => false,
        ]);
    }

    public function settings(Request $request)
    {
        $currentAffiliate = $this->resolvedAffiliate($request);
        $admin = $this->isAdmin($request);

        if (! $currentAffiliate && ! $admin) {
            // Let them see settings page if you want, but it should be empty / CTA
            return view('affiliate-settings', [
                'currentAffiliate' => null,
                'isAdmin' => false,
                'needsAffiliateSetup' => true,
            ]);
        }

        return view('affiliate-settings', [
            'currentAffiliate' => $currentAffiliate,
            'isAdmin' => $admin,
            'needsAffiliateSetup' => false,
        ]);
    }

    // Legacy stubs (keeps old routes from exploding)
    public function campaigns()
    {
        return view('affiliate-campaigns');
    }

    public function clicks()
    {
        return view('affiliate-analytics');
    }

    public function sessions()
    {
        return view('affiliate-analytics');
    }

    public function commissions()
    {
        return view('affiliate-sales');
    }
}
