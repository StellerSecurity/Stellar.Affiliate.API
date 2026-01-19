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
        return $affiliate instanceof Affiliate ? $affiliate : null;
    }

    public function dashboard(Request $request)
    {
        $currentAffiliate = $this->resolvedAffiliate($request);

        // Default: system-wide
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

        // If an affiliate is resolved, scope everything to that affiliate (privacy > vanity metrics)
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
        }

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
            'currentAffiliate'
        ));
    }

    public function affiliatesIndex(Request $request)
    {
        $currentAffiliate = $this->resolvedAffiliate($request);
        $search = $request->query('q');

        $query = Affiliate::query()->orderByDesc('created_at');

        // If affiliate user, only show self
        if ($currentAffiliate) {
            $query->where('id', $currentAffiliate->id);
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
        ]);
    }

    public function campaignsIndex(Request $request)
    {
        $currentAffiliate = $this->resolvedAffiliate($request);
        $search = $request->query('q');

        $query = AffiliateCampaign::with('affiliate')
            ->orderByDesc('created_at');

        if ($currentAffiliate) {
            $query->where('affiliate_id', (int) $currentAffiliate->id);
        }

        if ($search) {
            // IMPORTANT: group OR conditions, otherwise you leak other affiliates
            $query->where(function ($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                    ->orWhere('source', 'like', "%{$search}%")
                    ->orWhereHas('affiliate', function ($qa) use ($search) {
                        $qa->where('public_code', 'like', "%{$search}%");
                    });
            });
        }

        $campaigns = $query->paginate(25)->withQueryString();

        // If affiliate user, only show self in dropdown
        if ($currentAffiliate) {
            $affiliates = Affiliate::where('id', $currentAffiliate->id)->get(['id', 'public_code']);
        } else {
            $affiliates = Affiliate::orderBy('public_code')->get(['id', 'public_code']);
        }

        return view('affiliate-campaigns', [
            'campaigns'  => $campaigns,
            'affiliates' => $affiliates,
            'search'     => $search,
        ]);
    }

    public function campaignsStore(Request $request)
    {
        $currentAffiliate = $this->resolvedAffiliate($request);

        $rules = [
            'affiliate_id' => $currentAffiliate
                ? ['nullable']
                : ['required', 'exists:affiliates,id'],
            'name'         => ['required', 'string', 'max:255'],
            'source'       => ['nullable', 'string', 'max:50'],
            'sub_id1'      => ['nullable', 'string', 'max:255'],
            'sub_id2'      => ['nullable', 'string', 'max:255'],
        ];

        $data = $request->validate($rules);

        // If affiliate user, force affiliate_id to self (server-side, always)
        if ($currentAffiliate) {
            $data['affiliate_id'] = (int) $currentAffiliate->id;
        }

        AffiliateCampaign::create($data);

        return redirect()
            ->route('affiliate.campaigns.index')
            ->with('status', 'Campaign created successfully!');
    }

    public function affiliatesStore(Request $request)
    {
        $data = $request->validate([
            'name'              => ['required', 'string', 'max:255'],
            'email'             => ['nullable', 'email', 'max:255'],
            'public_code'       => ['nullable', 'string', 'max:50', 'unique:affiliates,public_code'],
            'base_redirect_url' => ['nullable', 'string', 'max:2048'],
            'is_active'         => ['nullable', 'boolean'],
        ]);

        if (empty($data['public_code'])) {
            $data['public_code'] = strtoupper(\Illuminate\Support\Str::random(8));
        }

        // Map "is_active" to your existing status enum behavior (minimal, keeps default active)
        // If you truly use status, you should save status instead of is_active.
        // We keep your logic but don't pretend is_active exists in DB.
        $isActive = $request->boolean('is_active', true);
        $data['status'] = $isActive ? 'active' : 'banned';

        // --- THIS IS THE IMPORTANT PART ---
        // If email matches an existing user, link to that user.
        // Otherwise, if you're logged in, link to the current user (self-create).
        $linkedUserId = null;

        if (!empty($data['email'])) {
            $linkedUserId = \App\Models\User::where('email', $data['email'])->value('id');
        }

        if (!$linkedUserId && auth()->check()) {
            $linkedUserId = auth()->id();
            // If they didn't provide email, at least store current user's email for future matching
            if (empty($data['email']) && auth()->user()?->email) {
                $data['email'] = auth()->user()->email;
            }
        }

        $data['external_user_id'] = $linkedUserId;

        Affiliate::create($data);

        return redirect()
            ->route('affiliate.affiliates.index')
            ->with('status', 'Affiliate created.');
    }

    public function analytics(Request $request)
    {
        $currentAffiliate = $this->resolvedAffiliate($request);
        $from = now()->subDays(30);

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
        ]);
    }

    public function campaigns()
    {
        return view('affiliate-campaigns');
    }

    public function payouts(Request $request)
    {
        $currentAffiliate = $this->resolvedAffiliate($request);
        $status = $request->query('status');

        $query = AffiliatePayout::with('affiliate')
            ->orderByDesc('created_at');

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
        ]);
    }

    public function sales(Request $request)
    {
        $currentAffiliate = $this->resolvedAffiliate($request);

        $status        = $request->query('status');
        $product       = $request->query('product');
        $affiliateCode = $request->query('affiliate');

        $query = AffiliateCommission::with('affiliate')
            ->orderByDesc('created_at');

        if ($currentAffiliate) {
            $query->where('affiliate_id', (int) $currentAffiliate->id);
        }

        if ($status && in_array($status, ['pending', 'approved', 'rejected', 'paid'], true)) {
            $query->where('status', $status);
        }

        if ($product) {
            $query->where('product', $product);
        }

        // Only allow affiliateCode filter when NOT in affiliate-scoped mode
        if (! $currentAffiliate && $affiliateCode) {
            $query->whereHas('affiliate', function ($q) use ($affiliateCode) {
                $q->where('public_code', $affiliateCode);
            });
        }

        $sales = $query->paginate(25)->withQueryString();

        $totalsQ = AffiliateCommission::query();
        $last30Q = AffiliateCommission::where('created_at', '>=', now()->subDays(30));

        if ($currentAffiliate) {
            $aid = (int) $currentAffiliate->id;
            $totalsQ->where('affiliate_id', $aid);
            $last30Q->where('affiliate_id', $aid);
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
        ]);
    }

    public function settings()
    {
        return view('affiliate-settings');
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
