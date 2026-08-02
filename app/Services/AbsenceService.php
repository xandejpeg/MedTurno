<?php

namespace App\Services;

use App\Enums\Role;
use App\Enums\ShiftStatus;
use App\Models\Absence;
use App\Models\Shift;
use App\Models\User;
use Illuminate\Support\Collection;

class AbsenceService
{
    /**
     * Plantões publicados afetados por uma ausência.
     *
     * @return Collection<int, Shift>
     */
    public function affectedShifts(Absence $absence): Collection
    {
        return Shift::with(['schedule', 'doctor'])
            ->where('user_id', $absence->user_id)
            ->whereBetween('date', [$absence->starts_on->toDateString(), $absence->ends_on->toDateString()])
            ->whereHas('schedule', fn ($q) => $q->where('status', \App\Enums\ScheduleStatus::Publicada->value))
            ->when($absence->scope === 'hospital' && $absence->hospital_id !== null, fn ($q) => $q->where('hospital_id', $absence->hospital_id))
            ->orderBy('date')
            ->orderBy('starts_at')
            ->get();
    }

    /**
     * Sugere o substituto mais adequado para um plantão (menos horas, sem conflito, sem ausência).
     */
    public function suggestSubstitute(Shift $shift): ?User
    {
        $candidates = User::query()
            ->whereHas('hospitalMemberships', fn ($q) => $q
                ->where('hospital_id', $shift->hospital_id)
                ->where('role', Role::Medico)
                ->where('active', true))
            ->where('id', '!=', $shift->user_id)
            ->get();

        $compliance = app(ComplianceService::class);

        $eligible = $candidates->filter(function (User $candidate) use ($shift, $compliance) {
            if ($candidate->isAbsentOn($shift->date, $shift->hospital_id)) {
                return false;
            }

            return $compliance->blockingViolation($shift->hospital, $candidate, $shift) === null;
        });

        if ($eligible->isEmpty()) {
            return null;
        }

        // Menos horas no mês do plantão.
        return $eligible->sortBy(function (User $candidate) use ($shift) {
            return Shift::where('user_id', $candidate->id)
                ->where('hospital_id', $shift->hospital_id)
                ->whereYear('date', $shift->date->year)
                ->whereMonth('date', $shift->date->month)
                ->get()
                ->sum(fn (Shift $s) => $s->starts_at->diffInMinutes($s->ends_at));
        })->first();
    }

    /**
     * Substitui o médico de um plantão por ausência.
     */
    public function substitute(Shift $shift, User $substitute): Shift
    {
        $shift->update([
            'user_id' => $substitute->id,
            'status' => ShiftStatus::Pendente,
            'confirmed_at' => null,
        ]);

        return $shift;
    }

    /**
     * Anuncia o plantão como cobertura de ausência (volta a ficar sem médico e disponível).
     */
    public function announceCoverage(Shift $shift): Shift
    {
        $shift->update([
            'user_id' => null,
            'status' => ShiftStatus::Disponivel,
            'confirmed_at' => null,
        ]);

        return $shift;
    }
}
