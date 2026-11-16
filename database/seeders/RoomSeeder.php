<?php

namespace Database\Seeders;

use App\Enums\RoomType;
use App\Models\Room;
use Illuminate\Database\Seeder;

class RoomSeeder extends Seeder
{
    public function run(): void
    {
        $rooms = [
            ['Computer Lab 1', 'LAB-1', RoomType::ComputerLab, 40, 'IT Building, 2nd floor', '40 desktop PCs, projector, aircon.'],
            ['Computer Lab 2', 'LAB-2', RoomType::ComputerLab, 35, 'IT Building, 2nd floor', '35 desktop PCs, smart TV.'],
            ['Audio-Visual Room', 'AVR', RoomType::Avr, 120, 'Main Building, ground floor', 'Stage, sound system, and projector.'],
            ['Room 301', 'RM-301', RoomType::Classroom, 45, 'IT Building, 3rd floor', 'Regular classroom with whiteboard.'],
        ];

        foreach ($rooms as [$name, $code, $type, $capacity, $location, $description]) {
            Room::factory()->create(compact('name', 'code', 'type', 'capacity', 'location', 'description'));
        }
    }
}
