<?php

namespace App\Http\Middleware;

use App\Models\Affiliate;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class ResolveAffiliateFromAuthUser
{
    public function handle(Request $request, Closure $next): Response
    {
        // Already resolved earlier in pipeline
        if ($request->attributes->get('affiliate')) {
            return $next($request);
        }

        $user = $request->user();

        if (! $user) {
            return $next($request);
        }

        $impersonation = $request->session()->get('affiliate_impersonation');
        if (is_array($impersonation)) {
            $affiliateId = (int) ($impersonation['affiliate_id'] ?? 0);
            $startedByUserId = (int) ($impersonation['started_by_user_id'] ?? 0);

            if ($affiliateId > 0
                && $startedByUserId === (int) $user->id
                && $user->affiliateAdminRole() === 'super_admin') {
                $affiliate = Affiliate::find($affiliateId);

                if ($affiliate) {
                    $request->attributes->set('affiliate', $affiliate);
                    $request->attributes->set('affiliate_impersonation', true);

                    return $next($request);
                }
            }

            $request->session()->forget('affiliate_impersonation');
        }

        // 1) Preferred: explicit link
        $affiliate = Affiliate::query()
            ->where('external_user_id', $user->id)
            ->first();

        // 2) Fallback: match by email only if not linked yet (prevents overwriting)
        if (! $affiliate) {
            $affiliate = Affiliate::query()
                ->whereNull('external_user_id')
                ->where('email', $user->email)
                ->first();

            if ($affiliate) {
                $affiliate->external_user_id = $user->id;
                $affiliate->save();
            }
        }

        if ($affiliate) {
            $request->attributes->set('affiliate', $affiliate);
        }

        return $next($request);
    }
}
