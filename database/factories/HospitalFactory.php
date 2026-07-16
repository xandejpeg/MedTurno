<?php

namespace Database\Factories;

use App\Models\Hospital;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Hospital>
 */
class HospitalFactory extends Factory
{
    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'name' => 'Hospital '.fake()->lastName(),
            'address' => fake()->streetAddress(),
            'phone' => '+5581'.fake()->numerify('3#######'),
            'active' => true,
        ];
    }
}
