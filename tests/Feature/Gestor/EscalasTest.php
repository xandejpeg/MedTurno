<?php

use App\Enums\RecurrenceType;
use App\Enums\Role;
use App\Enums\ScheduleStatus;
use App\Enums\ShiftOrigin;
use App\Enums\ShiftStatus;
use App\Models\Hospital;
use App\Models\Recurrence;
use App\Models\Schedule;
use App\Models\ShiftBoard;
use App\Models\ShiftTemplate;
use App\Models\User;
use App\Services\ScheduleService;
use Livewire\Volt\Volt;

/**
 * Agosto/2026: segundas-feiras nos dias 3, 10, 17, 24 e 31.
 */
function escalaSetup(): array
{
    $gestor = User::factory()->create();
    $hospital = Hospital::factory()->create();
    $gestor->hospitalMemberships()->create([
        'hospital_id' => $hospital->id,
        'role' => Role::Gestor,
    ]);
    session(['current_hospital_id' => $hospital->id]);

    $board = ShiftBoard::factory()->for($hospital)->create();

    return [$gestor, $hospital, $board];
}

function medicoNoQuadro(Hospital $hospital, ShiftBoard $board): User
{
    $medico = User::factory()->create();
    $medico->hospitalMemberships()->create([
        'hospital_id' => $hospital->id,
        'role' => Role::Medico,
    ]);
    $board->doctors()->attach($medico);

    return $medico;
}

test('createDraft gera plantões vagos a partir dos templates', function () {
    [$gestor, , $board] = escalaSetup();
    ShiftTemplate::factory()->for($board, 'board')->create([
        'weekday' => 1, 'start_time' => '07:00', 'end_time' => '19:00', 'slots' => 1, 'amount' => 1200,
    ]);

    $schedule = (new ScheduleService)->createDraft($board, 2026, 8, $gestor);

    expect($schedule->status)->toBe(ScheduleStatus::Rascunho)
        ->and($schedule->shifts()->count())->toBe(5)
        ->and($schedule->shifts()->where('status', ShiftStatus::SemMedico)->count())->toBe(5)
        ->and($schedule->shifts()->whereNotNull('amount')->count())->toBe(0);

    $first = $schedule->shifts()->orderBy('date')->first();
    expect($first->date->toDateString())->toBe('2026-08-03')
        ->and($first->starts_at->format('Y-m-d H:i'))->toBe('2026-08-03 07:00')
        ->and($first->ends_at->format('Y-m-d H:i'))->toBe('2026-08-03 19:00');
});

test('turno que atravessa a meia-noite termina no dia seguinte', function () {
    [$gestor, , $board] = escalaSetup();
    ShiftTemplate::factory()->for($board, 'board')->create([
        'weekday' => 1, 'start_time' => '19:00', 'end_time' => '07:00', 'crosses_midnight' => true,
    ]);

    $schedule = (new ScheduleService)->createDraft($board, 2026, 8, $gestor);

    $first = $schedule->shifts()->orderBy('date')->first();
    expect($first->starts_at->format('Y-m-d H:i'))->toBe('2026-08-03 19:00')
        ->and($first->ends_at->format('Y-m-d H:i'))->toBe('2026-08-04 07:00');
});

test('recorrência semanal pré-preenche todas as ocorrências', function () {
    [$gestor, $hospital, $board] = escalaSetup();
    $template = ShiftTemplate::factory()->for($board, 'board')->create([
        'weekday' => 1, 'start_time' => '07:00', 'end_time' => '19:00', 'amount' => 1200,
    ]);
    $medico = medicoNoQuadro($hospital, $board);
    Recurrence::factory()->create([
        'user_id' => $medico->id,
        'shift_template_id' => $template->id,
        'type' => RecurrenceType::Semanal,
        'reference_date' => '2026-08-03',
    ]);

    $schedule = (new ScheduleService)->createDraft($board, 2026, 8, $gestor);

    $assigned = $schedule->shifts()->where('user_id', $medico->id)->get();
    expect($assigned)->toHaveCount(5)
        ->and($assigned->every(fn ($s) => $s->status === ShiftStatus::Confirmado))->toBeTrue()
        ->and($assigned->every(fn ($s) => $s->origin === ShiftOrigin::Recorrencia))->toBeTrue()
        ->and($assigned->every(fn ($s) => (float) $s->amount === 1200.0))->toBeTrue();
});

