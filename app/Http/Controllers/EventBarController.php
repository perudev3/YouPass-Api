<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Controller;
use App\EventBarItem;
use Illuminate\Http\Request;

class EventBarController extends Controller
{

    public function items($eventId, $category)
    {
        $items = EventBarItem::where('event_id', $eventId)
            ->where('category', $category)
            ->where('available', 1)
            ->get();

        return response()->json($items);
    }

}