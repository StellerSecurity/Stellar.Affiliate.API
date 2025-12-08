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
     * Handle incoming affiliate click and redirect to product site
     * with affiliate session as query parameter.
     *
     * Example:
     *   https://stellarafi.com/api/v1/r/AFF123?src=youtube&campaign=review_oct&product=vpn
     *
     * Redirects to something like:
     *   https://stellarvpn.org/?aff_session=SESSIONTOKEN&code=AFF123&src=youtube&campaign=review_oct&product=vpn
     */
    public function redirect(Request $request, string $code)
    {
        $affiliate = Affiliate::where('public_code', $code)->first();

        if (! $affiliate) {
            return redirect(config('app.url'));
        }

        $source   = $request->query('src');
        $campaign = $request->query('campaign');
        $sub1     = $request->query('sub1');
        $sub2     = $request->query('sub2');
        $product  = $request->query('product'); // e.g. vpn, antivirus, notes

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

        // 1) Optional: campaign specific redirect (if you add such a column later)
        $campaignRedirect = $campaignModel?->redirect_url ?? null;

        // 2) Affiliate base redirect from DB
        $affiliateRedirect = $affiliate->base_redirect_url ?? null;

        // 3) Global default
        $defaultRedirect = config('affiliate.default_redirect_url', 'https://stellarvpn.org/');

        $baseRedirect = $campaignRedirect
            ?: $affiliateRedirect
                ?: $defaultRedirect;

        $redirectUrl = $this->buildRedirectUrl($baseRedirect, [
            'aff_session' => $sessionToken,
            'code'        => $affiliate->public_code,
            'src'         => $source,
            'campaign'    => $campaign,
            'sub1'        => $sub1,
            'sub2'        => $sub2,
            'product'     => $product,
        ]);

        return redirect($redirectUrl);
    }

    protected function hashIp(?string $ip): ?string
    {
        if (! $ip) {
            return null;
        }

        return hash('sha256', $ip . config('app.key'));
    }

    protected function buildRedirectUrl(string $base, array $params): string
    {
        $filtered = array_filter($params, fn ($v) => ! is_null($v) && $v !== '');
        $query    = http_build_query($filtered);

        if ($query === '') {
            return $base;
        }

        if (str_contains($base, '?')) {
            return $base . '&' . $query;
        }

        return $base . '?' . $query;
    }
}
