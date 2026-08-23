<?php

use App\Enums\Role;
use App\Enums\ScheduleStatus;
use App\Enums\ShiftStatus;
use App\Mail\EscalaPublicada;
use App\Jobs\SendSchedulePublishedWhatsApp;
use App\Models\Hospital;
use App\Models\User;
use App\Services\ScheduleService;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Queue;
use Livewire\Volt\Volt;

function pipelineSetup(): array
{
    $gestor = User::factory()->gestor()->create();
    $hospital = Hospital::factory()->create(['name' => 'Hospital Pipeline']);
    $gestor->hospitalMemberships()->create([
        'hospital_id' => $hospital->id,
        'role' => Role::Gestor,
    ]);

    $medico = User::factory()->medico()->create(['name' => 'Dr. Teste']);
    $medico->hospitalMemberships()->create([
        'hospital_id' => $hospital->id,
        'role' => Role::Medico,
    ]);

    return [$gestor, $hospital, $medico];
}

test('cadastro cria gestor com papel e autentica', function () {
    Volt::test('pages.auth.register')
        ->set('role', 'gestor')
        ->set('name', 'Novo Gestor')
        ->set('email', 'novo@gestor.com')
        ->set('phoneCountry', 'BR')
        ->set('phoneNumber', '11 96123-4567')
        ->set('password', 'senha-forte-123')
        ->set('password_confirmation', 'senha-forte-123')
        ->call('register')
        ->assertHasNoErrors();

    $user = User::where('email', 'novo@gestor.com')->first();

    expect($user)->not->toBeNull()
        ->and($user->isGestor())->toBeTrue()
        ->and($user->phone)->toBe('+5511961234567')
        ->and($user->email_verified_at)->not->toBeNull();

    $this->assertAuthenticatedAs($user);
});

test('cadastro exige celular com ddd', function () {
    Volt::test('pages.auth.register')
        ->set('role', 'medico')
        ->set('name', 'Novo Médico')
        ->set('email', 'novo@medico.com')
        ->set('password', 'senha-forte-123')
        ->set('password_confirmation', 'senha-forte-123')
        ->call('register')
        ->assertHasErrors(['phoneNumber' => 'required'])
        ->set('phoneNumber', '12345')
        ->call('register')
        ->assertHasErrors(['phoneNumber']);

    expect(User::where('email', 'novo@medico.com')->exists())->toBeFalse();
});

test('cadastro aceita celular internacional com código do país', function () {
    Volt::test('pages.auth.register')
        ->set('role', 'medico')
        ->set('name', 'Médico Internacional')
        ->set('email', 'internacional@medico.com')
        ->set('phoneCountry', 'NL')
        ->set('phoneNumber', '6 87171924')
        ->set('password', 'senha-forte-123')
        ->set('password_confirmation', 'senha-forte-123')
        ->call('register')
        ->assertHasNoErrors();

    expect(User::where('email', 'internacional@medico.com')->value('phone'))
        ->toBe('+31687171924');
});

test('cadastro exige a escolha entre gestor e médico', function () {
    Volt::test('pages.auth.register')
        ->set('name', 'Usuário Sem Papel')
        ->set('email', 'sem-papel@teste.com')
        ->set('phoneCountry', 'BR')
        ->set('phoneNumber', '11 99999-9999')
        ->set('password', 'senha-forte-123')
        ->set('password_confirmation', 'senha-forte-123')
        ->call('register')
        ->assertHasErrors(['role' => 'required'])
        ->assertSee('Selecione Gestor ou Médico para continuar.');

    expect(User::where('email', 'sem-papel@teste.com')->exists())->toBeFalse();
});

test('createMonthly cria 2 plantões por dia com períodos dia e noite', function () {
    [$gestor, $hospital] = pipelineSetup();

    $schedule = app(ScheduleService::class)->createMonthly($hospital, 2026, 8, $gestor);

    expect($schedule->status)->toBe(ScheduleStatus::Rascunho)
        ->and($schedule->shift_board_id)->toBeNull()
        ->and($schedule->shifts()->count())->toBe(31 * 2)
        ->and($schedule->shifts()->where('period', 'dia')->count())->toBe(31)
        ->and($schedule->shifts()->where('period', 'noite')->count())->toBe(31)
        ->and($schedule->shifts()->whereNotNull('user_id')->count())->toBe(0);
});

test('createMonthly não duplica a escala do mesmo mês', function () {
    [$gestor, $hospital] = pipelineSetup();
    $service = app(ScheduleService::class);
    $service->createMonthly($hospital, 2026, 8, $gestor);

    expect(fn () => $service->createMonthly($hospital, 2026, 8, $gestor))
        ->toThrow(InvalidArgumentException::class);
});

