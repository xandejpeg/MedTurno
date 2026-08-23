<?php

use App\Models\Hospital;
use App\Models\Invoice;
use App\Models\User;
use App\Services\InvoiceService;
use App\Services\NfseService;
use Illuminate\Support\Carbon;

beforeEach(function () {
    $this->hospital = Hospital::factory()->create(['name' => 'Hospital Teste', 'cnpj' => '12.345.678/0001-90']);
    $this->gestor = User::factory()->create(['role' => 'gestor']);
    $this->hospital->memberships()->create([
        'user_id' => $this->gestor->id,
        'role' => 'gestor',
        'active' => true,
    ]);
});

it('generates base data for nfse', function () {
    $service = app(InvoiceService::class);

    $base = $service->baseData($this->hospital, Carbon::parse('2026-08-01'), Carbon::parse('2026-08-31'));

    expect($base['tomador']['name'])->toBe('Hospital Teste');
    expect($base['tomador']['cnpj'])->toBe('12.345.678/0001-90');
    expect($base['periodo']['inicio'])->toBe('2026-08-01');
    expect($base['periodo']['fim'])->toBe('2026-08-31');
    expect($base['valor_total'])->toBeFloat();
});

it('registers invoice as rascunho when no number', function () {
    $service = app(InvoiceService::class);

    $invoice = $service->register(
        $this->hospital,
        Carbon::parse('2026-08-01'),
        Carbon::parse('2026-08-31'),
        1500.00,
    );

    expect($invoice->status)->toBe('rascunho');
    expect($invoice->number)->toBeNull();
    expect($invoice->statusLabel())->toBe('Rascunho');
});

it('registers invoice as emitida when number provided', function () {
    $service = app(InvoiceService::class);

    $invoice = $service->register(
        $this->hospital,
        Carbon::parse('2026-08-01'),
        Carbon::parse('2026-08-31'),
        1500.00,
        '12345',
        Carbon::parse('2026-08-03'),
    );

    expect($invoice->status)->toBe('emitida');
    expect($invoice->number)->toBe('12345');
    expect($invoice->statusLabel())->toBe('Emitida');
});

it('issues nfse as rascunho when provider not configured', function () {
    config(['services.nfse.url' => null, 'services.nfse.token' => null]);

    $service = app(NfseService::class);
    $invoice = $service->issue($this->hospital, Carbon::parse('2026-08-01'), Carbon::parse('2026-08-31'));

    expect($invoice->status)->toBe('rascunho');
    expect($invoice->number)->toBeNull();
});

it('is not configured when url or token missing', function () {
    config(['services.nfse.url' => null, 'services.nfse.token' => 'abc']);
    expect(app(NfseService::class)->isConfigured())->toBeFalse();

    config(['services.nfse.url' => 'https://api.example.com', 'services.nfse.token' => null]);
    expect(app(NfseService::class)->isConfigured())->toBeFalse();
});

it('is configured when url and token set', function () {
    config(['services.nfse.url' => 'https://api.example.com', 'services.nfse.token' => 'abc']);
    expect(app(NfseService::class)->isConfigured())->toBeTrue();
});

it('cancels invoice', function () {
    $invoice = Invoice::create([
        'hospital_id' => $this->hospital->id,
        'number' => '12345',
        'issue_date' => now(),
        'period_start' => '2026-08-01',
        'period_end' => '2026-08-31',
        'amount' => 1500.00,
        'status' => 'emitida',
    ]);

    $invoice->update(['status' => 'cancelada']);

    expect($invoice->refresh()->statusLabel())->toBe('Cancelada');
});
