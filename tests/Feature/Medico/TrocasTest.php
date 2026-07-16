<?php

use App\Enums\Role;
use App\Enums\ScheduleStatus;
use App\Enums\ShiftStatus;
use App\Enums\TransferStatus;
use App\Mail\TrocaAtualizada;
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
 * @return array{0: User, 1: User, 2: User, 3: Hospital, 4: Shift}
 */
function trocaSetup(ShiftStatus $shiftStatus = ShiftStatus::Confirmado): array
{
    $gestor = User::factory()->create();
    $hospital = Hospital::factory()->create();
    $gestor->hospitalMemberships()->create(['hospital_id' => $hospital->id, 'role' => Role::Gestor]);

    $medicoA = User::factory()->create();
    $medicoA->hospitalMemberships()->create(['hospital_id' => $hospital->id, 'role' => Role::Medico]);

    $medicoB = User::factory()->create();
    $medicoB->hospitalMemberships()->create(['hospital_id' => $hospital->id, 'role' => Role::Medico]);

    $board = ShiftBoard::factory()->for($hospital)->create();
    $schedule = Schedule::factory()->for($hospital)->create([
        'shift_board_id' => $board->id,
        'status' => ScheduleStatus::Publicada,
        'created_by' => $gestor->id,
    ]);

    $shift = Shift::factory()->for($schedule)->create([
        'user_id' => $medicoA->id,
        'status' => $shiftStatus,
        'amount' => 1200,
        'confirmed_at' => $shiftStatus === ShiftStatus::Confirmado ? now() : null,
    ]);

    return [$medicoA, $medicoB, $gestor, $hospital, $shift];
}

function transferService(): TransferService
{
    return app(TransferService::class);
}

test('médico pede troca direta: shift vira em_troca e receptor é notificado', function () {
    Mail::fake();
    [$a, $b, , , $shift] = trocaSetup();

    $transfer = transferService()->requestDirect($shift, $a, $b, 'Viagem');

    expect($transfer->status)->toBe(TransferStatus::AguardandoReceptor)
        ->and($shift->refresh()->status)->toBe(ShiftStatus::EmTroca)
        ->and(Notification::where('user_id', $b->id)->where('type', 'troca_pendente')->exists())->toBeTrue();

    Mail::assertQueued(TrocaAtualizada::class, 1);
});

test('não pode pedir troca de plantão que não é seu', function () {
    Mail::fake();
    [$a, $b, , , $shift] = trocaSetup();

    transferService()->requestDirect($shift, $b, $a);
})->throws(InvalidArgumentException::class, 'não é seu');

test('não pode haver duas trocas ativas pro mesmo plantão', function () {
    Mail::fake();
    [$a, $b, , , $shift] = trocaSetup();

    transferService()->requestDirect($shift, $a, $b);
    transferService()->requestDirect($shift->refresh(), $a, $b);
})->throws(InvalidArgumentException::class);

test('não pode passar pra quem não atua no hospital', function () {
    Mail::fake();
    [$a, , , , $shift] = trocaSetup();
    $estranho = User::factory()->create();

    transferService()->requestDirect($shift, $a, $estranho);
})->throws(InvalidArgumentException::class, 'não atua neste hospital');

test('receptor aceita: aguardando_gestor + notifica gestor e dono', function () {
    Mail::fake();
    [$a, $b, $gestor, , $shift] = trocaSetup();
    $transfer = transferService()->requestDirect($shift, $a, $b);

    transferService()->acceptByReceiver($transfer, $b);

    expect($transfer->refresh()->status)->toBe(TransferStatus::AguardandoGestor)
        ->and(Notification::where('user_id', $gestor->id)->where('type', 'troca_pendente')->exists())->toBeTrue()
        ->and(Notification::where('user_id', $a->id)->where('type', 'troca_aceita')->exists())->toBeTrue();
});

test('só o receptor pode aceitar', function () {
    Mail::fake();
    [$a, $b, , , $shift] = trocaSetup();
    $transfer = transferService()->requestDirect($shift, $a, $b);

    transferService()->acceptByReceiver($transfer, $a);
})->throws(InvalidArgumentException::class, 'não é pra você');

test('receptor recusa: plantão volta confirmado pro dono', function () {
    Mail::fake();
    [$a, $b, , , $shift] = trocaSetup(ShiftStatus::Confirmado);
    $transfer = transferService()->requestDirect($shift, $a, $b);

    transferService()->rejectByReceiver($transfer, $b);

    $shift->refresh();
    expect($transfer->refresh()->status)->toBe(TransferStatus::Recusada)
        ->and($transfer->decided_by)->toBe($b->id)
        ->and($shift->user_id)->toBe($a->id)
        ->and($shift->status)->toBe(ShiftStatus::Confirmado)
        ->and(Notification::where('user_id', $a->id)->where('type', 'troca_recusada')->exists())->toBeTrue();
});

