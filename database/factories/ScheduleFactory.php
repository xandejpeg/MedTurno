<?php

namespace Database\Factories;

use App\Enums\ScheduleStatus;
use App\Models\Hospital;
use App\Models\Schedule;
use App\Models\ShiftBoard;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Schedule>
 */
class ScheduleFactory extends Factory
{
    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'hospital_id' => Hospital::factory(),
            'shift_board_id' => ShiftBoard::factory(),
            'year' => 2026,
            'month' => fake()->numberBetween(1, 12),
            'status' => ScheduleStatus::Rascunho,
            'version' => 1,
            'created_by' => User::factory(),
        ];
    }
}
