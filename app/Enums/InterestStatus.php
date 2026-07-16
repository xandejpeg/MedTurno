<?php

namespace App\Enums;

enum InterestStatus: string
{
    case Pendente = 'pendente';
    case Aprovado = 'aprovado';
    case Rejeitado = 'rejeitado';
    case Retirado = 'retirado';
    case RejeitadoAuto = 'rejeitado_auto';
    case CanceladoAuto = 'cancelado_auto';

    public function label(): string
    {
        return match ($this) {
            self::Pendente => 'Pendente',
            self::Aprovado => 'Aprovado',
            self::Rejeitado => 'Rejeitado',
            self::Retirado => 'Retirado',
            self::RejeitadoAuto => 'Outro médico escolhido',
            self::CanceladoAuto => 'Anúncio cancelado',
        };
    }
}
