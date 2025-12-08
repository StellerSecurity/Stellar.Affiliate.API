<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Api\AffiliateTrackingController;


Route::get('/', function () {
    return "affiliate";
});



// Public affiliate tracking – no /api, no /v1
//
// Eksempel:

