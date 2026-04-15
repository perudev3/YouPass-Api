<?php

use Illuminate\Database\Seeder;
use App\User;
use App\Event;
use App\EventUserRole;

class EventUserRolesSeeder extends Seeder
{
    public function run()
    {
        // ─────────────────────────────
        // CREAR EVENTO
        // ─────────────────────────────
        $event = Event::create([
            'name' => 'Fiesta Demo',
            'description' => 'Evento de prueba QR',
            'date' => now(),
            'location' => 'Discoteca Test',
            'image' => 'test.jpg',
            'is_active' => 1
        ]);

        // ─────────────────────────────
        // USUARIO CLIENTE
        // ─────────────────────────────
        $cliente = User::create([
            'phone' => '999111111',
            'name' => 'Cliente Test',
            'email' => 'cliente@test.com',
            'birth_date' => '2000-01-01',
            'gender' => 'Hombre',
            'is_verified' => 1,
            'mood_partty' => 0
        ]);

        // ─────────────────────────────
        // USUARIO SCANNER PUERTA
        // ─────────────────────────────
        $puerta = User::create([
            'phone' => '999222222',
            'name' => 'Scanner Puerta',
            'email' => 'puerta@test.com',
            'birth_date' => '1995-01-01',
            'gender' => 'Hombre',
            'is_verified' => 1,
            'mood_partty' => 0
        ]);

        // ─────────────────────────────
        // USUARIO SCANNER BARRA
        // ─────────────────────────────
        $barra = User::create([
            'phone' => '999333333',
            'name' => 'Scanner Barra',
            'email' => 'barra@test.com',
            'birth_date' => '1998-01-01',
            'gender' => 'Mujer',
            'is_verified' => 1,
            'mood_partty' => 0
        ]);

        // ─────────────────────────────
        // ASIGNAR ROLES
        // ─────────────────────────────
        EventUserRole::create([
            'user_id' => $cliente->id,
            'event_id' => $event->id,
            'role' => 'cliente'
        ]);

        EventUserRole::create([
            'user_id' => $puerta->id,
            'event_id' => $event->id,
            'role' => 'scanner_puerta'
        ]);

        EventUserRole::create([
            'user_id' => $barra->id,
            'event_id' => $event->id,
            'role' => 'scanner_barra'
        ]);
    }
}
