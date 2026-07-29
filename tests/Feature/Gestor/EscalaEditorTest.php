<?php

use App\Enums\Role;
use App\Enums\ScheduleStatus;
use App\Enums\ShiftStatus;
use App\Mail\EscalaPublicada;
use App\Models\Hospital;
use App\Models\Schedule;
use App\Models\Shift;
use App\Models\ShiftBoard;
use App\Models\ShiftTemplate;
use App\Models\User;
use App\Services\ScheduleService;
use App\Services\NotificationService;
use App\Services\ShiftService;
use Illuminate\Support\Facades\Mail;
use Livewire\Volt\Volt;

function editorSetup(): array
{
    $gestor = User::factory()->create();
    $hospital = Hospital::factory()->create();
    $gestor->hospitalMemberships()->create([
        'hospital_id' => $hospital->id,
        'role' => Role::Gestor,
    ]);
    session(['current_hospital_id' => $hospital->id]);

    $board = ShiftBoard::factory()->for($hospital)->create();
    $schedule = Schedule::factory()->for($hospital)->create([
        'shift_board_id' => $board->id,
        'year' => 2026,
        'month' => 8,
        'created_by' => $gestor->id,
    ]);

    $medico = User::factory()->create();
    $medico->hospitalMemberships()->create([
        'hospital_id' => $hospital->id,
        'role' => Role::Medico,
    ]);
    $board->doctors()->attach($medico);

    return [$gestor, $hospital, $board, $schedule, $medico];
}

test('gestor atribui médico e congela o valor do template', function () {
    [$gestor, , $board, $schedule, $medico] = editorSetup();
    $template = ShiftTemplate::factory()->for($board, 'board')->create(['amount' => 1200]);
    $shift = Shift::factory()->for($schedule)->create(['shift_template_id' => $template->id]);

    Volt::actingAs($gestor)
        ->test('pages.gestor.escala', ['schedule' => $schedule])
        ->call('openShift', $shift->id)
        ->set('doctorId', (string) $medico->id)
        ->call('saveShift')
        ->assertHasNoErrors();

    $shift->refresh();
    expect($shift->user_id)->toBe($medico->id)
        ->and($shift->status)->toBe(ShiftStatus::Confirmado)
        ->and((float) $shift->amount)->toBe(1200.0);
});

test('trocar médico mantém o valor congelado e limpa confirmação', function () {
    [, $hospital, $board, $schedule, $medico] = editorSetup();
    $outro = User::factory()->create();
    $outro->hospitalMemberships()->create(['hospital_id' => $hospital->id, 'role' => Role::Medico]);
    $board->doctors()->attach($outro);

    $shift = Shift::factory()->for($schedule)->create([
        'user_id' => $medico->id,
        'status' => ShiftStatus::Confirmado,
        'amount' => 999,
        'confirmed_at' => now(),
    ]);

    (new ShiftService)->assignDoctor($shift, $outro);

    $shift->refresh();
    expect($shift->user_id)->toBe($outro->id)
        ->and($shift->status)->toBe(ShiftStatus::Confirmado)
        ->and((float) $shift->amount)->toBe(999.0)
        ->and($shift->confirmed_at)->not->toBeNull();
});

test('tirar médico volta o plantão pra sem_medico', function () {
    [$gestor, , , $schedule, $medico] = editorSetup();
    $shift = Shift::factory()->for($schedule)->create([
        'user_id' => $medico->id,
        'status' => ShiftStatus::Pendente,
    ]);

    Volt::actingAs($gestor)
        ->test('pages.gestor.escala', ['schedule' => $schedule])
        ->call('openShift', $shift->id)
        ->call('removeDoctor');

    $shift->refresh();
    expect($shift->user_id)->toBeNull()
        ->and($shift->status)->toBe(ShiftStatus::SemMedico);
});

test('plantão concluído não pode ser alterado', function () {
    [, , , $schedule, $medico] = editorSetup();
    $shift = Shift::factory()->for($schedule)->create([
        'user_id' => $medico->id,
        'status' => ShiftStatus::Concluido,
    ]);

    (new ShiftService)->unassignDoctor($shift);
})->throws(InvalidArgumentException::class);

test('conflito de horário é detectado mesmo em outro hospital', function () {
    [, , , $schedule, $medico] = editorSetup();

    // plantão existente do médico em OUTRO hospital, mesmo horário
    $outroShift = Shift::factory()->create([
        'user_id' => $medico->id,
        'status' => ShiftStatus::Confirmado,
        'starts_at' => '2026-08-03 07:00',
        'ends_at' => '2026-08-03 19:00',
    ]);

    $service = new ShiftService;
    $conflicts = $service->conflictsFor($medico, now()->parse('2026-08-03 10:00'), now()->parse('2026-08-03 14:00'));

    expect($conflicts->pluck('id')->all())->toBe([$outroShift->id]);

    // horários que só encostam (meio-aberto) não conflitam
    expect($service->conflictsFor($medico, now()->parse('2026-08-03 19:00'), now()->parse('2026-08-04 07:00')))->toHaveCount(0);
});