test('recorrência quinzenal respeita a paridade da data de referência', function () {
    [$gestor, $hospital, $board] = escalaSetup();
    $template = ShiftTemplate::factory()->for($board, 'board')->create([
        'weekday' => 1, 'start_time' => '07:00', 'end_time' => '19:00',
    ]);
    $medico = medicoNoQuadro($hospital, $board);
    Recurrence::factory()->create([
        'user_id' => $medico->id,
        'shift_template_id' => $template->id,
        'type' => RecurrenceType::Quinzenal,
        'reference_date' => '2026-08-10',
    ]);

    $schedule = (new ScheduleService)->createDraft($board, 2026, 8, $gestor);

    $dates = $schedule->shifts()->where('user_id', $medico->id)->orderBy('date')->pluck('date')
        ->map(fn ($d) => $d->toDateString())->all();

    expect($dates)->toBe(['2026-08-10', '2026-08-24'])
        ->and($schedule->shifts()->where('status', ShiftStatus::SemMedico)->count())->toBe(3);
});

test('recorrência quinzenal com referência em mês anterior mantém paridade', function () {
    [$gestor, $hospital, $board] = escalaSetup();
    $template = ShiftTemplate::factory()->for($board, 'board')->create([
        'weekday' => 1, 'start_time' => '07:00', 'end_time' => '19:00',
    ]);
    $medico = medicoNoQuadro($hospital, $board);
    Recurrence::factory()->create([
        'user_id' => $medico->id,
        'shift_template_id' => $template->id,
        'type' => RecurrenceType::Quinzenal,
        'reference_date' => '2026-07-20', // segunda, 14 dias antes de 03/08
    ]);

    $schedule = (new ScheduleService)->createDraft($board, 2026, 8, $gestor);

    $dates = $schedule->shifts()->where('user_id', $medico->id)->orderBy('date')->pluck('date')
        ->map(fn ($d) => $d->toDateString())->all();

    expect($dates)->toBe(['2026-08-03', '2026-08-17', '2026-08-31']);
});

test('template com 2 vagas e 1 recorrência deixa 1 vaga aberta por dia', function () {
    [$gestor, $hospital, $board] = escalaSetup();
    $template = ShiftTemplate::factory()->for($board, 'board')->create([
        'weekday' => 1, 'start_time' => '07:00', 'end_time' => '19:00', 'slots' => 2,
    ]);
    $medico = medicoNoQuadro($hospital, $board);
    Recurrence::factory()->create([
        'user_id' => $medico->id,
        'shift_template_id' => $template->id,
        'type' => RecurrenceType::Semanal,
        'reference_date' => '2026-08-03',
    ]);

    $schedule = (new ScheduleService)->createDraft($board, 2026, 8, $gestor);

    expect($schedule->shifts()->count())->toBe(10)
        ->and($schedule->shifts()->where('user_id', $medico->id)->count())->toBe(5)
        ->and($schedule->shifts()->where('status', ShiftStatus::SemMedico)->count())->toBe(5);
});

