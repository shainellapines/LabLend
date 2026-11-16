<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    use WithoutModelEvents;

    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        // Temporary demo accounts (password: "password"). The full UserSeeder comes in step 1.5.
        User::factory()->admin()->create([
            'name' => 'Lab Custodian',
            'email' => 'admin@lablend.test',
            'id_number' => 'EMP-0001',
            'department' => 'IT Department',
        ]);

        User::factory()->create([
            'name' => 'Student Demo',
            'email' => 'student@lablend.test',
            'id_number' => '2024-00001',
            'department' => 'BSIT',
        ]);
    }
}
