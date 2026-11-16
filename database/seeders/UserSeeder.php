<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;

/**
 * Demo accounts. Every password is "password" (listed in README.md).
 */
class UserSeeder extends Seeder
{
    public function run(): void
    {
        User::factory()->admin()->create([
            'name' => 'Lab Custodian',
            'email' => 'admin@lablend.test',
            'id_number' => 'EMP-0001',
            'department' => 'IT Department',
        ]);

        $faculty = [
            ['Maria Santos', 'faculty@lablend.test', 'EMP-0101'],
            ['Jose Reyes', 'faculty2@lablend.test', 'EMP-0102'],
        ];
        foreach ($faculty as [$name, $email, $idNumber]) {
            User::factory()->faculty()->create([
                'name' => $name, 'email' => $email, 'id_number' => $idNumber, 'department' => 'IT Department',
            ]);
        }

        $students = [
            ['Juan Dela Cruz', 'student@lablend.test', '2024-00001'],
            ['Angela Mendoza', 'student2@lablend.test', '2024-00002'],
            ['Mark Villanueva', 'student3@lablend.test', '2023-00015'],
            ['Kristine Garcia', 'student4@lablend.test', '2023-00027'],
            ['Paolo Ramos', 'student5@lablend.test', '2022-00031'],
        ];
        foreach ($students as [$name, $email, $idNumber]) {
            User::factory()->create([
                'name' => $name, 'email' => $email, 'id_number' => $idNumber, 'department' => 'BSIT',
            ]);
        }
    }
}
