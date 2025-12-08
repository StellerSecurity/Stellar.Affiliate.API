<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Affiliate;
use App\Models\AffiliateCampaign;
use App\Models\AffiliateClick;
use App\Models\AffiliateSession;
use Illuminate\Http\Request;
use Illuminate\Support\Str;

class AffiliateTrackingController extends Controller
{
    /**
     * Handle incoming affiliate click, set 180-day cookie and redirect to landing.
     *
     * Example route (with v1 prefix in routes/api.php):
     *   GET /api/v1/r/{code}
     *
     * Example URL:
     *   /api/v1/r/BLERIM01?src=youtube&campaign=yt_review_nov&sub1=video_id
     */
    public function redirect(Request $request, string $code)
    {
        $affiliate = Affiliate::where('public_code', $code)->first();

        if (! $affiliate) {
            // If affiliate not found, redirect to default app URL
            return redirect(config('app.url'));
        }

        $source   = $request->query('src');
        $campaign = $request->query('campaign');
        $sub1     = $request->query('sub1');
        $sub2     = $request->query('sub2');

        // Find or create campaign (you can change this to only allow predefined campaigns)
        $campaignModel = null;
        if ($campaign) {
            $campaignModel = AffiliateCampaign::firstOrCreate(
                [
                    'affiliate_id' => $affiliate->id,
                    'name'         => $campaign,
                ],
                [
                    'source'  => $source,
                    'sub_id1' => $sub1,
                    'sub_id2' => $sub2,
                ]
            );
        }

        // Create click record
        AffiliateClick::create([
            'affiliate_id' => $affiliate->id,
            'campaign_id'  => $campaignModel?->id,
            'source'       => $source,
            'session_id'   => (string) Str::uuid(),
            'ip_hash'      => $this->hashIp($request->ip()),
            'user_agent'   => (string) $request->userAgent(),
            'landing_url'  => $request->fullUrl(),
            'referrer'     => $request->headers->get('referer'),
        ]);

        // Create session and set cookie
        $sessionToken = Str::random(40);
        $expiresAt    = now()->addDays(180);

        AffiliateSession::create([
            'affiliate_id'        => $affiliate->id,
            'campaign_id'         => $campaignModel?->id,
            'source'              => $source,
            'session_token'       => $sessionToken,
            'browser_fingerprint' => null,
            'expires_at'          => $expiresAt,
        ]);

        // Set cookie for 180 days
        $response = redirect(config('app.url')); // TODO: change to your real landing URL if needed

        return $response->withCookie(cookie(
            name: 'stellar_aff',
            value: $sessionToken,
            minutes: 180 * 24 * 60, // 180 days
            path: '/',
            secure: true,
            httpOnly: true,
            sameSite: 'lax'
        ));
    }

    protected function hashIp(?string $ip): ?string
    {
        if (! $ip) {
            return null;
        }

        // Simple hashing to avoid storing raw IP addresses
        return hash('sha256', $ip . config('app.key'));
    }
}
