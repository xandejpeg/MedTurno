<?php

namespace App\Enums;

enum RecurrenceType: string
{
    case Semanal = 'semanal';
    case Quinzenal = 'quinzenal';

    public function label(): string
    {
        return match ($this) {
            self::Semanal => 'Semanal',
            self::Quinzenal => 'Quinzenal',
        };
    }
}
