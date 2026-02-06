<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Event;

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

        return response()->json($event);
    }

}
