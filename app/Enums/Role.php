<?php

namespace App\Enums;

enum Role: string
{
    case Gestor = 'gestor';
    case Medico = 'medico';
    case Financeiro = 'financeiro';
    case GestorMunicipal = 'gestor_municipal';

    public function label(): string
    {
        return match ($this) {
            self::Gestor => 'Gestor',
            self::Medico => 'Médico',
            self::Financeiro => 'Financeiro',
            self::GestorMunicipal => 'Gestor municipal',
        };
    }
}
