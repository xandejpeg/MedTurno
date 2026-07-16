<?php

use App\Enums\Role;
use App\Models\Hospital;
use App\Models\ShiftBoard;
use App\Models\ShiftTemplate;
use App\Models\User;
use App\Services\ShiftTemplateService;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Livewire\Volt\Volt;

function makeGestorComHospital(): array
{
    $gestor = User::factory()->create();
    $hospital = Hospital::factory()->create();
    $gestor->hospitalMemberships()->create([
        'hospital_id' => $hospital->id,
        'role' => Role::Gestor,
    ]);
    session(['current_hospital_id' => $hospital->id]);

    return [$gestor, $hospital];
}

test('gestor cria quadro no hospital atual', function () {
    [$gestor, $hospital] = makeGestorComHospital();

    Volt::actingAs($gestor)
        ->test('pages.gestor.quadros')
        ->call('create')
        ->set('name', 'Diurno UTI')
        ->set('description', 'UTI adulto')
        ->call('save')
        ->assertHasNoErrors();

    expect($hospital->shiftBoards()->where('name', 'Diurno UTI')->exists())->toBeTrue();
});

test('gestor não edita quadro de hospital que não gerencia', function () {
    [$gestor] = makeGestorComHospital();
    $outsider = ShiftBoard::factory()->create();

    Volt::actingAs($gestor)
        ->test('pages.gestor.quadros')
        ->call('edit', $outsider->id);
})->throws(ModelNotFoundException::class);

test('gestor não abre quadro de hospital alheio', function () {
    [$gestor] = makeGestorComHospital();
    $outsider = ShiftBoard::factory()->create();

    $this->actingAs($gestor)
        ->get(route('gestor.quadro', $outsider))
        ->assertForbidden();
});

test('gestor cria template no quadro', function () {
    [$gestor, $hospital] = makeGestorComHospital();
    $board = ShiftBoard::factory()->for($hospital)->create();

    Volt::actingAs($gestor)
        ->test('pages.gestor.quadro', ['board' => $board])
        ->call('createTemplate')
        ->set('weekday', 1)
        ->set('start_time', '07:00')
        ->set('end_time', '19:00')
        ->set('slots', 2)
        ->set('amount', '1200,00')
        ->set('label', 'Diurno')
        ->call('saveTemplate')
        ->assertHasNoErrors();

    $template = $board->templates()->first();
    expect($template->weekday)->toBe(1)
        ->and($template->crosses_midnight)->toBeFalse()
        ->and($template->slots)->toBe(2)
        ->and((float) $template->amount)->toBe(1200.0);
});

test('template com fim menor que início atravessa a meia-noite', function () {
    [$gestor, $hospital] = makeGestorComHospital();
    $board = ShiftBoard::factory()->for($hospital)->create();

    Volt::actingAs($gestor)
        ->test('pages.gestor.quadro', ['board' => $board])
        ->call('createTemplate')
        ->set('weekday', 1)
        ->set('start_time', '19:00')
        ->set('end_time', '07:00')
        ->call('saveTemplate')
        ->assertHasNoErrors();

    expect($board->templates()->first()->crosses_midnight)->toBeTrue();
});

test('template sobreposto no mesmo dia é rejeitado', function () {
    [$gestor, $hospital] = makeGestorComHospital();
    $board = ShiftBoard::factory()->for($hospital)->create();
    ShiftTemplate::factory()->for($board, 'board')->create([
        'weekday' => 1, 'start_time' => '07:00', 'end_time' => '19:00',
    ]);

    Volt::actingAs($gestor)
        ->test('pages.gestor.quadro', ['board' => $board])
        ->call('createTemplate')
        ->set('weekday', 1)
        ->set('start_time', '10:00')
        ->set('end_time', '14:00')
        ->call('saveTemplate')
        ->assertHasErrors('start_time');

    expect($board->templates()->count())->toBe(1);
});

test('turno noturno detecta sobreposição no dia seguinte', function () {
    $board = ShiftBoard::factory()->create();
    // Segunda 19:00 → Terça 07:00
    ShiftTemplate::factory()->for($board, 'board')->create([
        'weekday' => 1, 'start_time' => '19:00', 'end_time' => '07:00', 'crosses_midnight' => true,
    ]);

    $service = new ShiftTemplateService;

    // Terça 06:00–08:00 conflita com a madrugada do noturno de segunda
    expect($service->overlaps($board, 2, '06:00', '08:00', false))->toBeTrue()
        // Terça 07:00–19:00 não conflita (intervalo meio-aberto)
        ->and($service->overlaps($board, 2, '07:00', '19:00', false))->toBeFalse();
});

test('grade automática 12h cria 14 turnos', function () {
    [$gestor, $hospital] = makeGestorComHospital();
    $board = ShiftBoard::factory()->for($hospital)->create();

    Volt::actingAs($gestor)
        ->test('pages.gestor.quadro', ['board' => $board])
        ->call('openGridForm')
        ->set('gridDuration', 12)
        ->set('gridStart', '07:00')
        ->set('gridSlots', 1)
        ->call('applyGrid')
        ->assertHasNoErrors();

    expect($board->templates()->count())->toBe(14)
        ->and($board->templates()->where('crosses_midnight', true)->count())->toBe(7);
});

test('grade automática 24h cria 7 turnos e pula sobreposições', function () {
    [$gestor, $hospital] = makeGestorComHospital();
    $board = ShiftBoard::factory()->for($hospital)->create();
    ShiftTemplate::factory()->for($board, 'board')->create([
        'weekday' => 3, 'start_time' => '07:00', 'end_time' => '19:00',
    ]);

    $result = (new ShiftTemplateService)->applyGrid($board, 24, '07:00', 1, null);

    expect($result['created'])->toBe(6)
        ->and($result['skipped'])->toBe(1);
});

test('gestor vincula médicos ao quadro', function () {
    [$gestor, $hospital] = makeGestorComHospital();
    $board = ShiftBoard::factory()->for($hospital)->create();

    $medicos = User::factory()->count(3)->create();
    foreach ($medicos as $medico) {
        $medico->hospitalMemberships()->create([
            'hospital_id' => $hospital->id,
            'role' => Role::Medico,
        ]);
    }

    Volt::actingAs($gestor)
        ->test('pages.gestor.quadro', ['board' => $board])
        ->set('selectedDoctors', $medicos->pluck('id')->all())
        ->call('saveDoctors')
        ->assertHasNoErrors();

    expect($board->doctors()->count())->toBe(3);
});

test('médico de outro hospital não pode ser vinculado', function () {
    [$gestor, $hospital] = makeGestorComHospital();
    $board = ShiftBoard::factory()->for($hospital)->create();
    $estranho = User::factory()->create();

    Volt::actingAs($gestor)
        ->test('pages.gestor.quadro', ['board' => $board])
        ->set('selectedDoctors', [$estranho->id])
        ->call('saveDoctors');

    expect($board->doctors()->count())->toBe(0);
});