test('editor mostra aviso de conflito ao escolher médico ocupado', function () {
    [$gestor, , , $schedule, $medico] = editorSetup();
    Shift::factory()->create([
        'user_id' => $medico->id,
        'status' => ShiftStatus::Pendente,
        'starts_at' => '2026-08-03 07:00',
        'ends_at' => '2026-08-03 19:00',
    ]);
    $shift = Shift::factory()->for($schedule)->create([
        'date' => '2026-08-03',
        'starts_at' => '2026-08-03 10:00',
        'ends_at' => '2026-08-03 14:00',
    ]);

    Volt::actingAs($gestor)
        ->test('pages.gestor.escala', ['schedule' => $schedule])
        ->call('openShift', $shift->id)
        ->set('doctorId', (string) $medico->id)
        ->assertSee('Conflito de horário');
});

test('publicar muda status e envia email pros médicos da escala', function () {
    Mail::fake();
    [$gestor, , , $schedule, $medico] = editorSetup();
    Shift::factory()->for($schedule)->count(2)->create(['user_id' => $medico->id, 'status' => ShiftStatus::Pendente]);
    Shift::factory()->for($schedule)->create(); // vago

    Volt::actingAs($gestor)
        ->test('pages.gestor.escala', ['schedule' => $schedule])
        ->call('publish');

    $schedule->refresh();
    expect($schedule->status)->toBe(ScheduleStatus::Publicada)
        ->and($schedule->version)->toBe(1)
        ->and($schedule->published_at)->not->toBeNull();

    Mail::assertQueued(EscalaPublicada::class, 1);
    Mail::assertQueued(EscalaPublicada::class, fn (EscalaPublicada $mail) => $mail->hasTo($medico->email));
});

test('publicar duas vezes gera versão 2', function () {
    Mail::fake();
    [$gestor, , , $schedule, $medico] = editorSetup();
    Shift::factory()->for($schedule)->create(['user_id' => $medico->id, 'status' => ShiftStatus::Pendente]);

    $service = app(ScheduleService::class);
    $service->publish($schedule);
    $service->publish($schedule->refresh());

    expect($schedule->refresh()->version)->toBe(2)
        ->and($schedule->status)->toBe(ScheduleStatus::Publicada);

    Mail::assertQueued(EscalaPublicada::class, 2);
});

test('falha ao avisar médico não desfaz a publicação', function () {
    Mail::fake();
    [, , , $schedule, $medico] = editorSetup();
    Shift::factory()->for($schedule)->create(['user_id' => $medico->id]);

    $notifications = \Mockery::mock(NotificationService::class);
    $notifications->shouldReceive('send')->once()->andThrow(new RuntimeException('canal indisponível'));
    app()->instance(NotificationService::class, $notifications);

    app(ScheduleService::class)->publish($schedule);

    expect($schedule->refresh()->status)->toBe(ScheduleStatus::Publicada)
        ->and($schedule->published_at)->not->toBeNull();
});

test('replica escala de quadro preservando médico no mesmo turno semanal', function () {
    [$gestor, , $board, $august, $medico] = editorSetup();
    $template = ShiftTemplate::factory()->for($board, 'board')->create([
        'weekday' => 1,
        'start_time' => '07:00',
        'end_time' => '19:00',
        'slots' => 1,
        'amount' => 1600,
    ]);
    Shift::factory()->for($august)->create([
        'shift_template_id' => $template->id,
        'date' => '2026-08-10',
        'starts_at' => '2026-08-10 07:00',
        'ends_at' => '2026-08-10 19:00',
        'user_id' => $medico->id,
        'status' => ShiftStatus::Confirmado,
        'amount' => 1800,
    ]);

    $september = app(ScheduleService::class)->replicateToMonth($august, 2026, 9, $gestor);
    $copiedShift = $september->shifts()
        ->where('shift_template_id', $template->id)
        ->whereDate('date', '2026-09-14')
        ->firstOrFail();

    expect($september->shift_board_id)->toBe($board->id)
        ->and($september->status)->toBe(ScheduleStatus::Rascunho)
        ->and($copiedShift->user_id)->toBe($medico->id)
        ->and((float) $copiedShift->amount)->toBe(1800.0);
});

test('escala cancelada não pode ser publicada', function () {
    [, , , $schedule] = editorSetup();
    $schedule->update(['status' => ScheduleStatus::Cancelada]);

    app(ScheduleService::class)->publish($schedule->refresh());
})->throws(InvalidArgumentException::class);

test('gestor de outro hospital não abre o editor', function () {
    [, , , $schedule] = editorSetup();

    $intruso = User::factory()->create();
    $outroHospital = Hospital::factory()->create();
    $intruso->hospitalMemberships()->create([
        'hospital_id' => $outroHospital->id,
        'role' => Role::Gestor,
    ]);

    $this->actingAs($intruso)
        ->get(route('gestor.escala', $schedule))
        ->assertForbidden();
});
