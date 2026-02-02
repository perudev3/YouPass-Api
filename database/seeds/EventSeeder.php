<?php

use Illuminate\Database\Seeder;

use App\Event;
use Faker\Factory as Faker;
use Carbon\Carbon;

class EventSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $faker = Faker::create();

        // Crear 20 eventos de prueba
        for ($i = 1; $i <= 20; $i++) {
            Event::create([
                'name' => "Evento de prueba $i - " . $faker->sentence(3),
                'description' => $faker->paragraph(2),
                'date' => Carbon::now()->addDays(rand(1, 60)), // fechas futuras
                'location' => $faker->city,
                'image' => $faker->imageUrl(640, 480, 'nightlife', true),
                'is_active' => true,
            ]);
        }
    }
}
