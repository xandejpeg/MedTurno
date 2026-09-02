<?php

use App\Enums\Role;
use App\Models\Hospital;
use App\Models\User;

test('painel do gestor carrega quando o mes nao tem escala publicada', function () {
    $gestor = User::factory()->gestor()->create();
    $hospital = Hospital::factory()->create();

    $gestor->hospitalMemberships()->create([
        'hospital_id' => $hospital->id,
        'role' => Role::Gestor,
    ]);

    $medico = User::factory()->medico()->create();
    $medico->hospitalMemberships()->create([
        'hospital_id' => $hospital->id,
        'role' => Role::Medico,
    ]);

    $this->actingAs($gestor)
        ->get('/dashboard')
        ->assertOk();
});