test('lista escalas mensais sem quadro usando o nome do hospital', function () {
    [$gestor, $hospital] = pipelineSetup();
    $schedule = app(ScheduleService::class)->createMonthly($hospital, 2026, 8, $gestor);

    Volt::actingAs($gestor)
        ->test('pages.gestor.escalas')
        ->assertSee($hospital->name)
        ->assertSee('08/2026')
        ->assertSee(route('gestor.escala.montar', $schedule), false);
});

test('replica escala mensal para outro mês preservando a posição semanal', function () {
    [$gestor, $hospital, $medico] = pipelineSetup();
    $service = app(ScheduleService::class);
    $august = $service->createMonthly($hospital, 2026, 8, $gestor);
    $sourceShift = $august->shifts()
        ->whereDate('date', '2026-08-10')
        ->where('period', 'dia')
        ->firstOrFail();
    $sourceShift->update([
        'user_id' => $medico->id,
        'status' => ShiftStatus::Confirmado,
        'amount' => 1750,
        'note' => 'UTI',
    ]);

    $september = $service->replicateToMonth($august, 2026, 9, $gestor);
    $targetShift = $september->shifts()
        ->whereDate('date', '2026-09-14')
        ->where('period', 'dia')
        ->firstOrFail();

    expect($september->status)->toBe(ScheduleStatus::Rascunho)
        ->and($september->published_at)->toBeNull()
        ->and($september->shifts()->count())->toBe(30 * 2)
        ->and($targetShift->user_id)->toBe($medico->id)
        ->and($targetShift->status)->toBe(ShiftStatus::Confirmado)
        ->and((float) $targetShift->amount)->toBe(1750.0)
        ->and($targetShift->note)->toBe('UTI');
});

test('replicar escala nunca sobrescreve um mês existente', function () {
    [$gestor, $hospital] = pipelineSetup();
    $service = app(ScheduleService::class);
    $august = $service->createMonthly($hospital, 2026, 8, $gestor);
    $service->createMonthly($hospital, 2026, 9, $gestor);

    expect(fn () => $service->replicateToMonth($august, 2026, 9, $gestor))
        ->toThrow(InvalidArgumentException::class, 'Já existe uma escala');
});

test('preenchimento inteligente atribui médico nos dias e turnos selecionados', function () {
    [$gestor, $hospital, $medico] = pipelineSetup();
    $schedule = app(ScheduleService::class)->createMonthly($hospital, 2026, 8, $gestor);

    Volt::actingAs($gestor)
        ->test('pages.gestor.escala-montar', ['schedule' => $schedule])
        ->call('openSmartFill')
        ->set('smartDoctorId', (string) $medico->id)
        ->set('smartWeekdays', [1, 2])
        ->set('smartPeriods', ['dia'])
        ->call('applySmartFill')
        ->assertHasNoErrors()
        ->assertSee('9 plantões atribuídos');

    $assigned = $schedule->shifts()->where('user_id', $medico->id)->get();

    expect($assigned)->toHaveCount(9)
        ->and($assigned->every(fn ($shift) => in_array($shift->date->dayOfWeek, [1, 2], true)))->toBeTrue()
        ->and($assigned->every(fn ($shift) => $shift->period === 'dia'))->toBeTrue()
        ->and($schedule->shifts()->where('period', 'noite')->whereNotNull('user_id')->count())->toBe(0);
});

test('preenchimento inteligente exige médico dias e turnos', function () {
    [$gestor, $hospital] = pipelineSetup();
    $schedule = app(ScheduleService::class)->createMonthly($hospital, 2026, 8, $gestor);

    Volt::actingAs($gestor)
        ->test('pages.gestor.escala-montar', ['schedule' => $schedule])
        ->call('applySmartFill')
        ->assertHasErrors(['smartDoctorId', 'smartWeekdays', 'smartPeriods']);

    expect($schedule->shifts()->whereNotNull('user_id')->count())->toBe(0);
});

test('preenchimento inteligente substitui somente os plantões selecionados', function () {
    [$gestor, $hospital, $medico] = pipelineSetup();
    $outro = User::factory()->medico()->create();
    $outro->hospitalMemberships()->create(['hospital_id' => $hospital->id, 'role' => Role::Medico]);
    $schedule = app(ScheduleService::class)->createMonthly($hospital, 2026, 8, $gestor);
    $segundaDia = $schedule->shifts()->whereDate('date', '2026-08-03')->where('period', 'dia')->firstOrFail();
    $quartaNoite = $schedule->shifts()->whereDate('date', '2026-08-05')->where('period', 'noite')->firstOrFail();
    $segundaDia->update(['user_id' => $outro->id, 'status' => ShiftStatus::Confirmado]);
    $quartaNoite->update(['user_id' => $outro->id, 'status' => ShiftStatus::Confirmado]);

    Volt::actingAs($gestor)
        ->test('pages.gestor.escala-montar', ['schedule' => $schedule])
        ->set('smartDoctorId', (string) $medico->id)
        ->set('smartWeekdays', [1])
        ->set('smartPeriods', ['dia'])
        ->call('applySmartFill')
        ->assertHasNoErrors();

    expect($segundaDia->fresh()->user_id)->toBe($medico->id)
        ->and($quartaNoite->fresh()->user_id)->toBe($outro->id)
        ->and($schedule->shifts()->where('user_id', $medico->id)->count())->toBe(5);
});

