<?php

use App\Enums\Role;
use App\Enums\ScheduleStatus;
use App\Enums\ShiftStatus;
use App\Models\Hospital;
use App\Models\Notification;
use App\Models\Schedule;
use App\Models\Shift;
use App\Models\ShiftBoard;
use App\Models\User;
use App\Services\ScheduleService;
use Illuminate\Support\Facades\Mail;
use Livewire\Volt\Volt;

/**
 * @return array{0: User, 1: User, 2: Hospital, 3: Schedule}
 */
function medicoSetup(ScheduleStatus $status = ScheduleStatus::Publicada): array
{
    $gestor = User::factory()->create();
    $hospital = Hospital::factory()->create();
    $gestor->hospitalMemberships()->create(['hospital_id' => $hospital->id, 'role' => Role::Gestor]);

    $medico = User::factory()->create();
    $medico->hospitalMemberships()->create(['hospital_id' => $hospital->id, 'role' => Role::Medico]);

    $board = ShiftBoard::factory()->for($hospital)->create();
    $schedule = Schedule::factory()->for($hospital)->create([
        'shift_board_id' => $board->id,
        'status' => $status,
        'created_by' => $gestor->id,
    ]);

    return [$medico, $gestor, $hospital, $schedule];
}

test('painel mostra próximo plantão e pendentes', function () {
    [$medico, , , $schedule] = medicoSetup();
    $date = now()->addDays(3);
    Shift::factory()->for($schedule)->create([
        'user_id' => $medico->id,
        'status' => ShiftStatus::Pendente,
        'date' => $date->toDateString(),
        'starts_at' => $date->copy()->setTime(7, 0),
        'ends_at' => $date->copy()->setTime(19, 0),
        'amount' => 1200,
    ]);

    Volt::actingAs($medico)
        ->test('pages.medico.painel')
        ->assertSee($date->format('d/m'))
        ->assertSee('Pendentes de confirmação');
});

test('médico confirma plantão pendente e gestor recebe notificação', function () {
    [$medico, $gestor, , $schedule] = medicoSetup();
    $shift = Shift::factory()->for($schedule)->create([
        'user_id' => $medico->id,
        'status' => ShiftStatus::Pendente,
        'amount' => 1200,
    ]);

    Volt::actingAs($medico)
        ->test('pages.medico.plantao', ['shift' => $shift])
        ->call('confirm')
        ->assertHasNoErrors();

    $shift->refresh();
    expect($shift->status)->toBe(ShiftStatus::Confirmado)
        ->and($shift->confirmed_at)->not->toBeNull();

    $notification = Notification::where('user_id', $gestor->id)->first();
    expect($notification)->not->toBeNull()
        ->and($notification->type)->toBe('plantao_confirmado')
        ->and($notification->body)->toContain($medico->name);
});

test('confirmação é idempotente', function () {
    [$medico, $gestor, , $schedule] = medicoSetup();
    $shift = Shift::factory()->for($schedule)->create([
        'user_id' => $medico->id,
        'status' => ShiftStatus::Pendente,
    ]);

    $component = Volt::actingAs($medico)->test('pages.medico.plantao', ['shift' => $shift]);
    $component->call('confirm')->assertHasNoErrors();
    $confirmedAt = $shift->refresh()->confirmed_at;

    $component->call('confirm')->assertHasNoErrors();

    expect($shift->refresh()->confirmed_at->toIso8601String())->toBe($confirmedAt->toIso8601String())
        ->and(Notification::where('user_id', $gestor->id)->count())->toBe(1);
});

test('médico não acessa plantão de outro médico', function () {
    [$medico, , $hospital, $schedule] = medicoSetup();
    $outro = User::factory()->create();
    $outro->hospitalMemberships()->create(['hospital_id' => $hospital->id, 'role' => Role::Medico]);

    $shift = Shift::factory()->for($schedule)->create([
        'user_id' => $outro->id,
        'status' => ShiftStatus::Pendente,
    ]);

    $this->actingAs($medico)
        ->get(route('medico.plantao', $shift))
        ->assertForbidden();
});

test('médico não vê plantão de escala em rascunho', function () {
    [$medico, , , $schedule] = medicoSetup(ScheduleStatus::Rascunho);
    $shift = Shift::factory()->for($schedule)->create([
        'user_id' => $medico->id,
        'status' => ShiftStatus::Pendente,
    ]);

    $this->actingAs($medico)
        ->get(route('medico.plantao', $shift))
        ->assertNotFound();
});

