<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\AffiliateCampaign;
use Illuminate\Http\Request;

class AffiliateCampaignController extends Controller
{
    public function index(Request $request)
    {
        // Later: resolve affiliate_id from auth
        $affiliateId = $request->user()?->affiliate_id ?? null;

        $query = AffiliateCampaign::query();

        if ($affiliateId) {
            $query->where('affiliate_id', $affiliateId);
        }

        $campaigns = $query->orderByDesc('id')->paginate(20);

        return response()->json($campaigns);
    }

    public function store(Request $request)
    {
        // Later: resolve affiliate_id from auth
        $affiliateId = $request->user()?->affiliate_id ?? null;

        $data = $request->validate([
            'name'          => 'required|string|max:255',
            'source'        => 'nullable|string|max:50',
            'sub_id1'       => 'nullable|string|max:255',
            'sub_id2'       => 'nullable|string|max:255',
            'country_focus' => 'nullable|string|max:2',
        ]);

        if (! $affiliateId) {
            // You will later replace this with proper auth / token handling
            return response()->json(['message' => 'Affiliate not resolved'], 400);
        }

        $data['affiliate_id'] = $affiliateId;

        $campaign = AffiliateCampaign::create($data);

        return response()->json($campaign, 201);
    }
}
