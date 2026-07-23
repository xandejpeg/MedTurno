<?php

use App\Models\User;
use App\Models\Hospital;
use App\Models\Schedule;
use App\Enums\Role;
use Livewire\Volt\Volt;

test('tela de login administrativo existe somente na rota direta', function () {
    $this->get('/admin')
        ->assertOk()
        ->assertSeeVolt('pages.admin.login');

    $this->get('/login')
        ->assertOk()
        ->assertDontSee('/admin');
});

test('administrador autentica pela rota administrativa', function () {
    $admin = User::factory()->create([
        'is_admin' => true,
        'password' => 'senha-admin-segura',
    ]);

    Volt::test('pages.admin.login')
        ->set('form.email', $admin->email)
        ->set('form.password', 'senha-admin-segura')
        ->call('login')
        ->assertHasNoErrors()
        ->assertRedirect('/admin/dashboard');

    $this->assertAuthenticatedAs($admin);
});

test('usuário comum não autentica no painel administrativo', function () {
    $user = User::factory()->create([
        'is_admin' => false,
        'password' => 'senha-correta',
    ]);

    Volt::test('pages.admin.login')
        ->set('form.email', $user->email)
        ->set('form.password', 'senha-correta')
        ->call('login')
        ->assertHasErrors(['form.email'])
        ->assertNoRedirect();

    $this->assertGuest();
});

test('dashboard administrativo exige permissão e usa dados reais', function () {
    $admin = User::factory()->create(['is_admin' => true]);
    $manager = User::factory()->gestor()->create(['name' => 'Gestor Real']);

    $this->actingAs($admin)
        ->get('/admin/dashboard')
        ->assertOk()
        ->assertSee('Gestor Real');

    $this->actingAs($manager)
        ->get('/admin/dashboard')
        ->assertForbidden();
});

test('administrador consulta gestor hospitais e escalas em modo leitura', function () {
    $admin = User::factory()->create(['is_admin' => true]);
    $manager = User::factory()->gestor()->create(['name' => 'Gestora Consultada']);
    $hospital = Hospital::factory()->create(['name' => 'Hospital Consultado']);
    $manager->hospitalMemberships()->create(['hospital_id' => $hospital->id, 'role' => Role::Gestor]);
    $schedule = Schedule::factory()->create([
        'hospital_id' => $hospital->id,
        'created_by' => $manager->id,
    ]);

    $this->actingAs($admin)
        ->get('/admin/gestores/'.$manager->id)
        ->assertOk()
        ->assertSee('Gestora Consultada')
        ->assertSee('Hospital Consultado');

    $this->get('/admin/gestores/'.$manager->id.'/escalas/'.$schedule->id)
        ->assertOk()
        ->assertSee('Escala somente leitura')
        ->assertSee('Hospital Consultado');
});

test('administrador não abre escala fora dos hospitais do gestor selecionado', function () {
    $admin = User::factory()->create(['is_admin' => true]);
    $manager = User::factory()->gestor()->create();
    $otherHospital = Hospital::factory()->create();
    $schedule = Schedule::factory()->create(['hospital_id' => $otherHospital->id]);

    $this->actingAs($admin)
        ->get('/admin/gestores/'.$manager->id.'/escalas/'.$schedule->id)
        ->assertNotFound();
});

test('histórico administrativo inclui vínculo inativo de gestor', function () {
    $admin = User::factory()->create(['is_admin' => true]);
    $manager = User::factory()->create(['role' => null, 'name' => 'Gestor Histórico']);
    $hospital = Hospital::factory()->create(['name' => 'Hospital Histórico']);
    $manager->hospitalMemberships()->create([
        'hospital_id' => $hospital->id,
        'role' => Role::Gestor,
        'active' => false,
    ]);
    $schedule = Schedule::factory()->create(['hospital_id' => $hospital->id]);

    $this->actingAs($admin)
        ->get('/admin/gestores/'.$manager->id)
        ->assertOk()
        ->assertSee('Hospital Histórico')
        ->assertSee('Vínculo inativo');

    $this->get('/admin/gestores/'.$manager->id.'/escalas/'.$schedule->id)
        ->assertOk();
});