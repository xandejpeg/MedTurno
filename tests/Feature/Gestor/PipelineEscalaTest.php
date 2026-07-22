<?php

use App\Enums\Role;
use App\Enums\ScheduleStatus;
use App\Enums\ShiftStatus;
use App\Mail\EscalaPublicada;
use App\Models\Hospital;
use App\Models\User;
use App\Services\ScheduleService;
use Illuminate\Support\Facades\Mail;
use Livewire\Volt\Volt;

function pipelineSetup(): array
{
    $gestor = User::factory()->gestor()->create();
    $hospital = Hospital::factory()->create(['name' => 'Hospital Pipeline']);
    $gestor->hospitalMemberships()->create([
        'hospital_id' => $hospital->id,
        'role' => Role::Gestor,
    ]);

    $medico = User::factory()->medico()->create(['name' => 'Dr. Teste']);
    $medico->hospitalMemberships()->create([
        'hospital_id' => $hospital->id,
        'role' => Role::Medico,
    ]);

    return [$gestor, $hospital, $medico];
}

test('cadastro cria gestor com papel e autentica', function () {
    Volt::test('pages.auth.register')
        ->set('role', 'gestor')
        ->set('name', 'Novo Gestor')
        ->set('email', 'novo@gestor.com')
        ->set('password', 'senha-forte-123')
        ->set('password_confirmation', 'senha-forte-123')
        ->call('register')
        ->assertHasNoErrors();

    $user = User::where('email', 'novo@gestor.com')->first();

    expect($user)->not->toBeNull()
        ->and($user->isGestor())->toBeTrue()
        ->and($user->email_verified_at)->not->toBeNull();

    $this->assertAuthenticatedAs($user);
});

test('createMonthly cria 2 plantões por dia com períodos dia e noite', function () {
    [$gestor, $hospital] = pipelineSetup();

    $schedule = app(ScheduleService::class)->createMonthly($hospital, 2026, 8, $gestor);

    expect($schedule->status)->toBe(ScheduleStatus::Rascunho)
        ->and($schedule->shift_board_id)->toBeNull()
        ->and($schedule->shifts()->count())->toBe(31 * 2)
        ->and($schedule->shifts()->where('period', 'dia')->count())->toBe(31)
        ->and($schedule->shifts()->where('period', 'noite')->count())->toBe(31)
        ->and($schedule->shifts()->whereNotNull('user_id')->count())->toBe(0);
});

test('createMonthly não duplica a escala do mesmo mês', function () {
    [$gestor, $hospital] = pipelineSetup();
    $service = app(ScheduleService::class);
    $service->createMonthly($hospital, 2026, 8, $gestor);

    expect(fn () => $service->createMonthly($hospital, 2026, 8, $gestor))
        ->toThrow(InvalidArgumentException::class);
});

test('gestor atribui e remove médico de um plantão', function () {
    [$gestor, $hospital, $medico] = pipelineSetup();
    $schedule = app(ScheduleService::class)->createMonthly($hospital, 2026, 8, $gestor);
    $shift = $schedule->shifts()->where('period', 'dia')->first();

    $component = Volt::actingAs($gestor)
        ->test('pages.gestor.escala-montar', ['schedule' => $schedule])
        ->call('assign', $shift->id, $medico->id);

    $shift->refresh();
    expect($shift->user_id)->toBe($medico->id)
        ->and($shift->status)->toBe(ShiftStatus::Confirmado);

    $component->call('unassign', $shift->id);

    $shift->refresh();
    expect($shift->user_id)->toBeNull()
        ->and($shift->status)->toBe(ShiftStatus::SemMedico);
});

test('gestor não pode atribuir médico de outro hospital', function () {
    [$gestor, $hospital] = pipelineSetup();
    $schedule = app(ScheduleService::class)->createMonthly($hospital, 2026, 8, $gestor);
    $shift = $schedule->shifts()->first();
    $estranho = User::factory()->medico()->create();

    Volt::actingAs($gestor)
        ->test('pages.gestor.escala-montar', ['schedule' => $schedule])
        ->call('assign', $shift->id, $estranho->id)
        ->assertStatus(422);

    expect($shift->fresh()->user_id)->toBeNull();
});

test('publicar notifica os médicos com plantão e o médico vê a escala', function () {
    Mail::fake();
    [$gestor, $hospital, $medico] = pipelineSetup();
    $service = app(ScheduleService::class);
    $schedule = $service->createMonthly($hospital, 2026, 8, $gestor);
    $shift = $schedule->shifts()->where('period', 'dia')->first();
    $shift->update(['user_id' => $medico->id, 'status' => ShiftStatus::Confirmado]);

    $service->publish($schedule);

    expect($schedule->fresh()->status)->toBe(ScheduleStatus::Publicada);
    Mail::assertQueued(EscalaPublicada::class, fn ($mail) => $mail->hasTo($medico->email));

    Volt::actingAs($medico)
        ->test('pages.medico.escala', ['month' => '2026-08'])
        ->assertSee('Hospital Pipeline');
});
