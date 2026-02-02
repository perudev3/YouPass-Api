<?php

namespace App\Http\Controllers;

use App\Ticket;
use App\TicketType;
use App\Order;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use SimpleSoftwareIO\QrCode\Facades\QrCode;

class TicketController extends Controller
{

    public function buy(Request $request)
    {
        $request->validate([
            'event_id' => 'required|exists:events,id',
            'items' => 'required|array|min:1'
        ]);

        // 🔐 PASSPORT USER
        $user = auth()->user();

        return DB::transaction(function () use ($request, $user) {

            $total = 0;

            /* =========================
               VALIDAR STOCK
            ========================= */
            foreach ($request->items as $ticketTypeId => $qty) {

                $ticketType = TicketType::lockForUpdate()->findOrFail($ticketTypeId);

                if ($ticketType->stock < $qty) {
                    abort(400, "Stock insuficiente para {$ticketType->name}");
                }

                $total += $ticketType->price * $qty;
            }

            /* =========================
               CREAR ORDEN
            ========================= */
            $order = Order::create([
                'user_id' => $user->id,
                'event_id' => $request->event_id,
                'total' => $total,
                'status' => 'paid'
            ]);

            $tickets = [];

            /* =========================
               CREAR TICKETS
            ========================= */
            foreach ($request->items as $ticketTypeId => $qty) {

                $ticketType = TicketType::find($ticketTypeId);

                for ($i = 0; $i < $qty; $i++) {

                    $code = strtoupper(Str::random(10));

                    $ticket = Ticket::create([
                        'user_id' => $user->id,
                        'event_id' => $request->event_id,
                        'ticket_type_id' => $ticketType->id,
                        'order_id' => $order->id,
                        'code' => $code,
                        'status' => 'valid'
                    ]);


                    $tickets[] = $ticket;
                }

                // descontar stock
                $ticketType->decrement('stock', $qty);
            }

            return response()->json([
                'message' => 'Compra realizada con éxito',
                'order' => $order,
                'tickets' => $tickets,
                'success' => true,
            ], 201);
        });
    }

    public function myTickets()
    {
        $user = auth()->user(); // 🔐 Passport

        $tickets = Ticket::with([
                'event:id,name,date,location,image',
                'ticketType:id,name'
            ])
            ->where('user_id', $user->id)
            ->orderBy('created_at', 'desc')
            ->get()
            ->map(function ($ticket) {
                return [
                    'id' => $ticket->id,
                    'code' => $ticket->code,
                    'status' => $ticket->status,
                    'qr' => $ticket->qr_path
                        ? asset('storage/' . $ticket->qr_path)
                        : null,

                    'event' => [
                        'name' => $ticket->event->name,
                        'date' => $ticket->event->date,
                        'location' => $ticket->event->location,
                        'image' => $ticket->event->image,
                    ],

                    'ticket_type' => $ticket->ticketType->name,
                ];
            });

        return response()->json($tickets);
    }

    public function validateTicket(Request $request)
    {
        $request->validate([
            'code' => 'required|string'
        ]);

        $ticket = Ticket::where('code', $request->code)->first();

        if (!$ticket) {
            return response()->json([
                'message' => 'Ticket no encontrado'
            ], 404);
        }

        if ($ticket->status !== 'valid') {
            return response()->json([
                'message' => 'Ticket ya usado o cancelado',
                'status' => $ticket->status
            ], 422);
        }

        $ticket->update([
            'status' => 'used',
            'used_at' => now()
        ]);

        return response()->json([
            'message' => 'Ticket validado correctamente',
            'ticket' => [
                'id' => $ticket->id,
                'event_id' => $ticket->event_id,
                'ticket_type_id' => $ticket->ticket_type_id,
                'used_at' => $ticket->used_at
            ]
        ]);
    }


}
