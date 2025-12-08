<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Affiliate;
use App\Models\AffiliateCommission;
use App\Models\Payout;
use Illuminate\Http\Request;

class AffiliatePayoutController extends Controller
{
    public function summary(Request $request)
    {
        // Later: resolve affiliate_id from auth
        $affiliateId = $request->user()?->affiliate_id ?? null;

        if (! $affiliateId) {
            return response()->json(['message' => 'Affiliate not resolved'], 400);
        }

        $totalPaid = Payout::where('affiliate_id', $affiliateId)
            ->where('status', 'paid')
            ->sum('amount');

        $availableBalance = AffiliateCommission::where('affiliate_id', $affiliateId)
            ->where('status', 'approved')
            ->whereNull('payout_id')
            ->sum('amount');

        $nextPayout = Payout::where('affiliate_id', $affiliateId)
            ->whereIn('status', ['pending', 'processing'])
            ->orderBy('created_at', 'desc')
            ->first();

        return response()->json([
            'available_balance' => $availableBalance,
            'total_paid'        => $totalPaid,
            'next_payout'       => $nextPayout,
        ]);
    }

    public function history(Request $request)
    {
        // Later: resolve affiliate_id from auth
        $affiliateId = $request->user()?->affiliate_id ?? null;

        if (! $affiliateId) {
            return response()->json(['message' => 'Affiliate not resolved'], 400);
        }

        $payouts = Payout::where('affiliate_id', $affiliateId)
            ->orderByDesc('created_at')
            ->paginate(20);

        return response()->json($payouts);
    }
}
