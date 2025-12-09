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
    public function dashboard()
    {
        // Top-level stats
        $totalAffiliates  = Affiliate::count();
        $totalClicks      = AffiliateClick::count();
        $totalSessions    = AffiliateSession::count();
        $totalEarnings    = AffiliateCommission::sum('amount');

        $clicksLast30     = AffiliateClick::where('created_at', '>=', now()->subDays(30))->count();

        // If you later add an "is_initial" or "type" column, you can refine this again
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

        $currentAffiliate = null;
        if (auth()->check() && method_exists(auth()->user(), 'affiliate')) {
            $currentAffiliate = auth()->user()->affiliate;
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
        $search = $request->query('q');

        $query = Affiliate::query()->orderByDesc('created_at');

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
        $search = $request->query('q');

        $query = AffiliateCampaign::with('affiliate')
            ->orderByDesc('created_at');

        if ($search) {
            $query->where('name', 'like', "%{$search}%")
                ->orWhere('source', 'like', "%{$search}%")
                ->orWhereHas('affiliate', function ($q) use ($search) {
                    $q->where('public_code', 'like', "%{$search}%");
                });
        }

        $campaigns = $query->paginate(25)->withQueryString();

        $affiliates = Affiliate::orderBy('public_code')->get(['id', 'public_code']);

        return view('affiliate-campaigns', [
            'campaigns'  => $campaigns,
            'affiliates' => $affiliates,
            'search'     => $search,
        ]);
    }

    public function campaignsStore(Request $request)
    {
        $data = $request->validate([
            'affiliate_id' => ['required', 'exists:affiliates,id'],
            'name'         => ['required', 'string', 'max:255'],
            'source'       => ['nullable', 'string', 'max:50'],
            'sub_id1'      => ['nullable', 'string', 'max:255'],
            'sub_id2'      => ['nullable', 'string', 'max:255'],
        ]);

        AffiliateCampaign::create($data);

        return redirect()
            ->route('affiliate.campaigns.index')
            ->with('status', 'Campaign created successfully!');
    }

    public function affiliatesStore(Request $request)
    {
        $data = $request->validate([
            'name'                => ['required', 'string', 'max:255'],
            'email'               => ['nullable', 'email', 'max:255'],
            'public_code'         => ['nullable', 'string', 'max:50', 'unique:affiliates,public_code'],
            'base_redirect_url'   => ['nullable', 'string', 'max:2048'],
            'is_active'           => ['nullable', 'boolean'],
        ]);

        if (empty($data['public_code'])) {
            $data['public_code'] = strtoupper(Str::random(8));
        }

        $data['is_active'] = $request->boolean('is_active', true);

        Affiliate::create($data);

        return redirect()
            ->route('affiliate.affiliates.index')
            ->with('status', 'Affiliate created.');
    }

    public function analytics(Request $request)
    {
        $from = now()->subDays(30);

        $clicksLast30    = AffiliateClick::where('created_at', '>=', $from)->count();
        $sessionsLast30  = AffiliateSession::where('created_at', '>=', $from)->count();
        $salesLast30     = AffiliateCommission::where('created_at', '>=', $from)->count();
        $revenueLast30   = AffiliateCommission::where('created_at', '>=', $from)->sum('amount');

        $conversionRate  = $clicksLast30 > 0 ? round(($salesLast30 / max($clicksLast30, 1)) * 100, 2) : 0;
        $epc             = $clicksLast30 > 0 ? round($revenueLast30 / max($clicksLast30, 1), 4) : 0;

        // Top affiliates by commission in the last 30 days
        $topAffiliates = AffiliateCommission::with('affiliate')
            ->select(
                'affiliate_id',
                DB::raw('COUNT(*) as sales_count'),
                DB::raw('SUM(amount) as total_commission')
            )
            ->where('created_at', '>=', $from)
            ->groupBy('affiliate_id')
            ->orderByDesc('total_commission')
            ->limit(10)
            ->get();

        // Simple daily stats for last 7 days (clicks + sales)
        $from7 = now()->subDays(7)->startOfDay();

        $dailyClicks = AffiliateClick::select(
            DB::raw('DATE(created_at) as day'),
            DB::raw('COUNT(*) as clicks')
        )
            ->where('created_at', '>=', $from7)
            ->groupBy(DB::raw('DATE(created_at)'))
            ->orderBy('day')
            ->get()
            ->keyBy('day');

        $dailySales = AffiliateCommission::select(
            DB::raw('DATE(created_at) as day'),
            DB::raw('COUNT(*) as sales')
        )
            ->where('created_at', '>=', $from7)
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
        $status = $request->query('status');

        $query = AffiliatePayout::with('affiliate')
            ->orderByDesc('created_at');

        if ($status && in_array($status, ['pending', 'processing', 'paid', 'failed'], true)) {
            $query->where('status', $status);
        }

        $payouts = $query->paginate(25)->withQueryString();

        $totalPaid    = AffiliatePayout::where('status', 'paid')->sum('amount');
        $totalPending = AffiliatePayout::where('status', 'pending')->sum('amount');

        $lastPayout = AffiliatePayout::where('status', 'paid')
            ->orderByDesc('created_at')
            ->first();

        return view('affiliate-payouts', [
            'payouts'      => $payouts,
            'totalPaid'    => $totalPaid,
            'totalPending' => $totalPending,
            'lastPayout'   => $lastPayout,
            'currentStatusFilter' => $status,
        ]);
    }

    public function sales(Request $request)
    {
        $status        = $request->query('status');
        $product       = $request->query('product');
        $affiliateCode = $request->query('affiliate');

        $query = AffiliateCommission::with('affiliate')
            ->orderByDesc('created_at');

        if ($status && in_array($status, ['pending', 'approved', 'rejected', 'paid'], true)) {
            $query->where('status', $status);
        }

        if ($product) {
            $query->where('product', $product);
        }

        if ($affiliateCode) {
            $query->whereHas('affiliate', function ($q) use ($affiliateCode) {
                $q->where('public_code', $affiliateCode);
            });
        }

        $sales = $query->paginate(25)->withQueryString();

        $totalCommission = AffiliateCommission::sum('amount');
        $totalSalesCount = AffiliateCommission::count();
        $avgCommission   = $totalSalesCount > 0 ? $totalCommission / $totalSalesCount : 0;

        $last30Commission = AffiliateCommission::where('created_at', '>=', now()->subDays(30))->sum('amount');
        $last30Count      = AffiliateCommission::where('created_at', '>=', now()->subDays(30))->count();

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
        return view('.affiliate-settings');
    }

    public function clicks()
    {
        // you can implement this later
        return view('affiliate-analytics'); // placeholder
    }

    public function sessions()
    {
        // you can implement this later
        return view('affiliate-analytics'); // placeholder
    }

    public function commissions()
    {
        // you can implement this later
        return view('affiliate-sales'); // placeholder
    }
}
