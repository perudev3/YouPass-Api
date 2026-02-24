<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Event;
use App\Ticket;

class EventsController extends Controller
{
    public function index()
    {
        return Event::latest()->get();
    }

    public function show($id)
    {
        $event = Event::with('ticketTypes')->find($id);

        if (!$event) {
            return response()->json([
                'message' => 'Evento no encontrado'
            ], 404);
        }

        // 🔥 Asientos ya comprados para este evento
        $soldSeats = Ticket::where('event_id', $id)
            ->whereNotNull('seat_id')
            ->whereIn('status', ['valid', 'used'])  // 🔥 'valid' en vez de 'active'
            ->pluck('seat_id')
            ->toArray();

        $data = $event->toArray();
        $data['sold_seats'] = $soldSeats;

        return response()->json($data);
    }

}
