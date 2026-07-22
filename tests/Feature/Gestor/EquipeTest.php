<?php

use App\Enums\InvitationStatus;
use App\Enums\Role;
use App\Mail\ConviteMedico;
use App\Models\Hospital;
use App\Models\Invitation;
use App\Models\User;
use App\Services\InvitationService;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Support\Facades\Mail;
use Livewire\Volt\Volt;

function makeGestor(): array
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

test('gestor can invite a medico and email is queued', function () {
    Mail::fake();
    [$gestor, $hospital] = makeGestor();
    $this->actingAs($gestor);

    Volt::test('pages.gestor.equipe')
        ->set('name', 'Dra. Ana')
        ->set('email', 'ana@teste.com')
        ->call('invite');

    $invitation = Invitation::where('email', 'ana@teste.com')->first();

    expect($invitation)->not->toBeNull()
        ->and($invitation->status)->toBe(InvitationStatus::Pendente)
        ->and($invitation->hospital_id)->toBe($hospital->id);

    Mail::assertQueued(ConviteMedico::class, fn ($mail) => $mail->hasTo('ana@teste.com'));
});

test('accepting an invite creates the user with medico membership', function () {
    Mail::fake();
    [$gestor, $hospital] = makeGestor();

    $service = app(InvitationService::class);
    $invitation = $service->invite($hospital, $gestor, 'Dr. Bruno', 'bruno@teste.com');

    // não temos acesso ao token cru gerado; substituímos o hash por um conhecido
    $rawToken = 'token-teste-123';
    $invitation->update(['token_hash' => hash('sha256', $rawToken)]);

    $user = $service->accept($rawToken, 'senha-segura-8');

    expect($user->email)->toBe('bruno@teste.com')
        ->and($user->hospitalMemberships()->where('hospital_id', $hospital->id)->where('role', Role::Medico->value)->exists())->toBeTrue()
        ->and($invitation->fresh()->status)->toBe(InvitationStatus::Aceito);
});

test('accepting an invite for existing user only adds membership', function () {
    Mail::fake();
    [$gestor, $hospital] = makeGestor();
    $existing = User::factory()->create(['email' => 'ja-existe@teste.com']);

    $service = app(InvitationService::class);
    $invitation = $service->invite($hospital, $gestor, 'Dr. Já Existe', 'ja-existe@teste.com');
    $rawToken = 'token-existente';
    $invitation->update(['token_hash' => hash('sha256', $rawToken)]);

    $user = $service->accept($rawToken);

    expect($user->id)->toBe($existing->id)
        ->and(User::where('email', 'ja-existe@teste.com')->count())->toBe(1)
        ->and($user->hospitalMemberships()->where('hospital_id', $hospital->id)->exists())->toBeTrue();
});

test('expired invite cannot be accepted', function () {
    Mail::fake();
    [$gestor, $hospital] = makeGestor();

    $service = app(InvitationService::class);
    $invitation = $service->invite($hospital, $gestor, 'Dr. Atrasado', 'atrasado@teste.com');
    $rawToken = 'token-expirado';
    $invitation->update([
        'token_hash' => hash('sha256', $rawToken),
        'expires_at' => now()->subDay(),
    ]);

    expect(fn () => $service->accept($rawToken, 'senha-valida-8'))
        ->toThrow(InvalidArgumentException::class);
});

test('resending an invite cancels the previous one', function () {
    Mail::fake();
    [$gestor, $hospital] = makeGestor();

    $service = app(InvitationService::class);
    $first = $service->invite($hospital, $gestor, 'Dra. Carla', 'carla@teste.com');
    $second = $service->invite($hospital, $gestor, 'Dra. Carla', 'carla@teste.com');

    expect($first->fresh()->status)->toBe(InvitationStatus::Cancelado)
        ->and($second->fresh()->status)->toBe(InvitationStatus::Pendente);
});

test('gestor can deactivate and reactivate a medico', function () {
    [$gestor, $hospital] = makeGestor();
    $medico = User::factory()->create();
    $membership = $medico->hospitalMemberships()->create([
        'hospital_id' => $hospital->id,
        'role' => Role::Medico,
    ]);

    $this->actingAs($gestor);

    Volt::test('pages.gestor.equipe')->call('toggleActive', $membership->id);
    expect($membership->fresh()->active)->toBeFalse();

    Volt::test('pages.gestor.equipe')->call('toggleActive', $membership->id);
    expect($membership->fresh()->active)->toBeTrue();
});

test('invite accept page shows error for invalid token', function () {
    $this->get('/convite/aceitar?token=nao-existe')
        ->assertOk()
        ->assertSee('Convite inválido');
});

test('gestor cannot resend invite from another hospital', function () {
    Mail::fake();
    [$gestorA, $hospitalA] = makeGestor();
    [$gestorB, $hospitalB] = makeGestor();

    $service = app(InvitationService::class);
    $inviteB = $service->invite($hospitalB, $gestorB, 'Dr. Alheio', 'alheio@teste.com');

    session(['current_hospital_id' => $hospitalA->id]);
    $this->actingAs($gestorA);

    Volt::test('pages.gestor.equipe')
        ->call('resend', $inviteB->id);
})->throws(ModelNotFoundException::class);

test('invite exposes a usable plain token that opens the accept flow', function () {
    Mail::fake();
    [$gestor, $hospital] = makeGestor();

    $invitation = app(InvitationService::class)
        ->invite($hospital, $gestor, 'Dra. Link', 'link@teste.com');

    expect($invitation->plainToken)->not->toBeNull();

    // o link mandado no zap leva pra tela de aceite válida
    $this->get('/convite/aceitar?token='.$invitation->plainToken)
        ->assertOk()
        ->assertSee($hospital->name)
        ->assertSee('Dra. Link');
});

test('inviting surfaces a copyable link and whatsapp url in the equipe screen', function () {
    Mail::fake();
    [$gestor, $hospital] = makeGestor();
    $this->actingAs($gestor);

    Volt::test('pages.gestor.equipe')
        ->set('name', 'Dra. Zap')
        ->set('email', 'zap@teste.com')
        ->set('phone', '+55 81 99999-8888')
        ->call('invite')
        ->assertSet('invitedName', 'Dra. Zap')
        ->assertSeeHtml('/convite/aceitar')
        ->assertSeeHtml('wa.me/5581999998888');
});

test('equipe renderiza sem quebrar quando existe link de grupo pendente', function () {
    Mail::fake();
    [$gestor, $hospital] = makeGestor();
    app(InvitationService::class)->createGroupLink($hospital, $gestor);
    $this->actingAs($gestor);

    Volt::test('pages.gestor.equipe')->assertOk();
});
