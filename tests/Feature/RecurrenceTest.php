<?php

use App\Enums\RecurrenceType;
use App\Models\Recurrence;
use Illuminate\Support\Carbon;

function rec(array $attrs): Recurrence
{
    $r = new Recurrence;
    $r->forceFill(array_merge(['reference_date' => '2026-08-03'], $attrs));

    return $r;
}

test('semanal aplica em todo mesmo dia da semana', function () {
    $r = rec(['type' => RecurrenceType::Semanal]); // 03/08/2026 é segunda
    expect($r->appliesOn(Carbon::parse('2026-08-10')))->toBeTrue()
        ->and($r->appliesOn(Carbon::parse('2026-08-11')))->toBeFalse();
});

test('quinzenal aplica a cada 14 dias', function () {
    $r = rec(['type' => RecurrenceType::Quinzenal]);
    expect($r->appliesOn(Carbon::parse('2026-08-17')))->toBeTrue()
        ->and($r->appliesOn(Carbon::parse('2026-08-10')))->toBeFalse();
});

test('mensal aplica na mesma ocorrência do dia da semana', function () {
    $r = rec(['type' => RecurrenceType::Mensal]); // 03/08 é a 1ª segunda
    expect($r->appliesOn(Carbon::parse('2026-09-07')))->toBeTrue() // 1ª segunda de set
        ->and($r->appliesOn(Carbon::parse('2026-09-14')))->toBeFalse(); // 2ª segunda
});

test('dia do mês aplica no dia exato', function () {
    $r = rec(['type' => RecurrenceType::DiaDoMes, 'day_of_month' => 15]);
    expect($r->appliesOn(Carbon::parse('2026-08-15')))->toBeTrue()
        ->and($r->appliesOn(Carbon::parse('2026-08-16')))->toBeFalse();
});

test('intervalo de dias aplica a cada N dias', function () {
    $r = rec(['type' => RecurrenceType::IntervaloDias, 'interval_days' => 2]);
    expect($r->appliesOn(Carbon::parse('2026-08-05')))->toBeTrue()
        ->and($r->appliesOn(Carbon::parse('2026-08-06')))->toBeFalse();
});

test('semana do mês aplica na semana correta', function () {
    $r = rec(['type' => RecurrenceType::SemanaDoMes, 'week_of_month' => 1]); // 03/08 é 1ª segunda
    expect($r->appliesOn(Carbon::parse('2026-08-03')))->toBeTrue()
        ->and($r->appliesOn(Carbon::parse('2026-08-10')))->toBeFalse(); // 2ª segunda
});