test('dono cancela proposta: plantão volta pendente', function () {
    Mail::fake();
    [$a, $b, , , $shift] = trocaSetup(ShiftStatus::Pendente);
    $transfer = transferService()->requestDirect($shift, $a, $b);

    transferService()->cancelByOwner($transfer, $a);

    $shift->refresh();
    expect($transfer->refresh()->status)->toBe(TransferStatus::Cancelada)
        ->and($shift->status)->toBe(ShiftStatus::Pendente)
        ->and($shift->user_id)->toBe($a->id);
});

test('dono não cancela depois do receptor aceitar', function () {
    Mail::fake();
    [$a, $b, , , $shift] = trocaSetup();
    $transfer = transferService()->requestDirect($shift, $a, $b);
    transferService()->acceptByReceiver($transfer, $b);

    transferService()->cancelByOwner($transfer->refresh(), $a);
})->throws(InvalidArgumentException::class);

test('gestor aprova: plantão vira do receptor como pendente, valor preservado', function () {
    Mail::fake();
    [$a, $b, $gestor, , $shift] = trocaSetup();
    $transfer = transferService()->requestDirect($shift, $a, $b);
    transferService()->acceptByReceiver($transfer, $b);

    transferService()->approve($transfer->refresh(), $gestor);

    $shift->refresh();
    expect($transfer->refresh()->status)->toBe(TransferStatus::Aprovada)
        ->and($transfer->decided_by)->toBe($gestor->id)
        ->and($shift->user_id)->toBe($b->id)
        ->and($shift->status)->toBe(ShiftStatus::Pendente)
        ->and($shift->confirmed_at)->toBeNull()
        ->and((float) $shift->amount)->toBe(1200.0)
        ->and(Notification::where('user_id', $a->id)->where('type', 'troca_aprovada')->exists())->toBeTrue()
        ->and(Notification::where('user_id', $b->id)->where('type', 'troca_aprovada')->exists())->toBeTrue();
});

test('gestor não aprova antes do receptor aceitar', function () {
    Mail::fake();
    [$a, $b, $gestor, , $shift] = trocaSetup();
    $transfer = transferService()->requestDirect($shift, $a, $b);

    transferService()->approve($transfer, $gestor);
})->throws(InvalidArgumentException::class, 'não está aguardando aprovação');

test('gestor de outro hospital não aprova', function () {
    Mail::fake();
    [$a, $b, , , $shift] = trocaSetup();
    $transfer = transferService()->requestDirect($shift, $a, $b);
    transferService()->acceptByReceiver($transfer, $b);

    $outroGestor = User::factory()->create();
    $outroHospital = Hospital::factory()->create();
    $outroGestor->hospitalMemberships()->create(['hospital_id' => $outroHospital->id, 'role' => Role::Gestor]);

    transferService()->approve($transfer->refresh(), $outroGestor);
})->throws(InvalidArgumentException::class, 'não é gestor');

test('gestor recusa: plantão volta pro dono original', function () {
    Mail::fake();
    [$a, $b, $gestor, , $shift] = trocaSetup(ShiftStatus::Confirmado);
    $transfer = transferService()->requestDirect($shift, $a, $b);
    transferService()->acceptByReceiver($transfer, $b);

    transferService()->reject($transfer->refresh(), $gestor);

    $shift->refresh();
    expect($transfer->refresh()->status)->toBe(TransferStatus::Recusada)
        ->and($shift->user_id)->toBe($a->id)
        ->and($shift->status)->toBe(ShiftStatus::Confirmado)
        ->and(Notification::where('user_id', $b->id)->where('type', 'troca_recusada')->exists())->toBeTrue();
});

test('página medico/trocas: receptor aceita pela UI', function () {
    Mail::fake();
    [$a, $b, , , $shift] = trocaSetup();
    $transfer = transferService()->requestDirect($shift, $a, $b);

    Volt::actingAs($b)
        ->test('pages.medico.trocas')
        ->call('accept', $transfer->id)
        ->assertHasNoErrors();

    expect($transfer->refresh()->status)->toBe(TransferStatus::AguardandoGestor);
});

test('página gestor/trocas: gestor aprova pela UI', function () {
    Mail::fake();
    [$a, $b, $gestor, $hospital, $shift] = trocaSetup();
    $transfer = transferService()->requestDirect($shift, $a, $b);
    transferService()->acceptByReceiver($transfer, $b);
    session(['current_hospital_id' => $hospital->id]);

    Volt::actingAs($gestor)
        ->test('pages.gestor.trocas')
        ->call('approve', $transfer->id)
        ->assertHasNoErrors();

    expect($shift->refresh()->user_id)->toBe($b->id);
});

test('modal do plantão: dono envia proposta pela UI', function () {
    Mail::fake();
    [$a, $b, , , $shift] = trocaSetup();

    Volt::actingAs($a)
        ->test('pages.medico.plantao', ['shift' => $shift])
        ->set('colleagueId', (string) $b->id)
        ->set('reason', 'Compromisso')
        ->call('requestTransfer')
        ->assertHasNoErrors();

    expect($shift->refresh()->status)->toBe(ShiftStatus::EmTroca)
        ->and($shift->transfers()->count())->toBe(1);
});
