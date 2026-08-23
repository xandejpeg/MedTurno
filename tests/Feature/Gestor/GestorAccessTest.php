<?php

use App\Models\User;
use Illuminate\Support\Facades\Route;

beforeEach(function () {
    Route::get('/_gestor-access-test', fn () => 'ok')
        ->middleware(['web', 'auth', 'gestor']);
});

test('gestor acessa rotas exclusivas do painel', function () {
    $gestor = User::factory()->gestor()->create();

    $this->actingAs($gestor)
        ->get('/_gestor-access-test')
        ->assertOk();
});

test('médico não acessa rotas exclusivas do gestor', function () {
    $medico = User::factory()->medico()->create();

    $this->actingAs($medico)
        ->get('/_gestor-access-test')
        ->assertForbidden();
});