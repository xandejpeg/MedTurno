<?php

use App\Enums\Role;
use App\Models\CommunicationLog;
use App\Models\Hospital;
use App\Models\Schedule;
use App\Models\Shift;
use App\Models\User;
use App\Services\NotificationPreviewService;
use Illuminate\Support\Facades\Bus;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Mail;

function cenarioPreview(): array
{
    $hospital = Hospital::factory()->create(['name' => 'Hospital do Simulador']);

    $medico = User::factory()->create([
        'name' => 'Dra Simulada Silva',
        'email' => 'simulada@example.com',
        'phone' => '(27) 99861-8276',
        'role' => Role::Medico,
    ]);
    $medico->hospitalMemberships()->create(['hospital_id' => $hospital->id, 'role' => Role::Medico]);

    $schedule = Schedule::factory()->create([
        'hospital_id' => $hospital->id,
        'year' => 2026,
        'month' => 8,
    ]);

    Shift::factory()->create([
        'schedule_id' => $schedule->id,
        'hospital_id' => $hospital->id,
        'user_id' => $medico->id,
    ]);

    return [$hospital, $schedule, $medico];
}

test('pagina do simulador exige admin', function () {
    $gestor = User::factory()->create(['role' => Role::Gestor, 'is_admin' => false]);

    $this->actingAs($gestor)->get('/admin/simulador-notificacoes')->assertForbidden();
});

test('admin abre o simulador', function () {
    $admin = User::factory()->create(['is_admin' => true]);

    $this->actingAs($admin)
        ->get('/admin/simulador-notificacoes')
        ->assertOk()
        ->assertSee('Simulador de Notificações')
        ->assertSee('Nada é enviado nesta tela');
});

test('previa monta email e whatsapp do medico com plantao', function () {
    [$hospital, $schedule, $medico] = cenarioPreview();

    $previas = app(NotificationPreviewService::class)->forSchedule($schedule);

    expect($previas)->toHaveCount(1);

    $p = $previas[0];

    expect($p['doctor']->id)->toBe($medico->id);
    expect($p['shiftsCount'])->toBe(1);

    // e-mail
    expect($p['email']['destinatario'])->toBe('simulada@example.com');
    expect($p['email']['assunto'])->toContain('está publicada');
    expect($p['email']['corpoTexto'])->toContain('Hospital do Simulador');
    expect($p['email']['enviaria'])->toBeTrue();
    expect($p['email']['html'])->toBeString();
    expect($p['email']['erro'])->toBeNull();

    // whatsapp: telefone normalizado igual ao do WhatsAppService
    expect($p['whatsapp']['telefoneNormalizado'])->toBe('5527998618276');
    expect($p['whatsapp']['corpo'])->toContain('Hospital do Simulador');
    expect($p['whatsapp']['corpo'])->toContain('*DoctorTurn*');
    expect($p['whatsapp']['parametros']['{{3}}'])->toBe('Hospital do Simulador');
});

test('previa NAO envia email nem whatsapp nem enfileira job', function () {
    Mail::fake();
    Bus::fake();
    Http::preventStrayRequests();

    [, $schedule] = cenarioPreview();

    app(NotificationPreviewService::class)->forSchedule($schedule);

    Mail::assertNothingSent();
    Mail::assertNothingQueued();
    Bus::assertNothingDispatched();

    // nenhum log de comunicação deve ser criado pela prévia
    expect(CommunicationLog::count())->toBe(0);
});

test('previa explica porque o whatsapp nao seria enviado', function () {
    $hospital = Hospital::factory()->create();
    $schedule = Schedule::factory()->create(['hospital_id' => $hospital->id]);

    $semTelefone = User::factory()->create(['phone' => null, 'role' => Role::Medico]);
    Shift::factory()->create([
        'schedule_id' => $schedule->id,
        'hospital_id' => $hospital->id,
        'user_id' => $semTelefone->id,
    ]);

    config()->set('services.whatsapp.enabled', true);

    $p = app(NotificationPreviewService::class)->forSchedule($schedule)[0];

    expect($p['whatsapp']['enviaria'])->toBeFalse();
    expect($p['whatsapp']['motivo'])->toContain('sem telefone');
});

test('previa avisa quando integracao de whatsapp esta desligada', function () {
    config()->set('services.whatsapp.enabled', false);

    [, $schedule] = cenarioPreview();

    $p = app(NotificationPreviewService::class)->forSchedule($schedule)[0];

    expect($p['whatsapp']['enviaria'])->toBeFalse();
    expect($p['whatsapp']['motivo'])->toContain('desativada');
});

test('escala sem medico atribuido nao gera destinatarios', function () {
    $hospital = Hospital::factory()->create();
    $schedule = Schedule::factory()->create(['hospital_id' => $hospital->id]);

    Shift::factory()->create([
        'schedule_id' => $schedule->id,
        'hospital_id' => $hospital->id,
        'user_id' => null,
    ]);

    expect(app(NotificationPreviewService::class)->forSchedule($schedule))->toBeEmpty();
});
