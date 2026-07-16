<?php

namespace Database\Factories;

use App\Enums\ShiftOrigin;
use App\Enums\ShiftStatus;
use App\Models\Schedule;
use App\Models\Shift;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Carbon;

/**
 * @extends Factory<Shift>
 */
class ShiftFactory extends Factory
{
    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $date = Carbon::parse(fake()->dateTimeBetween('+1 week', '+2 months')->format('Y-m-d'));

        return [
            'schedule_id' => Schedule::factory(),
            'shift_template_id' => null,
            'hospital_id' => fn (array $attrs) => Schedule::find($attrs['schedule_id'])->hospital_id,
            'shift_board_id' => fn (array $attrs) => Schedule::find($attrs['schedule_id'])->shift_board_id,
            'date' => $date->toDateString(),
            'starts_at' => $date->copy()->setTime(7, 0),
            'ends_at' => $date->copy()->setTime(19, 0),
            'user_id' => null,
            'status' => ShiftStatus::SemMedico,
            'amount' => null,
            'origin' => ShiftOrigin::Manual,
        ];
    }
}
