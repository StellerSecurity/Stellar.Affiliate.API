<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Api\AffiliateTrackingController;
use App\Http\Controllers\AffiliatePortalController;
use App\Http\Controllers\Admin\AuthController;
use Illuminate\Support\Facades\Artisan;


/*
|--------------------------------------------------------------------------
| Public Root
|--------------------------------------------------------------------------
*/
Route::get('/', function () {
    // Clear all relevant caches
    Artisan::call('optimize:clear');   // clears config, route, view, cache
    Artisan::call('cache:clear');      // app cache
    Artisan::call('route:clear');      // route cache
    Artisan::call('config:clear');     // config cache
    Artisan::call('view:clear');       // compiled views

    return 'kk';
});

/*
|--------------------------------------------------------------------------
| Affiliate Portal Authentication
|--------------------------------------------------------------------------
*/
Route::get('/affiliate/login', [AuthController::class, 'showLoginForm'])
    ->name('affiliate.login');



Route::post('/affiliate/login', [AuthController::class, 'login'])
    ->name('affiliate.login.post');

Route::post('/affiliate/logout', [AuthController::class, 'logout'])
    ->name('affiliate.logout');

/*
|--------------------------------------------------------------------------
| Protected Affiliate Portal (requires Login)
|--------------------------------------------------------------------------
*/
Route::middleware(['auth:web'])
    ->prefix('affiliate')
    ->group(function () {

        // Dashboard
        Route::get('/dashboard', [AffiliatePortalController::class, 'dashboard'])
            ->name('affiliate.dashboard');

        // Sales
        Route::get('/sales', [AffiliatePortalController::class, 'sales'])
            ->name('affiliate.sales');

        Route::get('/campaigns', [AffiliatePortalController::class, 'campaignsIndex'])
            ->name('affiliate.campaigns.index');

        Route::post('/campaigns', [AffiliatePortalController::class, 'campaignsStore'])
            ->name('affiliate.campaigns.store');


        // Payouts
        Route::get('/payouts', [AffiliatePortalController::class, 'payouts'])
            ->name('affiliate.payouts');

        // Analytics
        Route::get('/analytics', [AffiliatePortalController::class, 'analytics'])
            ->name('affiliate.analytics');

        // Settings
        Route::get('/settings', [AffiliatePortalController::class, 'settings'])
            ->name('affiliate.settings');

        // Clicks / Sessions / Commissions
        Route::get('/clicks', [AffiliatePortalController::class, 'clicks'])
            ->name('affiliate.clicks');

        Route::get('/sessions', [AffiliatePortalController::class, 'sessions'])
            ->name('affiliate.sessions');

        Route::get('/commissions', [AffiliatePortalController::class, 'commissions'])
            ->name('affiliate.commissions');

        /*
        |--------------------------------------------------------------------------
        | NEW: Affiliate Management (list + create)
        |--------------------------------------------------------------------------
        */
        Route::get('/affiliates', [AffiliatePortalController::class, 'affiliatesIndex'])
            ->name('affiliate.affiliates.index');

        Route::post('/affiliates', [AffiliatePortalController::class, 'affiliatesStore'])
            ->name('affiliate.affiliates.store');
    });

/*
|--------------------------------------------------------------------------
| Public Tracking Redirect
|--------------------------------------------------------------------------
|
| Example:
|   https://stellarafi.com/r/AFFCODE?src=youtube&campaign=review&product=vpn
|
*/
Route::get('/r/{code}', [AffiliateTrackingController::class, 'redirect'])
    ->name('affiliate.track.public');
