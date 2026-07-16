<?php

use App\Enums\Role;
use App\Enums\ScheduleStatus;
use App\Enums\ShiftStatus;
use App\Models\Hospital;
use App\Models\Schedule;
use App\Models\Shift;
use App\Models\User;

test('plantoes:fechar conclui confirmados e marca pendentes como não cumpridos', function () {
    $gestor = User::factory()->create();
    $hospital = Hospital::factory()->create();
    $gestor->hospitalMemberships()->create(['hospital_id' => $hospital->id, 'role' => Role::Gestor]);

    $schedule = Schedule::factory()->for($hospital)->create([
        'status' => ScheduleStatus::Publicada,
        'created_by' => $gestor->id,
    ]);

    $medico = User::factory()->create();

    $confirmado = Shift::factory()->for($schedule)->create([
        'user_id' => $medico->id,
        'status' => ShiftStatus::Confirmado,
        'date' => now()->subDay(),
        'ends_at' => now()->subHours(5),
        'confirmed_at' => now()->subDays(2),
    ]);

    $pendente = Shift::factory()->for($schedule)->create([
        'user_id' => $medico->id,
        'status' => ShiftStatus::Pendente,
        'date' => now()->subDay(),
        'ends_at' => now()->subHours(5),
    ]);

    $futuro = Shift::factory()->for($schedule)->create([
        'user_id' => $medico->id,
        'status' => ShiftStatus::Confirmado,
        'date' => now()->addDay(),
        'ends_at' => now()->addDay(),
        'confirmed_at' => now(),
    ]);

    $this->artisan('plantoes:fechar')->assertSuccessful();

    expect($confirmado->refresh()->status)->toBe(ShiftStatus::Concluido)
        ->and($pendente->refresh()->status)->toBe(ShiftStatus::NaoCumprido)
        ->and($futuro->refresh()->status)->toBe(ShiftStatus::Confirmado);
});

test('respostas trazem headers de segurança', function () {
    $response = $this->get('/login');

    $response->assertHeader('X-Frame-Options', 'DENY')
        ->assertHeader('X-Content-Type-Options', 'nosniff')
        ->assertHeader('Referrer-Policy', 'strict-origin-when-cross-origin');
});
