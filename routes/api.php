<?php

use Illuminate\Support\Facades\Route;

use App\Http\Controllers\Api\LoginController;
use App\Http\Controllers\Api\AffiliateDashboardController;
use App\Http\Controllers\Api\AffiliateTrackingController;
use App\Http\Controllers\Api\AffiliateEventController;
use App\Http\Controllers\Api\AffiliateCampaignController;
use App\Http\Controllers\Api\AffiliatePayoutController;

/*
|--------------------------------------------------------------------------
| API Routes v1
|--------------------------------------------------------------------------
*/

// Public login for affiliates (no basic auth)
Route::prefix('v1')->group(function () {
    Route::post('/auth/login', [LoginController::class, 'login'])
        ->name('affiliate.auth.login');
});

// Internal / service-to-service (Basic Auth)
Route::prefix('v1')
    ->middleware('affiliate.basic')
    ->group(function () {
        // Tracking via redirect (if you front this behind something internal)
        Route::get('/r/{code}', [AffiliateTrackingController::class, 'redirect'])
            ->name('affiliate.track');

        // Billing -> Affiliate event (order paid)
        Route::post(
            '/affiliate/events/order-paid',
            [AffiliateEventController::class, 'handleOrderPaid']
        )->name('affiliate.events.order_paid');
    });

// Affiliate portal (Angular) protected by Bearer token
Route::prefix('v1/affiliate')
    ->middleware('affiliate.token')
    ->group(function () {
        Route::get('/dashboard', [AffiliateDashboardController::class, 'overview'])
            ->name('affiliate.dashboard');

        Route::get('/campaigns', [AffiliateCampaignController::class, 'index'])
            ->name('affiliate.campaigns.index');

        Route::post('/campaigns', [AffiliateCampaignController::class, 'store'])
            ->name('affiliate.campaigns.store');

        Route::get('/payouts/summary', [AffiliatePayoutController::class, 'summary'])
            ->name('affiliate.payouts.summary');

        Route::get('/payouts/history', [AffiliatePayoutController::class, 'history'])
            ->name('affiliate.payouts.history');
    });
