<?php

namespace Database\Factories;

use App\Models\Hospital;
use App\Models\ShiftBoard;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<ShiftBoard>
 */
class ShiftBoardFactory extends Factory
{
    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'hospital_id' => Hospital::factory(),
            'name' => fake()->unique()->words(2, true),
            'description' => null,
            'color' => null,
            'active' => true,
        ];
    }
}
