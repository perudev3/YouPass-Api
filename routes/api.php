<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Auth\AuthController;
use App\Http\Controllers\TicketController;
use App\Http\Controllers\EventController;

Route::prefix('auth')->group(function () {

    Route::post('/send-otp', [AuthController::class, 'sendOtp'])
        ->withoutMiddleware('throttle:api');

    Route::post('/verify-otp', [AuthController::class, 'verifyOtp'])
        ->withoutMiddleware('throttle:api');

    Route::middleware(['auth:api', 'throttle:10,1'])->group(function () {

        Route::post('/register-profile', [AuthController::class, 'registerProfile']);
        Route::get('/me', [AuthController::class, 'MeProfile']);

        Route::get('/events/{event}', [EventController::class, 'show']);

        Route::post('/purchases', [TicketController::class, 'buy'])
            ->withoutMiddleware('throttle:10,1');

        Route::get('/my-tickets', [TicketController::class, 'myTickets'])
            ->withoutMiddleware('throttle:10,1');;

        Route::post('/tickets/validate', [TicketController::class, 'validateTicket']);

    });
});
