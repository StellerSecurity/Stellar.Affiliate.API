<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class RequireAffiliateAdmin
{
    public function handle(Request $request, Closure $next): Response
    {
        $user = $request->user();

        if ($request->session()->has('affiliate_impersonation')) {
            return redirect()
                ->route('affiliate.dashboard')
                ->with('status', 'Exit the affiliate view before opening the admin center.');
        }

        if (! $user || ! $user->hasAffiliateAdminAccess()) {
            abort(403, 'Affiliate admin access required.');
        }

        return $next($request);
    }
}
