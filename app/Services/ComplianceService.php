<?php

namespace App\Services;

use App\Models\Hospital;
use App\Models\Shift;
use App\Models\User;
use Illuminate\Support\Carbon;

class ComplianceService
{
    /**
     * Verifica as regras de conformidade ao atribuir um médico a um plantão.
     * Retorna uma lista de violações: [['rule' => string, 'message' => string, 'blocking' => bool], ...]
     *
     * @return list<array{rule: string, message: string, blocking: bool}>
     */
    public function check(Hospital $hospital, User $doctor, Shift $shift): array
    {
        $violations = [];

        // 1. Tempo máximo de turno
        if ($hospital->max_shift_hours !== null) {
            $shiftHours = $shift->starts_at->diffInMinutes($shift->ends_at) / 60;
            if ($shiftHours > $hospital->max_shift_hours) {
                $violations[] = [
                    'rule' => 'max_shift',
                    'message' => "Plantão de {$shiftHours}h excede o máximo de {$hospital->max_shift_hours}h.",
                    'blocking' => true,
                ];
            }
        }

        // 2. Descanso entre plantões
        $minRest = $shift->period === 'noite'
            ? ($hospital->min_rest_hours_night ?? $hospital->min_rest_hours)
            : $hospital->min_rest_hours;

        if ($minRest !== null) {
            $adjacent = Shift::where('user_id', $doctor->id)
                ->where('hospital_id', $hospital->id)
                ->where('id', '!=', $shift->id)
                ->where(function ($q) use ($shift, $minRest) {
                    $q->whereBetween('ends_at', [$shift->starts_at->copy()->subHours($minRest), $shift->starts_at])
                        ->orWhereBetween('starts_at', [$shift->ends_at, $shift->ends_at->copy()->addHours($minRest)]);
                })
                ->exists();

            if ($adjacent) {
                $violations[] = [
                    'rule' => 'rest',
                    'message' => "Descanso mínimo de {$minRest}h não respeitado (plantão muito próximo de outro).",
                    'blocking' => true,
                ];
            }
        }

        // 3. Conflito de agenda (interseção de horário)
        if ($hospital->conflict_mode !== 'off') {
            $conflict = Shift::where('user_id', $doctor->id)
                ->where('id', '!=', $shift->id)
                ->where(function ($q) use ($shift) {
                    $q->whereBetween('starts_at', [$shift->starts_at, $shift->ends_at])
                        ->orWhereBetween('ends_at', [$shift->starts_at, $shift->ends_at])
                        ->orWhere(function ($q2) use ($shift) {
                            $q2->where('starts_at', '<=', $shift->starts_at)
                                ->where('ends_at', '>=', $shift->ends_at);
                        });
                })
                ->exists();

            if ($conflict) {
                $violations[] = [
                    'rule' => 'conflict',
                    'message' => 'Conflito de agenda: já existe um plantão nesse horário.',
                    'blocking' => $hospital->conflict_mode === 'block',
                ];
            }
        }

        return $violations;
    }

    /**
     * Retorna a primeira violação bloqueante, ou null.
     *
     * @return array{rule: string, message: string, blocking: bool}|null
     */
    public function blockingViolation(Hospital $hospital, User $doctor, Shift $shift): ?array
    {
        foreach ($this->check($hospital, $doctor, $shift) as $v) {
            if ($v['blocking']) {
                return $v;
            }
        }

        return null;
    }
}
