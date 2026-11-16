<?php

namespace Database\Factories;

use App\Enums\EquipmentCondition;
use App\Enums\EquipmentStatus;
use App\Models\Category;
use App\Models\Equipment;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Equipment>
 */
class EquipmentFactory extends Factory
{
    public function definition(): array
    {
        return [
            'category_id' => Category::factory(),
            'asset_tag' => strtoupper(fake()->unique()->bothify('???-####')),
            'name' => ucfirst(fake()->words(2, true)),
            'brand' => fake()->randomElement(['Epson', 'Acer', 'Lenovo', 'JBL', 'Ugreen', 'TP-Link']),
            'model' => strtoupper(fake()->bothify('??-###')),
            'serial_number' => strtoupper(fake()->bothify('SN########')),
            'location' => fake()->randomElement(['Lab 1 cabinet', 'Lab 2 cabinet', 'Custodian office']),
            'condition' => EquipmentCondition::Good,
            'status' => EquipmentStatus::Available,
            'description' => null,
        ];
    }

    public function underMaintenance(): static
    {
        return $this->state(fn () => ['status' => EquipmentStatus::UnderMaintenance]);
    }

    public function retired(): static
    {
        return $this->state(fn () => ['status' => EquipmentStatus::Retired]);
    }
}
