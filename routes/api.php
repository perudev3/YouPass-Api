<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Auth\AuthController;

Route::prefix('auth')->group(function () {
    Route::post('/send-otp', [AuthController::class, 'sendOtp'])
        ->withoutMiddleware('throttle:api');

    Route::post('/verify-otp', [AuthController::class, 'verifyOtp'])
        ->withoutMiddleware('throttle:api');
});
