<?php

namespace Database\Factories;

use App\Enums\ReservationStatus;
use App\Models\Reservation;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * Default: a pending reservation tomorrow 1:00–3:00 PM.
 * States: ->approved(), ->released(), ->returned(), ->overdue(), ->at($start, $end)
 *
 * @extends Factory<Reservation>
 */
class ReservationFactory extends Factory
{
    public function definition(): array
    {
        $start = now()->addDay()->setTime(13, 0);

        return [
            'reference_no' => config('lablend.reference_prefix') . '-' . now()->year . '-' . fake()->unique()->numerify('9###'),
            'user_id' => User::factory(),
            'room_id' => null,
            'purpose' => fake()->randomElement(['Class presentation', 'Thesis defense', 'Lab exercise', 'Seminar', 'Org meeting']),
            'start_at' => $start,
            'end_at' => $start->copy()->addHours(2),
            'status' => ReservationStatus::Pending,
        ];
    }

    public function at($start, $end): static
    {
        return $this->state(fn () => ['start_at' => $start, 'end_at' => $end]);
    }

    public function approved(): static
    {
        return $this->state(fn () => [
            'status' => ReservationStatus::Approved,
            'approved_by' => User::factory()->admin(),
            'approved_at' => now(),
        ]);
    }

    public function released(): static
    {
        return $this->approved()->state(fn (array $attributes) => [
            'status' => ReservationStatus::Released,
            'released_at' => $attributes['start_at'],
        ]);
    }

    public function returned(): static
    {
        return $this->released()->state(fn (array $attributes) => [
            'status' => ReservationStatus::Returned,
            'returned_at' => $attributes['end_at'],
        ]);
    }

    /** Released, and the end time was yesterday: overdue. */
    public function overdue(): static
    {
        // at() first: states run in order, and released() copies start_at into released_at.
        return $this->at(now()->subDay()->setTime(9, 0), now()->subDay()->setTime(11, 0))->released();
    }
}
