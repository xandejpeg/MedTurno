<?php

use App\Enums\InvitationStatus;
use App\Enums\InvitationType;
use App\Enums\Role;
use App\Models\Hospital;
use App\Models\Invitation;
use App\Models\ShiftBoard;
use App\Models\User;
use App\Services\InvitationService;
use Livewire\Volt\Volt;

/**
 * @return array{0: User, 1: Hospital}
 */
function gestorComHospital(): array
{
    $user = User::factory()->create();
    $hospital = Hospital::factory()->create();
    $user->hospitalMemberships()->create([
        'hospital_id' => $hospital->id,
        'role' => Role::Gestor,
    ]);
    session(['current_hospital_id' => $hospital->id]);

    return [$user, $hospital];
}

test('gestor gera um link de grupo reutilizável e sem expiração', function () {
    [$gestor, $hospital] = gestorComHospital();

    $link = app(InvitationService::class)->createGroupLink($hospital, $gestor);

    expect($link->type)->toBe(InvitationType::Grupo)
        ->and($link->plainToken)->not->toBeNull()
        ->and($link->plain_token)->toBe($link->plainToken)
        ->and($link->expires_at)->toBeNull()
        ->and($link->email)->toBeNull()
        ->and($link->isUsable())->toBeTrue();
});

test('duas pessoas entram pelo mesmo link de grupo e viram médicos do hospital', function () {
    [$gestor, $hospital] = gestorComHospital();
    $service = app(InvitationService::class);
    $link = $service->createGroupLink($hospital, $gestor);

    $a = $service->acceptGroup($link->plainToken, [
        'name' => 'Dra. Ana Grupo', 'email' => 'ana.grupo@teste.com', 'cpf' => '12345678901',
        'phone' => '+5581999990001', 'crm' => '11111', 'crm_uf' => 'PE', 'password' => 'senha-segura-8',
    ]);

    $b = $service->acceptGroup($link->plainToken, [
        'name' => 'Dr. Bruno Grupo', 'email' => 'bruno.grupo@teste.com', 'cpf' => '98765432100',
        'phone' => '+5581999990002', 'crm' => '22222', 'crm_uf' => 'PE', 'password' => 'senha-segura-8',
    ]);

    expect($a->cpf)->toBe('12345678901')
        ->and($a->crm)->toBe('11111')
        ->and($a->hospitalMemberships()->where('hospital_id', $hospital->id)->where('role', Role::Medico->value)->exists())->toBeTrue()
        ->and($b->hospitalMemberships()->where('hospital_id', $hospital->id)->where('role', Role::Medico->value)->exists())->toBeTrue()
        ->and($link->fresh()->memberships()->count())->toBe(2)
        ->and($link->fresh()->isUsable())->toBeTrue();
});

test('link de grupo com quadro já coloca a pessoa no quadro', function () {
    [$gestor, $hospital] = gestorComHospital();
    $board = ShiftBoard::factory()->create(['hospital_id' => $hospital->id]);
    $service = app(InvitationService::class);

    $link = $service->createGroupLink($hospital, $gestor, $board);
    $user = $service->acceptGroup($link->plainToken, [
        'name' => 'Dra. Quadro', 'email' => 'quadro@teste.com', 'cpf' => '11122233344',
        'phone' => '+5581999990003', 'crm' => '33333', 'password' => 'senha-segura-8',
    ]);

    expect($board->doctors()->whereKey($user->id)->exists())->toBeTrue();
});

test('accept individual rejeita um token de grupo', function () {
    [$gestor, $hospital] = gestorComHospital();
    $link = app(InvitationService::class)->createGroupLink($hospital, $gestor);

    expect(fn () => app(InvitationService::class)->accept($link->plainToken, 'senha-segura-8'))
        ->toThrow(InvalidArgumentException::class);
});