test('recorrência inativa e template inativo são ignorados', function () {
    [$gestor, $hospital, $board] = escalaSetup();
    $template = ShiftTemplate::factory()->for($board, 'board')->create([
        'weekday' => 1, 'start_time' => '07:00', 'end_time' => '19:00',
    ]);
    ShiftTemplate::factory()->for($board, 'board')->create([
        'weekday' => 2, 'start_time' => '07:00', 'end_time' => '19:00', 'active' => false,
    ]);
    $medico = medicoNoQuadro($hospital, $board);
    Recurrence::factory()->create([
        'user_id' => $medico->id,
        'shift_template_id' => $template->id,
        'type' => RecurrenceType::Semanal,
        'reference_date' => '2026-08-03',
        'active' => false,
    ]);

    $schedule = (new ScheduleService)->createDraft($board, 2026, 8, $gestor);

    expect($schedule->shifts()->count())->toBe(5)
        ->and($schedule->shifts()->whereNotNull('user_id')->count())->toBe(0);
});

test('não permite duas escalas do mesmo quadro no mesmo mês', function () {
    [$gestor, , $board] = escalaSetup();
    ShiftTemplate::factory()->for($board, 'board')->create(['weekday' => 1]);

    $service = new ScheduleService;
    $service->createDraft($board, 2026, 8, $gestor);
    $service->createDraft($board, 2026, 8, $gestor);
})->throws(InvalidArgumentException::class);

test('página nova escala gera escala mensal sem exigir quadro', function () {
    [$gestor, $hospital, $board] = escalaSetup();
    $board->delete();

    $component = Volt::actingAs($gestor)
        ->test('pages.gestor.escalas-nova')
        ->set('month', '2026-08')
        ->call('save')
        ->assertHasNoErrors();

    $schedule = Schedule::query()->where('hospital_id', $hospital->id)->where('month', 8)->firstOrFail();

    $component->assertRedirect(route('gestor.escala.montar', $schedule));

    expect($schedule->shift_board_id)->toBeNull()
        ->and($schedule->shifts()->count())->toBe(62);
});

test('listagem de escalas é isolada por hospital', function () {
    [$gestor] = escalaSetup();
    $outra = Schedule::factory()->create();

    Volt::actingAs($gestor)
        ->test('pages.gestor.escalas')
        ->assertDontSee($outra->board->name);
});

test('gestor cria recorrência validando dia da semana', function () {
    [$gestor, $hospital, $board] = escalaSetup();
    $template = ShiftTemplate::factory()->for($board, 'board')->create(['weekday' => 1]);
    $medico = medicoNoQuadro($hospital, $board);

    // 2026-08-04 é terça — não bate com o turno de segunda
    Volt::actingAs($gestor)
        ->test('pages.gestor.recorrencias')
        ->call('create')
        ->set('boardId', (string) $board->id)
        ->set('templateId', (string) $template->id)
        ->set('userId', (string) $medico->id)
        ->set('type', 'quinzenal')
        ->set('reference_date', '2026-08-04')
        ->call('save')
        ->assertHasErrors('reference_date');

    Volt::actingAs($gestor)
        ->test('pages.gestor.recorrencias')
        ->call('create')
        ->set('boardId', (string) $board->id)
        ->set('templateId', (string) $template->id)
        ->set('userId', (string) $medico->id)
        ->set('type', 'quinzenal')
        ->set('reference_date', '2026-08-03')
        ->call('save')
        ->assertHasNoErrors();

    expect(Recurrence::query()->where('user_id', $medico->id)->where('type', RecurrenceType::Quinzenal)->exists())->toBeTrue();
});

test('não permite recorrência duplicada ativa pro mesmo médico e turno', function () {
    [$gestor, $hospital, $board] = escalaSetup();
    $template = ShiftTemplate::factory()->for($board, 'board')->create(['weekday' => 1]);
    $medico = medicoNoQuadro($hospital, $board);
    Recurrence::factory()->create([
        'user_id' => $medico->id,
        'shift_template_id' => $template->id,
        'reference_date' => '2026-08-03',
    ]);

    Volt::actingAs($gestor)
        ->test('pages.gestor.recorrencias')
        ->call('create')
        ->set('boardId', (string) $board->id)
        ->set('templateId', (string) $template->id)
        ->set('userId', (string) $medico->id)
        ->set('reference_date', '2026-08-03')
        ->call('save')
        ->assertHasErrors('userId');
});
