<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Api\AffiliateTrackingController;


Route::get('/', function () {
    return "affiliate";
});



// Public affiliate tracking – no /api, no /v1
//
//   https://stellarafi.com/r/AFF123?src=youtube&campaign=review_oct&product=vpn
Route::get('/r/{code}', [AffiliateTrackingController::class, 'redirect'])
    ->name('affiliate.track.public');
