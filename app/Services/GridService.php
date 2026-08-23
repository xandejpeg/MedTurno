<?php

namespace App\Services;

use App\Models\Schedule;
use App\Models\Shift;
use App\Models\User;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;

class GridService
{
    /**
     * Saldo de horas de um médico: horas na escala vs. total na instituição no mês.
     *
     * @return array{escala: float, instituicao: float, limite: float|null, consumo_limite: float|null}
     */
    public function doctorHourBalance(User $doctor, Schedule $schedule): array
    {
        $escalaMinutes = $schedule->shifts()
            ->where('user_id', $doctor->id)
            ->get()
            ->sum(fn (Shift $s) => $s->starts_at->diffInMinutes($s->ends_at));

        $instituicaoMinutes = Shift::where('user_id', $doctor->id)
            ->where('hospital_id', $schedule->hospital_id)
            ->whereYear('date', $schedule->year)
            ->whereMonth('date', $schedule->month)
            ->get()
            ->sum(fn (Shift $s) => $s->starts_at->diffInMinutes($s->ends_at));

        $limit = \App\Models\HourLimit::forDoctorOn($doctor->id, $schedule->hospital_id, Carbon::create($schedule->year, $schedule->month, 1));

        return [
            'escala' => round($escalaMinutes / 60, 1),
            'instituicao' => round($instituicaoMinutes / 60, 1),
            'limite' => $limit?->hours,
            'consumo_limite' => $limit !== null ? $limit->consumedHours(Carbon::create($schedule->year, $schedule->month, 1)) : null,
        ];
    }

    /**
     * Saldos de horas de todos os médicos de uma escala.
     *
     * @return array<int, array{escala: float, instituicao: float, limite: float|null, consumo_limite: float|null}>
     */
    public function balancesForSchedule(Schedule $schedule): array
    {
        $doctorIds = $schedule->hospital->memberships()
            ->where('role', \App\Enums\Role::Medico->value)
            ->where('active', true)
            ->pluck('user_id');

        $balances = [];

        foreach ($doctorIds as $id) {
            $doctor = User::find($id);
            if ($doctor !== null) {
                $balances[$id] = $this->doctorHourBalance($doctor, $schedule);
            }
        }

        return $balances;
    }

    /**
     * Grade semanal de uma escala (7 dias a partir de uma data).
     *
     * @return array{days: list<array{date: Carbon, shifts: Collection<int, Shift>}>, weekStart: Carbon, weekEnd: Carbon}
     */
    public function weeklyGrid(Schedule $schedule, Carbon $weekStart): array
    {
        $weekEnd = $weekStart->copy()->endOfWeek();

        $shifts = $schedule->shifts()
            ->with('doctor')
            ->whereBetween('date', [$weekStart->toDateString(), $weekEnd->toDateString()])
            ->orderBy('date')
            ->orderBy('starts_at')
            ->get()
            ->groupBy(fn (Shift $s) => $s->date->toDateString());

        $days = [];
        $cursor = $weekStart->copy();
        while ($cursor->lte($weekEnd)) {
            $days[] = [
                'date' => $cursor->copy(),
                'shifts' => $shifts->get($cursor->toDateString(), collect()),
            ];
            $cursor->addDay();
        }

        return [
            'days' => $days,
            'weekStart' => $weekStart,
            'weekEnd' => $weekEnd,
        ];
    }
}
