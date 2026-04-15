<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use SimpleSoftwareIO\QrCode\Facades\QrCode;
use Illuminate\Support\Str;
use App\BarOrder;
use App\EventBarItem;

class BarOrderController extends Controller
{
    public function buy(Request $request)
    {
        $user = auth()->user();

        $item = EventBarItem::findOrFail($request->bar_item_id);

        $code = 'BAR-'.Str::upper(Str::random(8));

        $order = BarOrder::create([
            'user_id' => $user->id,
            'event_id' => $request->event_id,
            'bar_item_id' => $item->id,
            'quantity' => $request->qty,
            'price' => $item->price,
            'code' => $code
        ]);

        // ruta del QR
        $qrPath = 'qrs/bar_'.$order->id.'.svg';

        // generar QR en SVG (NO necesita imagick)
        $qr = QrCode::format('svg')
            ->size(300)
            ->generate($code);

        \Storage::disk('public')->put($qrPath, $qr);

        $order->qr_code = $qrPath;
        $order->save();

        return response()->json([
            'status' => 'success',
            'message' => 'Compra realizada',
            'order' => [
                'id' => $order->id,
                'code' => $order->code,
                'qr' => asset('storage/'.$order->qr_code)
            ]
        ]);
    }



    public function myOrders(Request $request)
    {
        $user = auth()->user();

        $orders = BarOrder::where('user_id', $user->id)
            ->with(['barItem', 'event'])
            ->orderByDesc('created_at')
            ->get()
            ->map(function ($order) {
                return [
                    'id'         => $order->id,
                    'code'       => $order->code,
                    'quantity'   => $order->quantity,
                    'price'      => $order->price,
                    'status'     => $order->status,
                    'created_at' => $order->created_at,
                    'qr_code'    => $order->qr_code
                        ? asset('storage/' . $order->qr_code)
                        : null,
                    'bar_item'   => $order->barItem ? [
                        'name'     => $order->barItem->name,
                        'category' => $order->barItem->category ?? null,
                    ] : null,
                    'event'      => $order->event ? [
                        'name' => $order->event->name,
                    ] : null,
                ];
            });

        return response()->json(['orders' => $orders]);
    }
}