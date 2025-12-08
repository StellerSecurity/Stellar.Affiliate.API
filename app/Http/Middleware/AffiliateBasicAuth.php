<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class AffiliateBasicAuth
{
    /**
     * Simple HTTP Basic Auth using API_USERNAME and API_PASSWORD from .env
     */
    public function handle(Request $request, Closure $next): Response
    {
        $username = $request->getUser();
        $password = $request->getPassword();

        $expectedUser = env('API_USERNAME');
        $expectedPass = env('API_PASSWORD');

        if (
            ! $username ||
            ! $password ||
            $expectedUser === null ||
            $expectedPass === null ||
            $username !== $expectedUser ||
            ! hash_equals($expectedPass, $password)
        ) {
            return response('Unauthorized', 401, [
                'WWW-Authenticate' => 'Basic realm="Stellar Affiliate API"',
            ]);
        }

        return $next($request);
    }
}
