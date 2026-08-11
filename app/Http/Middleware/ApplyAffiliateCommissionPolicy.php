<?php

namespace App\Http\Middleware;

use App\Models\AffiliateCommission;
use App\Services\AffiliateCommissionPolicy;
use App\Support\AffiliateRequestContext;
use Closure;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class ApplyAffiliateCommissionPolicy
{
    public function handle(Request $request, Closure $next): Response
    {
        if (! AffiliateRequestContext::isOrderPaid($request)) {
            return $next($request);
        }

        $product = app(AffiliateCommissionPolicy::class)->normalizeProduct((string) $request->input('product', 'esim'));
        $request->attributes->set('affiliate_commission_product', $product);

        $response = $next($request);

        if (! $response instanceof JsonResponse || $response->getStatusCode() >= 400) {
            return $response;
        }

        $payload = $response->getData(true);
        $commissionId = $payload['commission_id'] ?? null;

        if (! $commissionId || ($payload['status'] ?? null) !== 'ok') {
            return $response;
        }

        $commission = AffiliateCommission::query()->find($commissionId);
        if (! $commission) {
            return $response;
        }

        $payload['rate'] = (float) $commission->rate;
        $payload['amount'] = (float) $commission->amount;
        $response->setData($payload);

        return $response;
    }
}
