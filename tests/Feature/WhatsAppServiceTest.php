<?php

use App\Jobs\SendSchedulePublishedWhatsApp;
use App\Models\Hospital;
use App\Models\Schedule;
use App\Models\ShiftBoard;
use App\Models\User;
use App\Services\WhatsAppService;
use Illuminate\Support\Facades\Http;

test('normaliza celular brasileiro e envia template de escala pela meta', function () {
    Http::fake([
        'graph.facebook.com/*' => Http::response(['messages' => [['id' => 'wamid.123']]]),
    ]);
    config([
        'services.whatsapp.graph_version' => 'v23.0',
        'services.whatsapp.phone_number_id' => 'phone-id',
        'services.whatsapp.token' => 'token-secreto',
        'services.whatsapp.schedule_published_template' => 'escala_publicada_v2',
        'services.whatsapp.language' => 'pt_BR',
    ]);
    $doctor = User::factory()->create(['name' => 'Dra. Maria', 'phone' => '(27) 99861-8276']);
    $hospital = Hospital::factory()->create(['name' => 'Hospital Central']);
    $schedule = Schedule::factory()->create([
        'hospital_id' => $hospital->id,
        'shift_board_id' => null,
        'year' => 2026,
        'month' => 9,
    ]);

    app(WhatsAppService::class)->sendSchedulePublished($doctor, $schedule);

    Http::assertSent(function ($request) {
        expect($request->url())->toBe('https://graph.facebook.com/v23.0/phone-id/messages')
            ->and($request['to'])->toBe('5527998618276')
            ->and($request['type'])->toBe('template')
            ->and($request['template']['name'])->toBe('escala_publicada_v2')
            ->and($request['template']['language']['code'])->toBe('pt_BR')
            ->and($request['template']['components'][0]['parameters'][0]['text'])->toBe('Maria')
            ->and($request['template']['components'][0]['parameters'][1]['text'])->toBe('geral')
            ->and($request['template']['components'][0]['parameters'][2]['text'])->toBe('Hospital Central')
            ->and($request['template']['components'][0]['parameters'][3]['text'])->toBe('09/2026');

        return true;
    });
});

test('envia separadamente o quadro e o hospital da escala', function () {
    Http::fake([
        'graph.facebook.com/*' => Http::response(['messages' => [['id' => 'wamid.123']]]),
    ]);
    config([
        'services.whatsapp.graph_version' => 'v23.0',
        'services.whatsapp.phone_number_id' => 'phone-id',
        'services.whatsapp.token' => 'token-secreto',
        'services.whatsapp.schedule_published_template' => 'escala_publicada_v2',
        'services.whatsapp.language' => 'pt_BR',
    ]);
    $doctor = User::factory()->create(['name' => 'Dra. Maria', 'phone' => '(27) 99861-8276']);
    $hospital = Hospital::factory()->create(['name' => 'Hospital Central']);
    $board = ShiftBoard::factory()->for($hospital)->create(['name' => 'UTI Noturno']);
    $schedule = Schedule::factory()->for($hospital)->for($board, 'board')->create([
        'year' => 2026,
        'month' => 9,
    ]);

    app(WhatsAppService::class)->sendSchedulePublished($doctor, $schedule);

    Http::assertSent(function ($request) {
        expect($request['template']['components'][0]['parameters'][0]['text'])->toBe('Maria')
            ->and($request['template']['components'][0]['parameters'][1]['text'])->toBe('UTI Noturno')
            ->and($request['template']['components'][0]['parameters'][2]['text'])->toBe('Hospital Central')
            ->and($request['template']['components'][0]['parameters'][3]['text'])->toBe('09/2026');

        return true;
    });
});

test('envia cópia do template para contato externo', function () {
    Http::fake([
        'graph.facebook.com/*' => Http::response(['messages' => [['id' => 'wamid.123']]]),
    ]);
    config([
        'services.whatsapp.graph_version' => 'v23.0',
        'services.whatsapp.phone_number_id' => 'phone-id',
        'services.whatsapp.token' => 'token-secreto',
        'services.whatsapp.schedule_published_template' => 'escala_publicada_v2',
        'services.whatsapp.language' => 'pt_BR',
    ]);
    $hospital = Hospital::factory()->create(['name' => 'Hospital Central']);
    $schedule = Schedule::factory()->for($hospital)->create([
        'shift_board_id' => null,
        'year' => 2026,
        'month' => 9,
    ]);

    app(WhatsAppService::class)->sendSchedulePublishedTo(
        'Alessandro',
        '(27) 99762-3271',
        $schedule,
    );

    Http::assertSent(function ($request) {
        expect($request['to'])->toBe('5527997623271')
            ->and($request['template']['components'][0]['parameters'][0]['text'])->toBe('Alessandro');

        return true;
    });
});

test('job controlado envia somente ao contato externo informado', function () {
    Http::fake([
        'graph.facebook.com/*' => Http::response(['messages' => [['id' => 'wamid.123']]]),
    ]);
    config([
        'services.whatsapp.graph_version' => 'v23.0',
        'services.whatsapp.phone_number_id' => 'phone-id',
        'services.whatsapp.token' => 'token-secreto',
        'services.whatsapp.schedule_published_template' => 'escala_publicada_v2',
        'services.whatsapp.language' => 'pt_BR',
    ]);
    $hospital = Hospital::factory()->create(['name' => 'Hospital Central']);
    $schedule = Schedule::factory()->for($hospital)->create([
        'shift_board_id' => null,
        'year' => 2026,
        'month' => 9,
    ]);
    $job = new SendSchedulePublishedWhatsApp(
        $schedule->id,
        recipientName: 'Contato Autorizado',
        recipientPhone: '(27) 99999-4444',
    );

    $job->handle(app(WhatsAppService::class));

    Http::assertSent(function ($request) {
        expect($request['to'])->toBe('5527999994444')
            ->and($request['template']['components'][0]['parameters'][0]['text'])->toBe('Contato Autorizado');

        return true;
    });
});

test('rejeita celular que não pode ser normalizado para o whatsapp', function () {
    $doctor = User::factory()->create(['phone' => '12345']);
    $schedule = Schedule::factory()->create();

    app(WhatsAppService::class)->sendSchedulePublished($doctor, $schedule);
})->throws(InvalidArgumentException::class, 'celular válido para WhatsApp');

test('normaliza celular brasileiro legado com zero inicial', function () {
    expect(app(WhatsAppService::class)->normalizeBrazilianPhone('027 99861-8276'))
        ->toBe('5527998618276');
});

test('preserva códigos internacionais explícitos para o whatsapp', function () {
    $service = app(WhatsAppService::class);

    expect($service->normalizeBrazilianPhone('+31 6 87171924'))->toBe('31687171924')
        ->and($service->normalizeBrazilianPhone('+34 665 77 27 96'))->toBe('34665772796')
        ->and($service->normalizeBrazilianPhone('27 99820-5322'))->toBe('5527998205322');
});