test('gestor atribui e remove médico de um plantão', function () {
    [$gestor, $hospital, $medico] = pipelineSetup();
    $schedule = app(ScheduleService::class)->createMonthly($hospital, 2026, 8, $gestor);
    $shift = $schedule->shifts()->where('period', 'dia')->first();

    $component = Volt::actingAs($gestor)
        ->test('pages.gestor.escala-montar', ['schedule' => $schedule])
        ->call('assign', $shift->id, $medico->id);

    $shift->refresh();
    expect($shift->user_id)->toBe($medico->id)
        ->and($shift->status)->toBe(ShiftStatus::Confirmado);

    $component->call('unassign', $shift->id);

    $shift->refresh();
    expect($shift->user_id)->toBeNull()
        ->and($shift->status)->toBe(ShiftStatus::SemMedico);
});

test('gestor não pode atribuir médico de outro hospital', function () {
    [$gestor, $hospital] = pipelineSetup();
    $schedule = app(ScheduleService::class)->createMonthly($hospital, 2026, 8, $gestor);
    $shift = $schedule->shifts()->first();
    $estranho = User::factory()->medico()->create();

    Volt::actingAs($gestor)
        ->test('pages.gestor.escala-montar', ['schedule' => $schedule])
        ->call('assign', $shift->id, $estranho->id)
        ->assertStatus(422);

    expect($shift->fresh()->user_id)->toBeNull();
});

test('publicar notifica os médicos com plantão e o médico vê a escala', function () {
    Mail::fake();
    [$gestor, $hospital, $medico] = pipelineSetup();
    $service = app(ScheduleService::class);
    $schedule = $service->createMonthly($hospital, 2026, 8, $gestor);
    $shift = $schedule->shifts()->where('period', 'dia')->first();
    $shift->update(['user_id' => $medico->id, 'status' => ShiftStatus::Confirmado]);

    $service->publish($schedule);

    expect($schedule->fresh()->status)->toBe(ScheduleStatus::Publicada);
    Mail::assertQueued(EscalaPublicada::class, fn ($mail) => $mail->hasTo($medico->email));

    Volt::actingAs($medico)
        ->test('pages.medico.escala', ['month' => '2026-08'])
        ->assertSee('Hospital Pipeline');
});

test('publicar enfileira whatsapp somente para médico escalado com celular', function () {
    Mail::fake();
    Queue::fake();
    config(['services.whatsapp.enabled' => true]);
    [$gestor, $hospital, $medico] = pipelineSetup();
    $medico->update(['phone' => '(27) 99861-8276']);
    $semPlantao = User::factory()->medico()->create(['phone' => '(27) 99999-1111']);
    $semPlantao->hospitalMemberships()->create(['hospital_id' => $hospital->id, 'role' => Role::Medico]);
    $schedule = app(ScheduleService::class)->createMonthly($hospital, 2026, 8, $gestor);
    $schedule->shifts()->first()->update(['user_id' => $medico->id]);

    app(ScheduleService::class)->publish($schedule);

    Queue::assertPushed(SendSchedulePublishedWhatsApp::class, fn ($job) => $job->doctorId === $medico->id);
    Queue::assertNotPushed(SendSchedulePublishedWhatsApp::class, fn ($job) => $job->doctorId === $semPlantao->id);
});

test('publicar envia cópia administrativa para contatos externos configurados', function () {
    Mail::fake();
    Queue::fake();
    config([
        'services.whatsapp.enabled' => true,
        'services.notification_copy.enabled' => true,
        'services.notification_copy.name' => 'Alessandro',
        'services.notification_copy.email' => 'xande.chiareli@gmail.com',
        'services.notification_copy.phone' => '(27) 99762-3271',
    ]);
    [$gestor, $hospital] = pipelineSetup();
    $schedule = app(ScheduleService::class)->createMonthly($hospital, 2026, 8, $gestor);

    app(ScheduleService::class)->publish($schedule);

    Mail::assertQueued(
        EscalaPublicada::class,
        fn (EscalaPublicada $mail) => $mail->administrativeCopy
            && $mail->hasTo('xande.chiareli@gmail.com'),
    );
    Queue::assertPushed(
        SendSchedulePublishedWhatsApp::class,
        fn (SendSchedulePublishedWhatsApp $job) => $job->administrativeCopy
            && $job->doctorId === null,
    );
});

