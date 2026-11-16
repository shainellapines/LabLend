<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    use WithoutModelEvents;

    /**
     * php artisan migrate:fresh --seed
     * Order matters: equipment needs categories, reservations need users/equipment/rooms.
     */
    public function run(): void
    {
        $this->call([
            UserSeeder::class,
            CategorySeeder::class,
            EquipmentSeeder::class,
            RoomSeeder::class,
            ReservationSeeder::class,
        ]);
    }
}
