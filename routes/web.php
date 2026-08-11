<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Api\AffiliateTrackingController;
use App\Http\Controllers\AffiliatePortalController;
use App\Http\Controllers\AffiliateAdminController;
use App\Http\Controllers\Admin\AuthController;

/*
|--------------------------------------------------------------------------
| Public Root
|--------------------------------------------------------------------------
*/
Route::get('/', function () {
    return redirect()->route('affiliate.dashboard');
});

/*
|--------------------------------------------------------------------------
| Affiliate Portal Authentication
|--------------------------------------------------------------------------
*/
Route::get('/affiliate/login', [AuthController::class, 'showLoginForm'])
    ->name('affiliate.login');

Route::post('/affiliate/login', [AuthController::class, 'login'])
    ->middleware('throttle:10,1')
    ->name('affiliate.login.post');

// Self-registration (optional, can be disabled via AFFILIATE_SELF_REGISTER_ENABLED=false)
Route::get('/affiliate/register', [AuthController::class, 'showRegisterForm'])
    ->name('affiliate.register');

Route::post('/affiliate/register', [AuthController::class, 'register'])
    ->middleware('throttle:5,1')
    ->name('affiliate.register.post');

Route::get('/login', function () {
    return redirect()->route('affiliate.login');
})->name('login');

Route::get('/register', function () {
    return redirect()->route('affiliate.register');
})->name('register');

Route::post('/affiliate/logout', [AuthController::class, 'logout'])
    ->name('affiliate.logout');


/*
|--------------------------------------------------------------------------
| Affiliate Administration
|--------------------------------------------------------------------------
*/
Route::middleware(['auth:web', 'affiliate.admin'])
    ->prefix('affiliate/admin')
    ->name('affiliate.admin.')
    ->group(function () {
        Route::get('/', [AffiliateAdminController::class, 'dashboard'])->name('dashboard');

        Route::get('/affiliates', [AffiliateAdminController::class, 'affiliatesIndex'])->name('affiliates.index');
        Route::post('/affiliates', [AffiliateAdminController::class, 'affiliateStore'])->name('affiliates.store');
        Route::get('/affiliates/{affiliate}', [AffiliateAdminController::class, 'affiliateShow'])->name('affiliates.show');
        Route::post('/affiliates/{affiliate}/view-as-affiliate', [AffiliateAdminController::class, 'startAffiliateView'])->name('affiliates.view-as');
        Route::patch('/affiliates/{affiliate}', [AffiliateAdminController::class, 'affiliateUpdate'])->name('affiliates.update');
        Route::put('/affiliates/{affiliate}/rates', [AffiliateAdminController::class, 'affiliateRateUpdate'])->name('affiliates.rates.update');
        Route::delete('/affiliates/{affiliate}/rates', [AffiliateAdminController::class, 'affiliateRateDelete'])->name('affiliates.rates.delete');

        Route::get('/rates', [AffiliateAdminController::class, 'ratesIndex'])->name('rates.index');
        Route::put('/rates', [AffiliateAdminController::class, 'globalRateUpdate'])->name('rates.update');

        Route::get('/commissions', [AffiliateAdminController::class, 'commissionsIndex'])->name('commissions.index');
        Route::patch('/commissions/{commission}/status', [AffiliateAdminController::class, 'commissionStatusUpdate'])->name('commissions.status');
        Route::patch('/commissions/status/bulk', [AffiliateAdminController::class, 'commissionsBulkStatusUpdate'])->name('commissions.bulk-status');

        Route::get('/campaigns', [AffiliateAdminController::class, 'campaignsIndex'])->name('campaigns.index');
        Route::get('/tracking', [AffiliateAdminController::class, 'trackingIndex'])->name('tracking.index');

        Route::get('/payouts', [AffiliateAdminController::class, 'payoutsIndex'])->name('payouts.index');
        Route::patch('/payouts/{payout}/status', [AffiliateAdminController::class, 'payoutStatusUpdate'])->name('payouts.status');

        Route::get('/roles', [AffiliateAdminController::class, 'usersIndex'])->name('users.index');
        Route::patch('/roles/{user}', [AffiliateAdminController::class, 'userRoleUpdate'])->name('users.role');
    });

Route::post('/affiliate/impersonation/stop', [AffiliateAdminController::class, 'stopAffiliateView'])
    ->middleware('auth:web')
    ->name('affiliate.impersonation.stop');


/*
|--------------------------------------------------------------------------
| Protected Affiliate Portal (requires Login)
|--------------------------------------------------------------------------
|
| IMPORTANT:
| - auth:web ensures session login
| - resolve.affiliate attaches current affiliate to Request attributes
|
*/
Route::middleware(['auth:web', 'resolve.affiliate'])
    ->prefix('affiliate')
    ->group(function () {

        // Guided setup
        Route::get('/onboarding', [AffiliatePortalController::class, 'onboarding'])
            ->name('affiliate.onboarding');

        // Dashboard
        Route::get('/dashboard', [AffiliatePortalController::class, 'dashboard'])
            ->name('affiliate.dashboard');

        // Sales
        Route::get('/sales', [AffiliatePortalController::class, 'sales'])
            ->name('affiliate.sales');

        Route::get('/conversions/{commission}/order', [AffiliatePortalController::class, 'orderShow'])
            ->whereNumber('commission')
            ->middleware('throttle:60,1')
            ->name('affiliate.orders.show');

        // Campaigns
        Route::get('/campaigns', [AffiliatePortalController::class, 'campaignsIndex'])
            ->name('affiliate.campaigns.index');

        Route::post('/campaigns', [AffiliatePortalController::class, 'campaignsStore'])
            ->name('affiliate.campaigns.store');

        Route::patch('/campaigns/{campaign}', [AffiliatePortalController::class, 'campaignUpdate'])
            ->whereNumber('campaign')
            ->name('affiliate.campaigns.update');

        Route::patch('/campaigns/{campaign}/destination', [AffiliatePortalController::class, 'campaignDestinationUpdate'])
            ->whereNumber('campaign')
            ->name('affiliate.campaigns.destination.update');

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
        | Affiliate Management (admin-style)
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
    ->middleware('affiliate.track.prepare')
    ->name('affiliate.track.public');


