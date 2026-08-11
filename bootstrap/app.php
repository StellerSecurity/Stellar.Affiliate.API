<?php

use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;
use Illuminate\Http\Request;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        api: __DIR__.'/../routes/api.php',
        commands: __DIR__.'/../routes/console.php',
        health: '/up',
    )
    ->withMiddleware(function (Middleware $middleware): void {
        $trustedProxies = (string) env('TRUSTED_PROXIES', '*');
        $trustedProxyAddresses = $trustedProxies === '*'
            ? '*'
            : array_values(array_filter(array_map('trim', explode(',', $trustedProxies))));

        $middleware->trustProxies(
            at: $trustedProxyAddresses,
            headers: Request::HEADER_X_FORWARDED_FOR
                | Request::HEADER_X_FORWARDED_PORT
                | Request::HEADER_X_FORWARDED_PROTO
                | Request::HEADER_X_FORWARDED_AWS_ELB
        );

        $middleware->alias([
            'affiliate.basic' => \App\Http\Middleware\AffiliateBasicAuth::class,
            'affiliate.token' => \App\Http\Middleware\AffiliateTokenAuth::class,
            'resolve.affiliate' => \App\Http\Middleware\ResolveAffiliateFromAuthUser::class,
            'affiliate.admin' => \App\Http\Middleware\RequireAffiliateAdmin::class,
            'affiliate.track.prepare' => \App\Http\Middleware\PrepareAffiliateTrackingRequest::class,
        ]);

        $middleware->appendToGroup('api', \App\Http\Middleware\ApplyAffiliateCommissionPolicy::class);
    })
    ->withExceptions(function (Exceptions $exceptions): void {
        //
    })->create();
