<?php

namespace App\Http\Middleware;

use app\Models\Affiliate;
use App\Models\User;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class AffiliateTokenAuth
{
    /**
     * Authenticate affiliate using Bearer token stored on users.api_token.
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

        /** @var \App\Models\User|null $user */
        $user = User::where('api_token', $hashed)->first();

        if (! $user) {
            return response()->json(['message' => 'Unauthorized'], 401);
        }

        /** @var \app\Models\Affiliate|null $affiliate */
        $affiliate = Affiliate::where('external_user_id', $user->id)->first();

        if (! $affiliate) {
            return response()->json(['message' => 'Affiliate not found for user'], 403);
        }

        // Attach user & affiliate to the request for controllers to use
        $request->setUserResolver(fn () => $user);
        $request->attributes->set('affiliate', $affiliate);

        return $next($request);
    }
}
