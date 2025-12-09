<?php

namespace App\Http\Controllers;

use App\Models\Affiliate;
use App\Models\AffiliateClick;
use App\Models\AffiliateSession;
use App\Models\AffiliateCommission;
use App\Models\Payout as AffiliatePayout;

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

        return view('affiliate-portal', compact(
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

    public function analytics()
    {
        return view('affiliate-analytics');
    }

    public function campaigns()
    {
        return view('affiliate-campaigns');
    }

    public function payouts()
    {
        return view('affiliate-payouts');
    }

    public function sales()
    {
        return view('affiliate-sales');
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