test('modo controlado sem destinatários não enfileira mensagens nem notificações internas', function () {
    Mail::fake();
    Queue::fake();
    config([
        'services.whatsapp.enabled' => true,
        'services.notification_copy.enabled' => true,
        'services.notification_copy.name' => 'Administrativo',
        'services.notification_copy.email' => 'administrativo@example.com',
        'services.notification_copy.phone' => '(27) 99999-0000',
        'services.notification_test.enabled' => true,
        'services.notification_test.emails' => [],
        'services.notification_test.phones' => [],
    ]);
    [$gestor, $hospital, $medico] = pipelineSetup();
    $medico->update(['phone' => '(27) 99999-1111']);
    $schedule = app(ScheduleService::class)->createMonthly($hospital, 2026, 8, $gestor);
    $schedule->shifts()->first()->update(['user_id' => $medico->id]);

    app(ScheduleService::class)->publish($schedule);

    expect($schedule->fresh()->status)->toBe(ScheduleStatus::Publicada);
    Mail::assertNothingQueued();
    Queue::assertNothingPushed();
    $this->assertDatabaseMissing('notifications', [
        'user_id' => $medico->id,
        'type' => 'escala_publicada',
    ]);
});

test('modo controlado envia somente aos contatos autorizados', function () {
    Mail::fake();
    Queue::fake();
    config([
        'services.whatsapp.enabled' => true,
        'services.notification_copy.enabled' => true,
        'services.notification_copy.name' => 'Administrativo',
        'services.notification_copy.email' => 'administrativo@example.com',
        'services.notification_copy.phone' => '(27) 99999-0000',
        'services.notification_test.enabled' => true,
        'services.notification_test.recipient_name' => 'Validação DoctorTurn',
        'services.notification_test.emails' => [
            'autorizado1@example.com',
            'autorizado2@example.com',
            'autorizado1@example.com',
            'email-invalido',
        ],
        'services.notification_test.phones' => [
            '(27) 99999-2222',
            '(27) 99999-3333',
            '(27) 99999-2222',
            '123',
        ],
    ]);
    [$gestor, $hospital, $medico] = pipelineSetup();
    $medico->update(['email' => 'medico@example.com', 'phone' => '(27) 99999-1111']);
    $schedule = app(ScheduleService::class)->createMonthly($hospital, 2026, 8, $gestor);
    $schedule->shifts()->first()->update(['user_id' => $medico->id]);

    app(ScheduleService::class)->publish($schedule);

    Mail::assertQueued(EscalaPublicada::class, 2);
    Mail::assertQueued(EscalaPublicada::class, fn (EscalaPublicada $mail) => $mail->hasTo('autorizado1@example.com'));
    Mail::assertQueued(EscalaPublicada::class, fn (EscalaPublicada $mail) => $mail->hasTo('autorizado2@example.com'));
    Mail::assertNotQueued(EscalaPublicada::class, fn (EscalaPublicada $mail) => $mail->hasTo('medico@example.com'));
    Mail::assertNotQueued(EscalaPublicada::class, fn (EscalaPublicada $mail) => $mail->hasTo('administrativo@example.com'));
    Queue::assertPushed(SendSchedulePublishedWhatsApp::class, 2);
    Queue::assertPushed(
        SendSchedulePublishedWhatsApp::class,
        fn (SendSchedulePublishedWhatsApp $job) => $job->doctorId === null
            && ! $job->administrativeCopy
            && $job->recipientName === 'Validação DoctorTurn'
            && in_array($job->recipientPhone, ['(27) 99999-2222', '(27) 99999-3333'], true),
    );
    Queue::assertNotPushed(
        SendSchedulePublishedWhatsApp::class,
        fn (SendSchedulePublishedWhatsApp $job) => $job->doctorId !== null || $job->administrativeCopy,
    );
    $this->assertDatabaseMissing('notifications', [
        'user_id' => $medico->id,
        'type' => 'escala_publicada',
    ]);
});

test('email de escala publicada incorpora ícone e capa da DoctorTurn', function () {
    [$gestor, $hospital] = pipelineSetup();
    $schedule = app(ScheduleService::class)->createMonthly($hospital, 2026, 8, $gestor);

    $html = (new EscalaPublicada($schedule, 'Dr. Teste'))->render();

    expect($html)
        ->toContain('alt="DoctorTurn"')
        ->and(substr_count($html, 'data:image/jpeg;base64,'))->toBe(2);
});
