<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\AffiliateCampaign;
use Illuminate\Http\Request;

class AffiliateCampaignController extends Controller
{
    public function index(Request $request)
    {
        $affiliate = $request->attributes->get('affiliate');

        if (! $affiliate || ! isset($affiliate->id)) {
            return response()->json(['message' => 'Affiliate not resolved'], 403);
        }


        $campaigns = AffiliateCampaign::query()
            ->where('affiliate_id', (int) $affiliate->id)
            ->orderByDesc('id')
            ->paginate(20);

        return response()->json($campaigns);
    }

    public function store(Request $request)
    {
        $affiliate = $request->attributes->get('affiliate');

        if (! $affiliate || ! isset($affiliate->id)) {
            return response()->json(['message' => 'Affiliate not resolved'], 403);
        }

        $data = $request->validate([
            'name'          => 'required|string|max:255',
            'source'        => 'nullable|string|max:50',
            'sub_id1'       => 'nullable|string|max:255',
            'sub_id2'       => 'nullable|string|max:255',
            'country_focus' => 'nullable|string|max:2',
        ]);

        $data['affiliate_id'] = (int) $affiliate->id;

        $campaign = AffiliateCampaign::create($data);

        return response()->json($campaign, 201);
    }
}