test('gerar novo link de grupo cancela o anterior do mesmo escopo', function () {
    [$gestor, $hospital] = gestorComHospital();
    $service = app(InvitationService::class);

    $first = $service->createGroupLink($hospital, $gestor);
    $second = $service->createGroupLink($hospital, $gestor);

    expect($first->fresh()->status)->toBe(InvitationStatus::Cancelado)
        ->and($second->fresh()->status)->toBe(InvitationStatus::Pendente);
});

test('cadastroCompleto exige nome, email, celular, cpf e crm', function () {
    $completo = User::factory()->create([
        'phone' => '+5581999990004', 'cpf' => '11111111111', 'crm' => '44444',
    ]);
    $incompleto = User::factory()->create([
        'phone' => null, 'cpf' => null, 'crm' => null,
    ]);

    expect($completo->cadastroCompleto())->toBeTrue()
        ->and($incompleto->cadastroCompleto())->toBeFalse();
});

test('página de aceite do link de grupo mostra o formulário de cadastro', function () {
    [$gestor, $hospital] = gestorComHospital();
    $link = app(InvitationService::class)->createGroupLink($hospital, $gestor);

    $this->get('/convite/aceitar?token='.$link->plainToken)
        ->assertOk()
        ->assertSee($hospital->name)
        ->assertSee('Nome completo')
        ->assertSee('CPF')
        ->assertSee('Buscar país ou código')
        ->assertSee('Número com DDD')
        ->assertDontSee('Foto (opcional)')
        ->assertDontSee('Tirar foto / enviar');
});

test('cadastro por link de grupo rejeita celular curto', function () {
    Volt::test('pages.convite.aceitar')
        ->set('valid', true)
        ->set('isGroup', true)
        ->set('name', 'Dr. Celular')
        ->set('email', 'celular@teste.com')
        ->set('cpf', '12345678901')
        ->set('phoneCountry', 'BR')
        ->set('phoneNumber', '12345')
        ->set('crm', '18647')
        ->set('crm_uf', 'ES')
        ->set('password', 'senha-segura-8')
        ->set('password_confirmation', 'senha-segura-8')
        ->call('register')
        ->assertHasErrors(['phoneNumber'])
        ->assertSee('Digite um celular válido para o país selecionado.');

    expect(User::where('email', 'celular@teste.com')->exists())->toBeFalse();
});

test('cadastro por link de grupo aceita celular internacional', function () {
    Volt::test('pages.convite.aceitar')
        ->set('valid', true)
        ->set('isGroup', true)
        ->set('name', 'Dr. Internacional')
        ->set('email', 'internacional.grupo@teste.com')
        ->set('cpf', '12345678901')
        ->set('phoneCountry', 'NL')
        ->set('phoneNumber', '6 87171924')
        ->set('crm', '18647')
        ->set('crm_uf', 'ES')
        ->set('password', 'senha-segura-8')
        ->set('password_confirmation', 'senha-segura-8')
        ->call('register')
        ->assertHasNoErrors('phoneNumber');
});

test('painel de convites gera link e lista a equipe com status de cadastro', function () {
    [$gestor, $hospital] = gestorComHospital();
    $this->actingAs($gestor);

    Volt::test('pages.gestor.convites')
        ->set('boardId', '')
        ->call('generate')
        ->assertHasNoErrors();

    expect(Invitation::where('hospital_id', $hospital->id)->where('type', InvitationType::Grupo)->where('status', InvitationStatus::Pendente)->exists())
        ->toBeTrue();
});

test('gestor adiciona um médico a um quadro pela tela de convites', function () {
    [$gestor, $hospital] = gestorComHospital();
    $board = ShiftBoard::factory()->create(['hospital_id' => $hospital->id]);
    $medico = User::factory()->create();
    $medico->hospitalMemberships()->create(['hospital_id' => $hospital->id, 'role' => Role::Medico]);
    $this->actingAs($gestor);

    Volt::test('pages.gestor.convites')
        ->call('addToBoard', $medico->id, $board->id)
        ->assertHasNoErrors();

    expect($board->doctors()->whereKey($medico->id)->exists())->toBeTrue();
});
