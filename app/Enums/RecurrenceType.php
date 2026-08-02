<?php

namespace App\Enums;

enum RecurrenceType: string
{
    case Semanal = 'semanal';
    case Quinzenal = 'quinzenal';
    case Mensal = 'mensal';
    case DiaDoMes = 'dia_do_mes';
    case IntervaloDias = 'intervalo_dias';
    case SemanaDoMes = 'semana_do_mes';

    public function label(): string
    {
        return match ($this) {
            self::Semanal => 'Semanal (toda semana)',
            self::Quinzenal => 'Semana sim, semana não',
            self::Mensal => 'Mensal (mesmo dia da semana)',
            self::DiaDoMes => 'Por dia do mês',
            self::IntervaloDias => 'A cada N dias',
            self::SemanaDoMes => 'Por semana do mês',
        };
    }
}