test('escala do médico mostra só os próprios plantões publicados', function () {
    [$medico, , $hospital, $schedule] = medicoSetup();
    $outro = User::factory()->create();
    $outro->hospitalMemberships()->create(['hospital_id' => $hospital->id, 'role' => Role::Medico]);

    $date = now()->startOfMonth()->addDays(9);
    Shift::factory()->for($schedule)->create([
        'user_id' => $medico->id,
        'status' => ShiftStatus::Confirmado,
        'date' => $date->toDateString(),
        'starts_at' => $date->copy()->setTime(7, 0),
        'ends_at' => $date->copy()->setTime(19, 0),
    ]);
    Shift::factory()->for($schedule)->create([
        'user_id' => $outro->id,
        'status' => ShiftStatus::Pendente,
        'date' => $date->toDateString(),
        'starts_at' => $date->copy()->setTime(19, 0),
        'ends_at' => $date->copy()->addDay()->setTime(7, 0),
    ]);

    $component = Volt::actingAs($medico)
        ->test('pages.medico.escala')
        ->set('month', now()->format('Y-m'));

    expect($component->viewData('shifts'))->toHaveCount(1)
        ->and($component->viewData('shifts')->first()->user_id)->toBe($medico->id);
});

test('filtro por hospital na escala do médico', function () {
    [$medico, $gestor, $hospital, $schedule] = medicoSetup();

    $hospital2 = Hospital::factory()->create();
    $gestor->hospitalMemberships()->create(['hospital_id' => $hospital2->id, 'role' => Role::Gestor]);
    $medico->hospitalMemberships()->create(['hospital_id' => $hospital2->id, 'role' => Role::Medico]);
    $board2 = ShiftBoard::factory()->for($hospital2)->create();
    $schedule2 = Schedule::factory()->for($hospital2)->create([
        'shift_board_id' => $board2->id,
        'status' => ScheduleStatus::Publicada,
        'created_by' => $gestor->id,
    ]);

    $date = now()->startOfMonth()->addDays(4);
    foreach ([$schedule, $schedule2] as $i => $s) {
        Shift::factory()->for($s)->create([
            'user_id' => $medico->id,
            'status' => ShiftStatus::Pendente,
            'date' => $date->copy()->addDays($i)->toDateString(),
            'starts_at' => $date->copy()->addDays($i)->setTime(7, 0),
            'ends_at' => $date->copy()->addDays($i)->setTime(19, 0),
        ]);
    }

    $component = Volt::actingAs($medico)
        ->test('pages.medico.escala')
        ->set('month', now()->format('Y-m'))
        ->set('hospitalFilter', (string) $hospital2->id);

    expect($component->viewData('shifts'))->toHaveCount(1)
        ->and($component->viewData('shifts')->first()->hospital_id)->toBe($hospital2->id);
});

test('publicar escala gera notificação interna pros médicos', function () {
    Mail::fake();
    [$medico, $gestor, , $schedule] = medicoSetup(ScheduleStatus::Rascunho);
    Shift::factory()->for($schedule)->create([
        'user_id' => $medico->id,
        'status' => ShiftStatus::Pendente,
    ]);

    (new ScheduleService)->publish($schedule);

    $notification = Notification::where('user_id', $medico->id)->first();
    expect($notification)->not->toBeNull()
        ->and($notification->type)->toBe('escala_publicada');
});

test('página de notificações marca todas como lidas ao abrir', function () {
    $user = User::factory()->create();
    Notification::factory()->count(3)->for($user)->create();

    Volt::actingAs($user)
        ->test('pages.notificacoes');

    expect($user->notifications()->whereNull('read_at')->count())->toBe(0);
});

test('detalhe do plantão mostra o valor pro médico', function () {
    [$medico, , , $schedule] = medicoSetup();
    $shift = Shift::factory()->for($schedule)->create([
        'user_id' => $medico->id,
        'status' => ShiftStatus::Pendente,
        'amount' => 1350.50,
    ]);

    Volt::actingAs($medico)
        ->test('pages.medico.plantao', ['shift' => $shift])
        ->assertSee('1.350,50')
        ->assertSee('Confirmar plantão');
});
