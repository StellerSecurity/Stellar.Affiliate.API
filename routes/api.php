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
| Versioned v1 + protected by Basic Auth (API_USERNAME / API_PASSWORD)
|--------------------------------------------------------------------------
*/

Route::prefix('v1')
    ->middleware('affiliate.basic')
    ->group(function () {

        /**
         * Public tracking endpoint (now protected by Basic Auth too)
         *
         * Example:
         *   https://affiliate.stellar/api/v1/r/BLERIM01?src=youtube&campaign=yt_review&sub1=video
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
         */
        Route::post(
            '/affiliate/events/order-paid',
            [AffiliateEventController::class, 'handleOrderPaid']
        )->name('affiliate.events.order_paid');

        /**
         * Protected affiliate API (dashboard, campaigns, payouts, etc.)
         * All behind Basic Auth for now.
         *
         * Prefix: /api/v1/affiliate/...
         */
        Route::prefix('affiliate')->group(function () {
            /**
             * Dashboard overview
             *
             * GET /api/v1/affiliate/dashboard
             */
            Route::get('/dashboard', [AffiliateDashboardController::class, 'overview'])
                ->name('affiliate.dashboard');

            /**
             * Campaigns
             *
             * GET  /api/v1/affiliate/campaigns   -> index (list campaigns)
             * POST /api/v1/affiliate/campaigns   -> store (create campaign)
             */
            Route::get('/campaigns', [AffiliateCampaignController::class, 'index'])
                ->name('affiliate.campaigns.index');

            Route::post('/campaigns', [AffiliateCampaignController::class, 'store'])
                ->name('affiliate.campaigns.store');

            /**
             * Payouts
             *
             * GET /api/v1/affiliate/payouts/summary -> overview (available balance, total paid, next payout)
             * GET /api/v1/affiliate/payouts/history -> paginated list of payouts
             */
            Route::get('/payouts/summary', [AffiliatePayoutController::class, 'summary'])
                ->name('affiliate.payouts.summary');

            Route::get('/payouts/history', [AffiliatePayoutController::class, 'history'])
                ->name('affiliate.payouts.history');
        });
    });
