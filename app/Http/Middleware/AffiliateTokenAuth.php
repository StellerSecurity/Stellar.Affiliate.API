<?php

namespace App\Http\Middleware;

use App\Models\Affiliate;
use App\Models\User;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class AffiliateTokenAuth
{
    /**
     * Authenticate affiliate using Bearer token stored on users.api_token.
     * Also auto-links Affiliate.external_user_id via email fallback if needed.
     */
    public function handle(Request $request, Closure $next): Response
    {
        $authHeader = $request->header('Authorization');

        if (! $authHeader || ! str_starts_with($authHeader, 'Bearer ')) {
            return response()->json(['message' => 'Unauthorized'], 401);
        }

        $token = trim(substr($authHeader, 7));

        if ($token === '') {
            return response()->json(['message' => 'Unauthorized'], 401);
        }

        $hashed = hash('sha256', $token);

        /** @var User|null $user */
        $user = User::where('api_token', $hashed)->first();

        if (! $user) {
            return response()->json(['message' => 'Unauthorized'], 401);
        }

        // 1) Normal path: already linked
        $affiliate = Affiliate::where('external_user_id', $user->id)->first();

        // 2) Fallback: match by email and auto-link
        if (! $affiliate && isset($user->email) && $user->email) {
            $affiliate = Affiliate::whereNull('external_user_id')
                ->where('email', $user->email)
                ->first();

            if ($affiliate) {
                $affiliate->external_user_id = (int) $user->id;
                $affiliate->save();
            }
        }

        if (! $affiliate) {
            return response()->json(['message' => 'Affiliate not found for user'], 403);
        }

        // Attach user & affiliate to the request for controllers to use
        $request->setUserResolver(fn () => $user);
        $request->attributes->set('affiliate', $affiliate);

        return $next($request);
    }
}
