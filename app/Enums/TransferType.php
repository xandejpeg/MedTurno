<?php

namespace App\Enums;

enum TransferType: string
{
    case Direta = 'direta';
    case Mural = 'mural';

    public function label(): string
    {
        return match ($this) {
            self::Direta => 'Troca direta',
            self::Mural => 'Mural',
        };
    }
}
