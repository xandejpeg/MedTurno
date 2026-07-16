<?php

namespace App\Enums;

enum ShiftStatus: string
{
    case SemMedico = 'sem_medico';
    case Pendente = 'pendente';
    case Confirmado = 'confirmado';
    case EmTroca = 'em_troca';
    case Disponivel = 'disponivel';
    case Concluido = 'concluido';
    case NaoCumprido = 'nao_cumprido';
    case Cancelado = 'cancelado';

    public function label(): string
    {
        return match ($this) {
            self::SemMedico => 'Sem médico',
            self::Pendente => 'Pendente',
            self::Confirmado => 'Confirmado',
            self::EmTroca => 'Em troca',
            self::Disponivel => 'Disponível',
            self::Concluido => 'Concluído',
            self::NaoCumprido => 'Não cumprido',
            self::Cancelado => 'Cancelado',
        };
    }
}
