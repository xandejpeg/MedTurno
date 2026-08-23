<?php

use App\Models\User;

test('administrador acessa patch notes no painel administrativo', function () {
    $admin = User::factory()->create(['is_admin' => true]);

    $this->actingAs($admin)
        ->get(route('admin.patch-notes'))
        ->assertOk()
        ->assertSee('Patch Notes')
        ->assertSee('v1.0.1')
        ->assertSee('Notificações por e-mail prontas')
        ->assertSee('v1.0.0')
        ->assertSee('Primeira versão do DoctorTurn');
});

test('gestor acessa patch notes no painel de gestão', function () {
    $gestor = User::factory()->gestor()->create();

    $this->actingAs($gestor)
        ->get(route('gestor.patch-notes'))
        ->assertOk()
        ->assertSee('Patch Notes')
        ->assertSee('v1.0.1')
        ->assertSee('29/07/2026 até as 18:00')
        ->assertSee('v1.0.0')
        ->assertSee('Replicação de uma escala para outros meses');
});

test('médico não acessa patch notes de gestores', function () {
    $medico = User::factory()->medico()->create();

    $this->actingAs($medico)
        ->get(route('gestor.patch-notes'))
        ->assertForbidden();
});

test('visitante é redirecionado ao tentar acessar patch notes', function () {
    $this->get(route('gestor.patch-notes'))
        ->assertRedirect(route('login'));
});

test('página permanece disponível quando ainda não existem versões', function () {
    config(['patch-notes' => []]);
    $gestor = User::factory()->gestor()->create();

    $this->actingAs($gestor)
        ->get(route('gestor.patch-notes'))
        ->assertOk()
        ->assertSee('Nenhuma versão publicada');
});