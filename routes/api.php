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

// 🔓 Public endpoints (no auth)
Route::prefix('v1')->group(function () {
    // Affiliate login (email + password → api token)
    Route::post('/auth/login', [LoginController::class, 'login'])
        ->name('affiliate.auth.login');

//   https://stellarafi.com/api/v1/r/AFF123?src=youtube&campaign=review_oct&product=vpn
    Route::get('/r/{code}', [AffiliateTrackingController::class, 'redirect'])
        ->name('affiliate.track.public');
});

// 🔐 Internal service-to-service (Basic Auth with API_USERNAME / API_PASSWORD)
Route::prefix('v1')
    ->middleware('affiliate.basic')
    ->group(function () {
        // Billing → Affiliate event: order paid
        //
        // Billing sends a normalized JSON payload here when an order is paid.
        Route::post(
            '/affiliate/events/order-paid',
            [AffiliateEventController::class, 'handleOrderPaid']
        )->name('affiliate.events.order_paid');
    });

// 🔐 Affiliate portal (Angular) – Bearer token auth (AffiliateTokenAuth)
Route::prefix('v1/affiliate')
    ->middleware('affiliate.token')
    ->group(function () {
        // Dashboard overview
        // GET /api/v1/affiliate/dashboard
        Route::get('/dashboard', [AffiliateDashboardController::class, 'overview'])
            ->name('affiliate.dashboard');

        // Campaigns
        // GET  /api/v1/affiliate/campaigns
        // POST /api/v1/affiliate/campaigns
        Route::get('/campaigns', [AffiliateCampaignController::class, 'index'])
            ->name('affiliate.campaigns.index');

        Route::post('/campaigns', [AffiliateCampaignController::class, 'store'])
            ->name('affiliate.campaigns.store');

        // Payouts
        // GET /api/v1/affiliate/payouts/summary
        // GET /api/v1/affiliate/payouts/history
        Route::get('/payouts/summary', [AffiliatePayoutController::class, 'summary'])
            ->name('affiliate.payouts.summary');

        Route::get('/payouts/history', [AffiliatePayoutController::class, 'history'])
            ->name('affiliate.payouts.history');
    });
