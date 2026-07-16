<?php

namespace App\Services;

use App\Enums\ShiftStatus;
use App\Models\Shift;
use App\Models\User;
use Illuminate\Database\Eloquent\Collection;

class ShiftService
{
    /**
     * Plantões do médico que conflitam com o intervalo dado (independente de hospital).
     *
     * @return Collection<int, Shift>
     */
    public function conflictsFor(User $doctor, \DateTimeInterface $startsAt, \DateTimeInterface $endsAt, ?int $ignoreShiftId = null): Collection
    {
        return Shift::query()
            ->where('user_id', $doctor->id)
            ->whereNotIn('status', [ShiftStatus::Cancelado, ShiftStatus::NaoCumprido])
            ->when($ignoreShiftId !== null, fn ($q) => $q->whereKeyNot($ignoreShiftId))
            ->where('starts_at', '<', $endsAt)
            ->where('ends_at', '>', $startsAt)
            ->with('hospital')
            ->get();
    }

    /**
     * Atribui (ou troca) o médico do plantão. Congela o valor do template
     * (ou o valor padrão do hospital) na primeira atribuição.
     * Atribuição direta pelo gestor já entra confirmada — médico só precisa
     * aceitar quando recebe plantão via troca/repasse.
     */
    public function assignDoctor(Shift $shift, User $doctor): Shift
    {
        if (in_array($shift->status, [ShiftStatus::Concluido, ShiftStatus::NaoCumprido, ShiftStatus::Cancelado], true)) {
            throw new \InvalidArgumentException('Este plantão não pode mais ser alterado.');
        }

        $shift->update([
            'user_id' => $doctor->id,
            'status' => ShiftStatus::Confirmado,
            'confirmed_at' => now(),
            'amount' => $shift->amount ?? $shift->template->amount ?? $shift->hospital->default_shift_amount,
        ]);

        return $shift;
    }

    /**
     * Tira o médico do plantão (volta pra sem_medico).
     */
    public function unassignDoctor(Shift $shift): Shift
    {
        if (in_array($shift->status, [ShiftStatus::Concluido, ShiftStatus::NaoCumprido, ShiftStatus::Cancelado], true)) {
            throw new \InvalidArgumentException('Este plantão não pode mais ser alterado.');
        }

        $shift->update([
            'user_id' => null,
            'status' => ShiftStatus::SemMedico,
            'confirmed_at' => null,
        ]);

        return $shift;
    }

    /**
     * Médico confirma o plantão (pendente → confirmado). Idempotente.
     */
    public function confirm(Shift $shift, User $doctor, NotificationService $notifications): Shift
    {
        if ($shift->user_id !== $doctor->id) {
            throw new \InvalidArgumentException('Este plantão não é seu.');
        }

        if ($shift->status === ShiftStatus::Confirmado) {
            return $shift; // idempotente
        }

        if ($shift->status !== ShiftStatus::Pendente) {
            throw new \InvalidArgumentException('Este plantão não pode ser confirmado.');
        }

        $shift->update([
            'status' => ShiftStatus::Confirmado,
            'confirmed_at' => now(),
        ]);

        $shift->load('hospital');

        $notifications->notifyGestores(
            $shift->hospital,
            'plantao_confirmado',
            'Plantão confirmado',
            "{$doctor->name} confirmou o plantão de {$shift->date->format('d/m/Y')} às {$shift->starts_at->format('H:i')}.",
            route('gestor.escala', $shift->schedule_id, false),
        );

        return $shift;
    }
}
