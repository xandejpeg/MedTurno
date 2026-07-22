<?php

namespace App\Enums;

enum InvitationType: string
{
    case Individual = 'individual';
    case Grupo = 'grupo';

    public function label(): string
    {
        return match ($this) {
            self::Individual => 'Individual',
            self::Grupo => 'Grupo',
        };
    }
}
