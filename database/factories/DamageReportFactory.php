<?php

namespace Database\Factories;

use App\Enums\DamageSeverity;
use App\Enums\DamageStatus;
use App\Models\DamageReport;
use App\Models\Equipment;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<DamageReport>
 */
class DamageReportFactory extends Factory
{
    public function definition(): array
    {
        return [
            'equipment_id' => Equipment::factory(),
            'reservation_id' => null,
            'reported_by' => User::factory(),
            'description' => fake()->sentence(),
            'severity' => DamageSeverity::Minor,
            'status' => DamageStatus::Open,
        ];
    }
}
