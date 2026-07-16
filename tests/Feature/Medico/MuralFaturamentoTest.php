<?php

use App\Enums\InterestStatus;
use App\Enums\Role;
use App\Enums\ScheduleStatus;
use App\Enums\ShiftStatus;
use App\Enums\TransferStatus;
use App\Enums\TransferType;
use App\Models\Hospital;
use App\Models\Notification;
use App\Models\Schedule;
use App\Models\Shift;
use App\Models\ShiftBoard;
use App\Models\User;
use App\Services\TransferService;
use Illuminate\Support\Facades\Mail;
use Livewire\Volt\Volt;

/**
 * @return array{0: User, 1: User, 2: User, 3: User, 4: Hospital, 5: Shift, 6: ShiftBoard}
 */
function muralSetup(): array
{
    $gestor = User::factory()->create();
    $hospital = Hospital::factory()->create();
    $gestor->hospitalMemberships()->create(['hospital_id' => $hospital->id, 'role' => Role::Gestor]);

    $board = ShiftBoard::factory()->for($hospital)->create();

    $doctors = collect();
    foreach (range(1, 3) as $i) {
        $medico = User::factory()->create();
        $medico->hospitalMemberships()->create(['hospital_id' => $hospital->id, 'role' => Role::Medico]);
        $board->doctors()->attach($medico);
        $doctors->push($medico);
    }

    [$a, $b, $c] = $doctors;

    $schedule = Schedule::factory()->for($hospital)->create([
        'shift_board_id' => $board->id,
        'status' => ScheduleStatus::Publicada,
        'created_by' => $gestor->id,
    ]);

    $shift = Shift::factory()->for($schedule)->create([
        'shift_board_id' => $board->id,
        'user_id' => $a->id,
        'status' => ShiftStatus::Confirmado,
        'amount' => 1200,
        'confirmed_at' => now(),
        'date' => now()->addDays(5),
    ]);

    return [$a, $b, $c, $gestor, $hospital, $shift, $board];
}

function muralService(): TransferService
{
    return app(TransferService::class);
}

test('anunciar no mural: shift vira disponivel e colegas do quadro são notificados', function () {
    Mail::fake();
    [$a, $b, $c, , , $shift] = muralSetup();

    $transfer = muralService()->announce($shift, $a, 'Não consigo ir');

    expect($transfer->type)->toBe(TransferType::Mural)
        ->and($transfer->status)->toBe(TransferStatus::AguardandoReceptor)
        ->and($shift->refresh()->status)->toBe(ShiftStatus::Disponivel)
        ->and(Notification::where('user_id', $b->id)->where('type', 'mural_anuncio')->exists())->toBeTrue()
        ->and(Notification::where('user_id', $c->id)->where('type', 'mural_anuncio')->exists())->toBeTrue()
        ->and(Notification::where('user_id', $a->id)->where('type', 'mural_anuncio')->exists())->toBeFalse();
});

test('não pode ter interesse no próprio plantão nem sem ser do quadro', function () {
    Mail::fake();
    [$a, , , , $hospital, $shift] = muralSetup();

    muralService()->announce($shift, $a);
    $shift->refresh();

    expect(fn () => muralService()->expressInterest($shift, $a))
        ->toThrow(InvalidArgumentException::class, 'próprio plantão');

    $fora = User::factory()->create();
    $fora->hospitalMemberships()->create(['hospital_id' => $hospital->id, 'role' => Role::Medico]);

    expect(fn () => muralService()->expressInterest($shift, $fora))
        ->toThrow(InvalidArgumentException::class, 'não participa deste quadro');
});

test('gestor aprova um interessado: vencedor leva, perdedor é rejeitado_auto e notificado', function () {
    Mail::fake();
    [$a, $b, $c, $gestor, , $shift] = muralSetup();

    muralService()->announce($shift, $a);
    $shift->refresh();

    $interestB = muralService()->expressInterest($shift, $b);
    $interestC = muralService()->expressInterest($shift, $c);

    muralService()->approveInterest($interestB, $gestor);

    $shift->refresh();

    expect($shift->user_id)->toBe($b->id)
        ->and($shift->status)->toBe(ShiftStatus::Pendente)
        ->and($shift->confirmed_at)->toBeNull()
        ->and($shift->amount)->toBe('1200.00')
        ->and($interestB->refresh()->status)->toBe(InterestStatus::Aprovado)
        ->and($interestC->refresh()->status)->toBe(InterestStatus::RejeitadoAuto)
        ->and($shift->transfers()->first()->status)->toBe(TransferStatus::Aprovada)
        ->and($shift->transfers()->first()->to_user_id)->toBe($b->id)
        ->and(Notification::where('user_id', $c->id)->where('type', 'mural_perdeu')->exists())->toBeTrue()
        ->and(Notification::where('user_id', $b->id)->where('type', 'mural_aprovado')->exists())->toBeTrue();
});

test('cancelar anúncio restaura o plantão e encerra interesses', function () {
    Mail::fake();
    [$a, $b, , , , $shift] = muralSetup();

    $transfer = muralService()->announce($shift, $a);
    $shift->refresh();
    $interest = muralService()->expressInterest($shift, $b);

    muralService()->cancelAnnouncement($transfer, $a);

    expect($shift->refresh()->status)->toBe(ShiftStatus::Confirmado)
        ->and($shift->user_id)->toBe($a->id)
        ->and($interest->refresh()->status)->toBe(InterestStatus::CanceladoAuto)
        ->and($transfer->refresh()->status)->toBe(TransferStatus::Cancelada);
});

test('médico manifesta e retira interesse pelo mural (UI)', function () {
    Mail::fake();
    [$a, $b, , , , $shift] = muralSetup();

    muralService()->announce($shift, $a);

    Volt::actingAs($b)
        ->test('pages.medico.mural')
        ->assertSee($shift->date->format('d/m/Y'))
        ->call('interest', $shift->id)
        ->call('withdraw', $shift->id);

    expect($shift->interests()->first()->status)->toBe(InterestStatus::Retirado);
});

test('gestor edita o valor de um plantão individual no editor de escala', function () {
    [, , , $gestor, $hospital, $shift] = muralSetup();

    session(['current_hospital_id' => $hospital->id]);

    Volt::actingAs($gestor)
        ->test('pages.gestor.escala', ['schedule' => $shift->schedule])
        ->call('openShift', $shift->id)
        ->set('amount', '1500')
        ->call('saveShift')
        ->assertHasNoErrors();

    expect($shift->refresh()->amount)->toBe('1500.00');
});

test('faturamento soma só confirmados/concluídos e mostra cancelados à parte', function () {
    [$a, , , $gestor, $hospital, $shift] = muralSetup();

    Shift::factory()->for($shift->schedule)->create([
        'shift_board_id' => $shift->shift_board_id,
        'user_id' => $a->id,
        'status' => ShiftStatus::Cancelado,
        'amount' => 900,
        'date' => $shift->date,
    ]);

    session(['current_hospital_id' => $hospital->id]);

    Volt::actingAs($gestor)
        ->test('pages.gestor.faturamento', ['month' => $shift->date->format('Y-m')])
        ->assertSee($a->name)
        ->assertSee('R$ 1.200,00')
        ->assertSee('1 cancelado(s)/não cumprido(s)')
        ->assertDontSee('R$ 2.100,00');
});
