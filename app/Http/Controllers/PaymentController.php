<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Transbank\Webpay\WebpayPlus\Transaction;

class PaymentController extends Controller
{
    public function create(Request $request)
    {
        $user = auth()->user();

        $order = Order::create([
            'user_id' => $user->id,
            'event_id' => $request->event_id,
            'total' => $request->total,
            'status' => 'pending'
        ]);

        $buyOrder = 'ORD-' . $order->id;
        $sessionId = session()->getId();
        $amount = $order->total;
        $returnUrl = config('app.url') . '/api/payment/commit';

        $response = (new Transaction)->create(
            $buyOrder,
            $sessionId,
            $amount,
            $returnUrl
        );

        $order->update([
            'payment_reference' => $response->token
        ]);

        return response()->json([
            'url' => $response->url,
            'token' => $response->token,
            'order_id' => $order->id
        ]);
    }

    public function commit(Request $request)
    {
        $token = $request->input('token_ws');

        $response = (new Transaction)->commit($token);

        $order = Order::where('payment_reference', $token)->first();

        if ($response->isApproved()) {

            $order->update([
                'status' => 'paid'
            ]);

            // 🔥 AQUÍ recién creas tickets
            app(TicketController::class)->generateTicketsFromOrder($order);

            return redirect('https://tu-app.com/payment-success');

        } else {

            $order->update([
                'status' => 'failed'
            ]);

            return redirect('https://tu-app.com/payment-error');
        }
    }
}
