<?php

use App\Enums\Role;
use App\Models\Absence;
use App\Models\Hospital;
use App\Models\User;
use App\Services\ScheduleService;

test('gestor registra ausência e ela bloqueia atribuição naquela data', function () {
    $gestor = User::factory()->create();
    $hospital = Hospital::factory()->create();
    $gestor->hospitalMemberships()->create(['hospital_id' => $hospital->id, 'role' => Role::Gestor]);
    $medico = User::factory()->create(['name' => 'Dra. Ausente']);
    $medico->hospitalMemberships()->create(['hospital_id' => $hospital->id, 'role' => Role::Medico]);

    Absence::create([
        'user_id' => $medico->id,
        'hospital_id' => $hospital->id,
        'starts_on' => '2026-08-10',
        'ends_on' => '2026-08-12',
        'scope' => 'hospital',
    ]);

    expect($medico->isAbsentOn('2026-08-11', $hospital->id))->toBeTrue()
        ->and($medico->isAbsentOn('2026-08-15', $hospital->id))->toBeFalse();
});

test('ausência em todas as escalas cobre qualquer hospital', function () {
    $medico = User::factory()->create();
    Absence::create([
        'user_id' => $medico->id,
        'hospital_id' => null,
        'starts_on' => '2026-08-10',
        'ends_on' => '2026-08-12',
        'scope' => 'all',
    ]);

    expect($medico->isAbsentOn('2026-08-11', 999))->toBeTrue();
});
