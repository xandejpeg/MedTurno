<?php

namespace Database\Factories;

use App\Enums\RecurrenceType;
use App\Models\Recurrence;
use App\Models\ShiftTemplate;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Recurrence>
 */
class RecurrenceFactory extends Factory
{
    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'user_id' => User::factory(),
            'shift_template_id' => ShiftTemplate::factory(),
            'type' => RecurrenceType::Semanal,
            'reference_date' => now()->startOfMonth()->toDateString(),
            'active' => true,
        ];
    }
}
