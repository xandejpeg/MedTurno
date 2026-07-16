<?php

namespace App\Enums;

enum ScheduleStatus: string
{
    case Rascunho = 'rascunho';
    case Publicada = 'publicada';
    case Cancelada = 'cancelada';
    case Arquivada = 'arquivada';

    public function label(): string
    {
        return match ($this) {
            self::Rascunho => 'Rascunho',
            self::Publicada => 'Publicada',
            self::Cancelada => 'Cancelada',
            self::Arquivada => 'Arquivada',
        };
    }
}
