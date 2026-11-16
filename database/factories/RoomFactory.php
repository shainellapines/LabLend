<?php

namespace Database\Factories;

use App\Enums\RoomType;
use App\Models\Room;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Room>
 */
class RoomFactory extends Factory
{
    public function definition(): array
    {
        return [
            'name' => 'Room ' . fake()->unique()->numberBetween(100, 999),
            'code' => strtoupper(fake()->unique()->bothify('RM-###')),
            'type' => RoomType::Classroom,
            'capacity' => fake()->numberBetween(20, 50),
            'location' => 'IT Building',
            'description' => null,
            'is_active' => true,
        ];
    }

    public function inactive(): static
    {
        return $this->state(fn () => ['is_active' => false]);
    }
}
