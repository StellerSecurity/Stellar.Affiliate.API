<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Affiliate;
use Illuminate\Http\Request;

class AffiliateDashboardController extends Controller
{
    public function overview(Request $request)
    {
        // Later: resolve affiliate from auth / api key / token
        // For now you can hardcode or debug with a fixed affiliate_id.

        // Example placeholder response
        return response()->json([
            'total_earnings'        => 0,
            'last_30_days_earnings' => 0,
            'clicks_last_30'        => 0,
            'signups_last_30'       => 0,
            'conversion_rate'       => 0,
            'active_subscriptions'  => 0,
            'next_payout_amount'    => 0,
            'next_payout_in_days'   => null,
            'latest_sales'          => [],
        ]);
    }
}
