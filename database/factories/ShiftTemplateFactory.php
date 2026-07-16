<?php

namespace Database\Factories;

use App\Models\ShiftBoard;
use App\Models\ShiftTemplate;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<ShiftTemplate>
 */
class ShiftTemplateFactory extends Factory
{
    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'shift_board_id' => ShiftBoard::factory(),
            'weekday' => fake()->numberBetween(0, 6),
            'start_time' => '07:00',
            'end_time' => '19:00',
            'crosses_midnight' => false,
            'slots' => 1,
            'amount' => null,
            'label' => null,
            'active' => true,
        ];
    }
}
