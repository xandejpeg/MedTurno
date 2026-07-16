<?php

namespace Database\Factories;

use App\Models\Notification;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Notification>
 */
class NotificationFactory extends Factory
{
    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'user_id' => User::factory(),
            'hospital_id' => null,
            'type' => 'geral',
            'title' => fake()->sentence(3),
            'body' => fake()->sentence(),
            'link' => null,
            'read_at' => null,
        ];
    }
}
