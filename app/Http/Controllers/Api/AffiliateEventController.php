<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Affiliate;
use App\Models\AffiliateCommission;
use App\Models\AffiliateSession;
use App\Models\Payout;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class AffiliateEventController extends Controller
{
    /**
     * Endpoint that receives normalized billing events.
     *
     * Example payload:
     * {
     *   "event": "order.paid",
     *   "order_id": 12345,
     *   "user_id": 678,
     *   "product": "stellar_vpn",
     *   "plan": "annual",
     *   "amount": 59.99,
     *   "currency": "EUR",
     *   "is_initial": true
     * }
     */
    public function handleOrderPaid(Request $request)
    {
        // Later: validate a shared secret / signature from billing service
        $data = $request->validate([
            'order_id'    => 'required|integer',
            'user_id'     => 'required|integer',
            'amount'      => 'required|numeric',
            'currency'    => 'required|string|size:3',
            'product'     => 'required|string',
            'plan'        => 'required|string',
            'is_initial'  => 'required|boolean',
            'subscription_id' => 'nullable|integer',
        ]);

        // TODO:
        // 1) Resolve affiliate_id from user_id (via some internal mapping or service)
        // 2) Decide rate: 1.00 for initial, 0.60 for recurring
        // 3) Create AffiliateCommission record
        // This will be implemented when integrating with your main billing system.

        Log::info('Received order.paid event for affiliate processing', $data);

        return response()->json(['status' => 'ok']);
    }
}
