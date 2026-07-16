<?php

namespace App\Enums;

enum TransferStatus: string
{
    case AguardandoReceptor = 'aguardando_receptor';
    case AguardandoGestor = 'aguardando_gestor';
    case Aprovada = 'aprovada';
    case Recusada = 'recusada';
    case Cancelada = 'cancelada';
    case Expirada = 'expirada';

    public function label(): string
    {
        return match ($this) {
            self::AguardandoReceptor => 'Aguardando colega',
            self::AguardandoGestor => 'Aguardando gestor',
            self::Aprovada => 'Aprovada',
            self::Recusada => 'Recusada',
            self::Cancelada => 'Cancelada',
            self::Expirada => 'Expirada',
        };
    }

    public function isActive(): bool
    {
        return in_array($this, [self::AguardandoReceptor, self::AguardandoGestor], true);
    }
}
