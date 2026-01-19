<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\AffiliateCommission;
use App\Models\Payout;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class AffiliateDashboardController extends Controller
{


    public function overview(Request $request): JsonResponse
    {
        $affiliate = $request->attributes->get('affiliate');

        // Safety check
        if (! $affiliate) {
            return response()->json(['message' => 'Affiliate not resolved'], 400);
        }

        $affiliateId = $affiliate->id;

        // Example basic stats – du kan udbygge senere
        $totalEarnings = AffiliateCommission::where('affiliate_id', $affiliateId)
            ->whereIn('status', ['approved', 'paid_out'])
            ->sum('amount');

        $last30Earnings = AffiliateCommission::where('affiliate_id', $affiliateId)
            ->whereIn('status', ['approved', 'paid_out'])
            ->where('created_at', '>=', now()->subDays(30))
            ->sum('amount');

        $totalPaid = Payout::where('affiliate_id', $affiliateId)
            ->where('status', 'paid')
            ->sum('amount');

        return response()->json([
            'affiliate' => [
                'id'            => $affiliate->id,
                'code'          => $affiliate->public_code,
                'status'        => $affiliate->status,
                'payoutCurrency'=> $affiliate->payout_currency,
            ],
            'total_earnings'        => $totalEarnings,
            'last_30_days_earnings' => $last30Earnings,
            'total_paid_out'        => $totalPaid,
            // You can add more stats later
        ]);
    }
}
