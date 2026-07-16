<?php

namespace Database\Factories;

use App\Enums\InterestStatus;
use App\Models\Shift;
use App\Models\ShiftInterest;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<ShiftInterest>
 */
class ShiftInterestFactory extends Factory
{
    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'shift_id' => Shift::factory(),
            'user_id' => User::factory(),
            'status' => InterestStatus::Pendente,
        ];
    }
}
