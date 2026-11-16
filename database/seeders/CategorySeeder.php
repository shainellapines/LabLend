<?php

namespace Database\Seeders;

use App\Models\Category;
use Illuminate\Database\Seeder;

class CategorySeeder extends Seeder
{
    public function run(): void
    {
        $categories = [
            ['Projectors', 'PRJ', 'Multimedia projectors for classes and presentations.'],
            ['Laptops', 'LAP', 'Laptops for presentations, defenses, and lab work.'],
            ['Cables & Adapters', 'CBL', 'HDMI/VGA cables, USB-C adapters, and dongles.'],
            ['Audio', 'AUD', 'Speakers and microphones.'],
            ['Extension Cords', 'EXT', 'Extension cords and power strips.'],
            ['Networking', 'NET', 'Routers, switches, and network testers.'],
        ];

        foreach ($categories as [$name, $code, $description]) {
            Category::factory()->create(compact('name', 'code', 'description'));
        }
    }
}
