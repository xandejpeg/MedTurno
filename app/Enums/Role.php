<?php

namespace App\Enums;

enum Role: string
{
    case Gestor = 'gestor';
    case Medico = 'medico';

    public function label(): string
    {
        return match ($this) {
            self::Gestor => 'Gestor',
            self::Medico => 'Médico',
        };
    }
}
