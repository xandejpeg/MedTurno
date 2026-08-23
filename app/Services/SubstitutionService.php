<?php

namespace App\Services;

use App\Enums\ShiftStatus;
use App\Models\Shift;
use App\Models\User;

class SubstitutionService
{
    public function __construct(
        private NotificationService $notifications,
    ) {}

    /**
     * Substitui o médico de um plantão, com registro e notificações.
     */
    public function substitute(Shift $shift, User $newDoctor, User $byManager, ?string $reason = null): Shift
    {
        $previousDoctor = $shift->doctor;

        $shift->update([
            'user_id' => $newDoctor->id,
            'status' => ShiftStatus::Pendente,
            'confirmed_at' => null,
        ]);

        $when = $shift->date->format('d/m/Y').' às '.$shift->starts_at->format('H:i');

        // Notifica o novo médico.
        $this->notifications->send(
            $newDoctor,
            'substituicao',
            'Você foi escalado para um plantão',
            "Você foi escalado para o plantão de {$when} no {$shift->hospital->name} por {$byManager->name}.",
            route('medico.plantao', $shift, false),
            $shift->hospital,
        );

        // Notifica o médico anterior (se havia).
        if ($previousDoctor !== null) {
            $this->notifications->send(
                $previousDoctor,
                'substituicao',
                'Você foi substituído em um plantão',
                "Você foi substituído no plantão de {$when} no {$shift->hospital->name} por {$newDoctor->name}.",
                route('medico.painel', absolute: false),
                $shift->hospital,
            );
        }

        return $shift;
    }
}
