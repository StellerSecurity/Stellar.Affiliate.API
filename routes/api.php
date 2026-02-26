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

// Public affiliate auth (token-based)
Route::prefix('v1')->group(function () {
    Route::post('/auth/register', [LoginController::class, 'register'])
        ->middleware('throttle:10,1')
        ->name('affiliate.auth.register');

    Route::post('/auth/login', [LoginController::class, 'login'])
        ->middleware('throttle:20,1')
        ->name('affiliate.auth.login');
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


