<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Api\AffiliateTrackingController;
use App\Http\Controllers\AffiliatePortalController;
use App\Http\Controllers\Admin\AuthController;

// Simple root check
Route::get('/', function () {
    return 'affiliate';
});

// Affiliate portal login / logout
Route::get('/affiliate/login', [AuthController::class, 'showLoginForm'])
    ->name('affiliate.login');

Route::post('/affiliate/login', [AuthController::class, 'login'])
    ->name('affiliate.login.post');

Route::post('/affiliate/logout', [AuthController::class, 'logout'])
    ->name('affiliate.logout');

// Protected affiliate portal (requires auth)
Route::middleware(['auth'])
    ->prefix('affiliate')
    ->group(function () {
        Route::get('/dashboard', [AffiliatePortalController::class, 'dashboard'])
            ->name('affiliate.dashboard');

        Route::get('/sales', [AffiliatePortalController::class, 'sales'])
            ->name('affiliate.sales');

        Route::get('/payouts', [AffiliatePortalController::class, 'payouts'])
            ->name('affiliate.payouts');

        Route::get('/analytics', [AffiliatePortalController::class, 'analytics'])
            ->name('affiliate.analytics');

        Route::get('/settings', [AffiliatePortalController::class, 'settings'])
            ->name('affiliate.settings');

        // Internal overview pages
        Route::get('/clicks', [AffiliatePortalController::class, 'clicks'])
            ->name('affiliate.clicks');

        Route::get('/sessions', [AffiliatePortalController::class, 'sessions'])
            ->name('affiliate.sessions');

        Route::get('/commissions', [AffiliatePortalController::class, 'commissions'])
            ->name('affiliate.commissions');
    });

// Public affiliate tracking – no /api, no /v1
// Example: https://stellarafi.com/r/AFF123?src=youtube&campaign=review_oct&product=vpn
Route::get('/r/{code}', [AffiliateTrackingController::class, 'redirect'])
    ->name('affiliate.track.public');
