<?php

use App\Models\User;
use Illuminate\Support\Facades\Route;

beforeEach(function () {
    Route::get('/_admin-access-test', fn () => 'ok')
        ->middleware(['web', 'auth', 'admin']);
});

test('administrador acessa rotas administrativas', function () {
    $admin = User::factory()->create(['is_admin' => true]);

    $this->actingAs($admin)
        ->get('/_admin-access-test')
        ->assertOk();
});

test('usuário comum não acessa rotas administrativas', function () {
    $user = User::factory()->create(['is_admin' => false]);

    $this->actingAs($user)
        ->get('/_admin-access-test')
        ->assertForbidden();
});