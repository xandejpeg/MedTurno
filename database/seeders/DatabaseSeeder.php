<?php

namespace Database\Seeders;

use App\Enums\Role;
use App\Models\Hospital;
use App\Models\User;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    use WithoutModelEvents;

    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        $thallys = User::factory()->create([
            'name' => 'Thallys',
            'email' => 'thallys@teste.com',
            'password' => bcrypt('mudar123'),
        ]);

        $hospitals = [
            Hospital::create(['name' => 'Hospital Santa Maria']),
            Hospital::create(['name' => 'Hospital São Gabriel']),
        ];

        foreach ($hospitals as $hospital) {
            $thallys->hospitalMemberships()->create([
                'hospital_id' => $hospital->id,
                'role' => Role::Gestor,
            ]);
        }
    }
}
