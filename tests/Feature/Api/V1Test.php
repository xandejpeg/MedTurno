<?php

use App\Enums\Role;
use App\Models\Hospital;
use App\Models\Schedule;
use App\Models\User;
use App\Services\ScheduleService;

test('api exige token válido', function () {
    $this->getJson('/api/v1/escalas')->assertStatus(401);
    $this->getJson('/api/v1/escalas', ['Authorization' => 'Bearer invalido'])->assertStatus(401);
});

test('api lista escalas do hospital autenticado', function () {
    $gestor = User::factory()->create();
    $hospital = Hospital::factory()->create();
    $gestor->hospitalMemberships()->create(['hospital_id' => $hospital->id, 'role' => Role::Gestor]);
    app(ScheduleService::class)->createMonthly($hospital, 2026, 8, $gestor);

    $response = $this->getJson('/api/v1/escalas', ['Authorization' => 'Bearer '.$hospital->api_token]);

    $response->assertOk()->assertJsonCount(1, 'data');
});

test('api lista profissionais do hospital', function () {
    $gestor = User::factory()->create();
    $hospital = Hospital::factory()->create();
    $gestor->hospitalMemberships()->create(['hospital_id' => $hospital->id, 'role' => Role::Gestor]);
    $medico = User::factory()->create();
    $medico->hospitalMemberships()->create(['hospital_id' => $hospital->id, 'role' => Role::Medico]);

    $response = $this->getJson('/api/v1/profissionais', ['Authorization' => 'Bearer '.$hospital->api_token]);

    $response->assertOk()->assertJsonPath('data.0.id', $medico->id);
});
