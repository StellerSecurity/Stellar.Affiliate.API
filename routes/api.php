<?php

use Illuminate\Support\Facades\Route;

use App\Http\Controllers\Api\AffiliateDashboardController;
use App\Http\Controllers\Api\AffiliateTrackingController;
use App\Http\Controllers\Api\AffiliateEventController;
use App\Http\Controllers\Api\AffiliateCampaignController;
use App\Http\Controllers\Api\AffiliatePayoutController;

/*
|--------------------------------------------------------------------------
| API Routes
|--------------------------------------------------------------------------
| These routes are loaded by the RouteServiceProvider within a group
| which is assigned the "api" middleware group. Enjoy building your API!
*/

/**
 * Public tracking endpoint
 *
 * Example:
 *   https://affiliate.stellar/api/r/BLERIM01?src=youtube&campaign=yt_review&sub1=video
 *
 * This will:
 *  - log the click
 *  - create an affiliate session
 *  - set the 180-day cookie
 *  - redirect to your main app URL (inside controller)
 */
Route::get('/r/{code}', [AffiliateTrackingController::class, 'redirect'])
    ->name('affiliate.track');

/**
 * Webhook / event endpoint from billing microservice
 *
 * Billing normalizes App Store / Play Store / Stripe / etc.
 * and then POST'er her når en ordre er betalt.
 *
 * TODO: add signature / shared-secret middleware later.
 */
Route::post('/affiliate/events/order-paid', [AffiliateEventController::class, 'handleOrderPaid'])
    ->name('affiliate.events.order_paid');


// Protected affiliate API (dashboard, campaigns, payouts, etc.)
// Later you can swap 'auth:sanctum' with whatever guard you use.
Route::middleware(['auth:sanctum'])->prefix('affiliate')->group(function () {
    /**
     * Dashboard overview
     *
     * GET /api/affiliate/dashboard
     */
    Route::get('/dashboard', [AffiliateDashboardController::class, 'overview'])
        ->name('affiliate.dashboard');

    /**
     * Campaigns
     *
     * GET  /api/affiliate/campaigns   -> index (list campaigns)
     * POST /api/affiliate/campaigns   -> store (create campaign)
     */
    Route::get('/campaigns', [AffiliateCampaignController::class, 'index'])
        ->name('affiliate.campaigns.index');

    Route::post('/campaigns', [AffiliateCampaignController::class, 'store'])
        ->name('affiliate.campaigns.store');

    /**
     * Payouts
     *
     * GET /api/affiliate/payouts/summary -> overview (available balance, total paid, next payout)
     * GET /api/affiliate/payouts/history -> paginated list of payouts
     */
    Route::get('/payouts/summary', [AffiliatePayoutController::class, 'summary'])
        ->name('affiliate.payouts.summary');

    Route::get('/payouts/history', [AffiliatePayoutController::class, 'history'])
        ->name('affiliate.payouts.history');
});
