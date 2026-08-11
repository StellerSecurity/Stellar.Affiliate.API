<?php

namespace App\Http\Middleware;

use App\Models\Affiliate;
use App\Models\AffiliateCampaign;
use App\Services\AffiliateCommissionPolicy;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class PrepareAffiliateTrackingRequest
{
    public function handle(Request $request, Closure $next): Response
    {
        $code = trim((string) $request->route('code'));
        $affiliate = $code !== ''
            ? Affiliate::query()->where('public_code', $code)->first()
            : null;

        if (! $affiliate) {
            return $next($request);
        }

        if ($affiliate->status !== 'active') {
            return redirect((string) config('app.url'));
        }

        $campaignName = trim((string) $request->query('campaign', ''));
        $campaign = null;

        if ($campaignName !== '') {
            $campaign = AffiliateCampaign::query()
                ->where('affiliate_id', (int) $affiliate->id)
                ->where('name', $campaignName)
                ->first();
        }

        $policy = app(AffiliateCommissionPolicy::class);
        $product = $policy->normalizeProduct((string) ($campaign?->product ?: $request->query('product', 'esim')));
        if (! in_array($product, ['esim', 'vpn', 'antivirus'], true)) {
            $product = 'esim';
        }
        $request->query->set('product', $product);

        if ($campaign) {
            foreach ([
                'src' => $campaign->source,
                'sub1' => $campaign->sub_id1,
                'sub2' => $campaign->sub_id2,
            ] as $key => $value) {
                if (trim((string) $request->query($key, '')) === '' && trim((string) $value) !== '') {
                    $request->query->set($key, $value);
                }
            }
        }

        $redirect = trim((string) $request->query('redirect', ''));
        if (! $this->isHttpUrl($redirect)) {
            $redirect = '';
        }

        if ($redirect === '' && $campaign) {
            $campaignRedirect = trim((string) $campaign->redirect_url);
            $redirect = $this->isHttpUrl($campaignRedirect)
                ? $campaignRedirect
                : $this->productDefaultRedirect($product);
        }

        if ($redirect === '' && ! $this->isHttpUrl((string) $affiliate->base_redirect_url)) {
            $redirect = $this->productDefaultRedirect($product);
        }

        if ($redirect !== '') {
            $request->query->set('redirect', $redirect);
        } else {
            $request->query->remove('redirect');
        }

        return $next($request);
    }

    private function productDefaultRedirect(string $product): string
    {
        return (string) config(
            'affiliate.products.'.$product.'.default_redirect_url',
            config('affiliate.products.esim.default_redirect_url')
        );
    }

    private function isHttpUrl(string $url): bool
    {
        if ($url === '' || ! filter_var($url, FILTER_VALIDATE_URL)) {
            return false;
        }

        return in_array(strtolower((string) parse_url($url, PHP_URL_SCHEME)), ['http', 'https'], true);
    }
}
