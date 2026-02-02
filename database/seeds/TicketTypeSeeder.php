<?php

use Illuminate\Database\Seeder;
use App\Event;
use App\TicketType;

class TicketTypeSeeder extends Seeder
{
    public function run(): void
    {
        $events = Event::all();

        foreach ($events as $event) {
            // Entrada General
            TicketType::create([
                'event_id' => $event->id,
                'name' => 'Entrada General',
                'price' => 80,
                'stock' => 300,
                'max_per_user' => 5,
                'is_active' => true,
            ]);

            // Entrada VIP
            TicketType::create([
                'event_id' => $event->id,
                'name' => 'Entrada VIP',
                'price' => 150,
                'stock' => 80,
                'max_per_user' => 2,
                'is_active' => true,
            ]);
        }
    }
}