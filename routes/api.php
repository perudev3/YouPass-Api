<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Auth\AuthController;
use App\Http\Controllers\TicketController;
use App\Http\Controllers\EventsController;
use App\Http\Controllers\InvitationController;
use App\Http\Controllers\BarOrderController;
use App\Http\Controllers\CardController;

Route::middleware('auth:api')->get(
    '/auth/event-bar/{event}/{category}',
    'EventBarController@items'
);

Route::post('/flow/confirm', [TicketController::class, 'flowConfirm']);
Route::get('/flow/return',   [TicketController::class, 'flowReturn']);

Route::prefix('auth')->group(function () {

    Route::post('/send-otp', [AuthController::class, 'sendOtp'])
        ->withoutMiddleware('throttle:api');

    Route::post('/verify-otp', [AuthController::class, 'verifyOtp'])
        ->withoutMiddleware('throttle:api');

    Route::post('/invite/{token}', [EventsController::class, 'claim'])
        ->withoutMiddleware('throttle:api');

    Route::middleware(['auth:api', 'throttle:60,1'])->group(function () {

        Route::post('/register-profile', [AuthController::class, 'registerProfile']);
        Route::get('/me', [AuthController::class, 'MeProfile']);        
        Route::post('/profile', [AuthController::class, 'updateProfile']);

        Route::post('/create-transaction', [TicketController::class, 'createTransaction'])->withoutMiddleware('throttle:10,1');

        Route::post('/purchases', [TicketController::class, 'buy'])
            ->withoutMiddleware('throttle:10,1');

        Route::get('/my-tickets', [TicketController::class, 'myTickets'])
            ->withoutMiddleware('throttle:10,1');        

        Route::post('/tickets/validate', [TicketController::class, 'validateTicket']);

        Route::get('/events', [EventsController::class, 'index']);

        Route::get('/events/{event}', [EventsController::class, 'show']);

        Route::post('/mood_partty/status', [AuthController::class, 'MoodPartty']);

        Route::get('/tickets/{ticketId}/invitations', [InvitationController::class, 'index']);
        Route::post('/invitations/{id}/assign', [InvitationController::class, 'assign']);
        Route::get('/my-invitations', [InvitationController::class, 'myInvitations'])
            ->withoutMiddleware('throttle:10,1');

        Route::post('/invitations/{id}/respond', [InvitationController::class, 'respond'])
            ->withoutMiddleware('throttle:10,1');

        Route::post('/bar/buy',[BarOrderController::class,'buy']);

        Route::get('/bar-orders/my', [BarOrderController::class, 'myOrders']);

        Route::get('/cards', [CardController::class, 'index']);
        Route::post('/cards', [CardController::class, 'store']);
        Route::delete('/cards/{id}', [CardController::class, 'destroy']);

        Route::get('/my-role', [AuthController::class, 'myRole']);

    });
});
