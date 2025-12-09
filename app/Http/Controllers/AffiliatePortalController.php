<?php

namespace App\Http\Controllers;

use App\Models\Affiliate;
use App\Models\AffiliateClick;
use App\Models\AffiliateSession;
use App\Models\AffiliateCommission;
use App\Models\Payout as AffiliatePayout;
use Illuminate\Http\Request;

class AffiliatePortalController extends Controller
{
    public function dashboard()
    {
        // Top stats
        $totalAffiliates   = Affiliate::count();
        $totalClicks       = AffiliateClick::count();
        $totalSessions     = AffiliateSession::count();
        $totalEarnings     = AffiliateCommission::sum('amount');

        $clicksLast30      = AffiliateClick::where('created_at', '>=', now()->subDays(30))->count();
        $initialSalesLast30 = AffiliateCommission::where('is_initial', true)
            ->where('created_at', '>=', now()->subDays(30))
            ->count();

        $pendingPayouts    = AffiliatePayout::where('status', 'pending')->sum('amount');
        $paidPayouts       = AffiliatePayout::where('status', 'paid')->sum('amount');

        // Recent things
        $latestAffiliates  = Affiliate::latest()->take(5)->get();
        $latestSales       = AffiliateCommission::with('affiliate')
            ->latest()
            ->take(10)
            ->get();

        $recentPayouts     = AffiliatePayout::with('affiliate')
            ->latest()
            ->take(5)
            ->get();

        // If current logged-in user is tied to a specific affiliate
        $currentAffiliate = null;
        if (auth()->check() && method_exists(auth()->user(), 'affiliate')) {
            $currentAffiliate = auth()->user()->affiliate;
        }

        return view('admin.affiliate-portal', compact(
            'totalAffiliates',
            'totalClicks',
            'totalSessions',
            'totalEarnings',
            'clicksLast30',
            'initialSalesLast30',
            'pendingPayouts',
            'paidPayouts',
            'latestAffiliates',
            'latestSales',
            'recentPayouts',
            'currentAffiliate'
        ));
    }

    public function analytics()
    {
        return view('admin.affiliate-analytics');
    }

    public function campaigns()
    {
        return view('admin.affiliate-campaigns');
    }

    public function payouts()
    {
        return view('admin.affiliate-payouts');
    }

    public function sales()
    {
        return view('admin.affiliate-sales');
    }

    public function settings()
    {
        return view('admin.affiliate-settings');
    }
}
