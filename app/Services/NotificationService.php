<?php

namespace App\Services;

use App\Enums\Role;
use App\Models\Hospital;
use App\Models\Notification;
use App\Models\User;

class NotificationService
{
    public function send(User $user, string $type, string $title, string $body, ?string $link = null, ?Hospital $hospital = null): Notification
    {
        return Notification::create([
            'user_id' => $user->id,
            'hospital_id' => $hospital?->id,
            'type' => $type,
            'title' => $title,
            'body' => $body,
            'link' => $link,
        ]);
    }

    /**
     * Notifica todos os gestores ativos do hospital.
     */
    public function notifyGestores(Hospital $hospital, string $type, string $title, string $body, ?string $link = null): void
    {
        $gestores = User::query()
            ->whereHas('hospitalMemberships', fn ($q) => $q
                ->where('hospital_id', $hospital->id)
                ->where('role', Role::Gestor)
                ->where('active', true))
            ->get();

        foreach ($gestores as $gestor) {
            $this->send($gestor, $type, $title, $body, $link, $hospital);
        }
    }
}
