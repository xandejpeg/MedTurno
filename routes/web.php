<?php

use App\Http\Controllers\ReportController;
use Illuminate\Support\Facades\Route;
use Livewire\Volt\Volt;

Route::redirect('/', '/login');

Volt::route('admin', 'pages.admin.login')
    ->name('admin.login');

Route::prefix('admin')->middleware(['auth', 'admin'])->name('admin.')->group(function () {
    Volt::route('dashboard', 'pages.admin.dashboard')
        ->name('dashboard');

    Volt::route('patch-notes', 'pages.admin.patch-notes')
        ->name('patch-notes');

    Volt::route('central', 'pages.admin.central')
        ->name('central');

    Volt::route('gestores/{manager}', 'pages.admin.manager-show')
        ->name('managers.show');

    Volt::route('gestores/{manager}/escalas/{schedule}', 'pages.admin.schedule-show')
        ->name('schedules.show');
});

Volt::route('dashboard', 'pages.dashboard')
    ->middleware(['auth', 'verified'])
    ->name('dashboard');

Route::view('profile', 'profile')
    ->middleware(['auth'])
    ->name('profile');

Route::middleware(['auth', 'verified'])->group(function () {
    Volt::route('gestor/patch-notes', 'pages.gestor.patch-notes')
        ->middleware('gestor')
        ->name('gestor.patch-notes');

    Volt::route('gestor/hospitais', 'pages.gestor.hospitais')
        ->name('gestor.hospitais');

    Volt::route('gestor/hospitais/{hospital}', 'pages.gestor.hospital')
        ->name('gestor.hospital');

    Volt::route('gestor/equipe', 'pages.gestor.equipe')
        ->name('gestor.equipe');

    Volt::route('gestor/convites', 'pages.gestor.convites')
        ->name('gestor.convites');

    Volt::route('gestor/quadros', 'pages.gestor.quadros')
        ->name('gestor.quadros');

    Volt::route('gestor/quadros/{board}', 'pages.gestor.quadro')
        ->name('gestor.quadro');

    Volt::route('gestor/recorrencias', 'pages.gestor.recorrencias')
        ->name('gestor.recorrencias');

    Volt::route('gestor/escalas', 'pages.gestor.escalas')
        ->name('gestor.escalas');

    Volt::route('gestor/escalas/nova', 'pages.gestor.escalas-nova')
        ->name('gestor.escalas.nova');

    Volt::route('gestor/escalas/{schedule}', 'pages.gestor.escala')
        ->name('gestor.escala');

    Volt::route('gestor/escalas/{schedule}/montar', 'pages.gestor.escala-montar')
        ->name('gestor.escala.montar');

    Volt::route('gestor/trocas', 'pages.gestor.trocas')
        ->name('gestor.trocas');

    Volt::route('gestor/faturamento', 'pages.gestor.faturamento')
        ->name('gestor.faturamento');

    Route::get('gestor/relatorio', [ReportController::class, 'monthly'])
        ->name('gestor.relatorio');

    Volt::route('medico', 'pages.medico.painel')
        ->name('medico.painel');

    Volt::route('medico/escala', 'pages.medico.escala')
        ->name('medico.escala');

    Volt::route('medico/trocas', 'pages.medico.trocas')
        ->name('medico.trocas');

    Volt::route('medico/mural', 'pages.medico.mural')
        ->name('medico.mural');

    Volt::route('medico/plantoes/{shift}', 'pages.medico.plantao')
        ->name('medico.plantao');

    Volt::route('notificacoes', 'pages.notificacoes')
        ->name('notificacoes');
});

Volt::route('convite/aceitar', 'pages.convite.aceitar')
    ->middleware('throttle:20,1')
    ->name('convite.aceitar');

require __DIR__.'/auth.php';
