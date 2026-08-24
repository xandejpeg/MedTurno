<?php

use App\Enums\Role;
use App\Models\Hospital;
use App\Models\User;
use App\Services\ImpersonationService;

test('pagina de operadores lista gestores e usuarios', function () {
    $admin = User::factory()->create(['is_admin' => true]);

    $gestor = User::factory()->create([
        'name' => 'Gestora Operadora',
        'email' => 'gestora.op@example.com',
        'role' => Role::Gestor,
    ]);

    $hospital = Hospital::factory()->create(['name' => 'Hospital Operado']);
    $gestor->hospitalMemberships()->create([
        'hospital_id' => $hospital->id,
        'role' => Role::Gestor,
    ]);

    $this->actingAs($admin)
        ->get('/admin/operadores')
        ->assertOk()
        ->assertSee('Gestão de Operadores')
        ->assertSee('Gestora Operadora')
        ->assertSee('Hospital Operado');
});

test('usuario comum nao acessa pagina de operadores', function () {
    $gestor = User::factory()->create(['role' => Role::Gestor, 'is_admin' => false]);

    $this->actingAs($gestor)->get('/admin/operadores')->assertForbidden();
});

test('admin personifica gestor e volta ao painel', function () {
    $admin = User::factory()->create(['is_admin' => true]);
    $gestor = User::factory()->create(['role' => Role::Gestor, 'is_admin' => false]);

    // entra como o gestor
    $this->actingAs($admin)
        ->post(route('admin.impersonate.start', $gestor))
        ->assertRedirect(route('dashboard'));

    expect(auth()->id())->toBe($gestor->id);
    expect(session(ImpersonationService::SESSION_KEY))->toBe($admin->id);

    // volta para o admin
    $this->post(route('impersonate.stop'))
        ->assertRedirect(route('admin.operadores'));

    expect(auth()->id())->toBe($admin->id);
    expect(session()->has(ImpersonationService::SESSION_KEY))->toBeFalse();
});

test('admin personificando gestor consegue abrir telas de gestor', function () {
    $admin = User::factory()->create(['is_admin' => true]);
    $gestor = User::factory()->create([
        'role' => Role::Gestor,
        'is_admin' => false,
        'email_verified_at' => now(),
    ]);

    $hospital = Hospital::factory()->create(['name' => 'Hospital Visivel']);
    $gestor->hospitalMemberships()->create([
        'hospital_id' => $hospital->id,
        'role' => Role::Gestor,
    ]);

    $this->actingAs($admin)->post(route('admin.impersonate.start', $gestor));

    $this->get('/gestor/hospitais')->assertOk()->assertSee('Hospital Visivel');
});

test('nao personifica outro administrador', function () {
    $admin = User::factory()->create(['is_admin' => true]);
    $outro = User::factory()->create(['is_admin' => true]);

    $this->actingAs($admin)
        ->post(route('admin.impersonate.start', $outro))
        ->assertForbidden();

    expect(auth()->id())->toBe($admin->id);
});

test('usuario comum nao inicia personificacao', function () {
    $gestor = User::factory()->create(['role' => Role::Gestor, 'is_admin' => false]);
    $medico = User::factory()->create(['role' => Role::Medico, 'is_admin' => false]);

    $this->actingAs($gestor)
        ->post(route('admin.impersonate.start', $medico))
        ->assertForbidden();

    expect(auth()->id())->toBe($gestor->id);
});

test('banner de personificacao aparece nas telas do app', function () {
    $admin = User::factory()->create(['is_admin' => true]);
    $medico = User::factory()->create([
        'name' => 'Medico Observado',
        'role' => Role::Medico,
        'is_admin' => false,
        'email_verified_at' => now(),
    ]);

    $this->actingAs($admin)->post(route('admin.impersonate.start', $medico));

    $this->get('/medico')
        ->assertOk()
        ->assertSee('Você está vendo o sistema como')
        ->assertSee('Medico Observado');
});

test('parar personificacao sem sessao ativa redireciona para login admin', function () {
    $medico = User::factory()->create(['role' => Role::Medico, 'is_admin' => false]);

    $this->actingAs($medico)
        ->post(route('impersonate.stop'))
        ->assertRedirect(route('admin.login'));
});

test('reenviar personificacao ja ativa nao da 403 e volta ao app', function () {
    $admin = User::factory()->create(['is_admin' => true]);
    $gestor = User::factory()->create([
        'role' => Role::Gestor,
        'is_admin' => false,
        'email_verified_at' => now(),
    ]);

    // primeiro clique: entra como o gestor
    $this->actingAs($admin)
        ->post(route('admin.impersonate.start', $gestor))
        ->assertRedirect(route('dashboard'));

    // segundo clique (duplo-clique / refresh do POST): já não é admin.
    // Deve redirecionar com aviso, nunca 403.
    $this->post(route('admin.impersonate.start', $gestor))
        ->assertRedirect(route('dashboard'));

    expect(auth()->id())->toBe($gestor->id);
    expect(session(ImpersonationService::SESSION_KEY))->toBe($admin->id);

    // e o retorno continua funcionando
    $this->post(route('impersonate.stop'))->assertRedirect(route('admin.operadores'));
    expect(auth()->id())->toBe($admin->id);
});

test('personificando nao consegue abrir paginas admin mas nao toma 403', function () {
    $admin = User::factory()->create(['is_admin' => true]);
    $medico = User::factory()->create([
        'role' => Role::Medico,
        'is_admin' => false,
        'email_verified_at' => now(),
    ]);

    $this->actingAs($admin)->post(route('admin.impersonate.start', $medico));

    $this->get('/admin/operadores')->assertRedirect(route('dashboard'));
});
