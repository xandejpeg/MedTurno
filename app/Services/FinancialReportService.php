<?php

namespace App\Services;

use App\Enums\ShiftStatus;
use App\Models\Hospital;
use App\Models\Shift;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;

class FinancialReportService
{
    /**
     * Status que contam para o financeiro.
     *
     * @var list<ShiftStatus>
     */
    private const PAYABLE = [
        ShiftStatus::Confirmado,
        ShiftStatus::Concluido,
        ShiftStatus::Pendente,
    ];

    /**
     * Extrato consolidado por profissional.
     *
     * @param  array{schedule_id?: int|null, board_id?: int|null, user_id?: int|null, tag?: string|null, include_bonus?: bool}  $filters
     * @return Collection<int, array{doctor: \App\Models\User, plantoes: int, horas: float, valor: float}>
     */
    public function consolidatedByDoctor(Hospital $hospital, Carbon $from, Carbon $to, array $filters = []): Collection
    {
        $shifts = $this->baseQuery($hospital, $from, $to, $filters)->get();

        return $shifts
            ->whereNotNull('user_id')
            ->groupBy('user_id')
            ->map(function (Collection $group) {
                $doctor = $group->first()->doctor;
                $valor = $group->sum(fn (Shift $s) => (float) $s->amount + $this->bonus($s));
                $horas = $group->sum(fn (Shift $s) => $s->starts_at->diffInMinutes($s->ends_at)) / 60;

                return [
                    'doctor' => $doctor,
                    'plantoes' => $group->count(),
                    'horas' => round($horas, 1),
                    'valor' => round($valor, 2),
                ];
            })
            ->sortByDesc('valor')
            ->values();
    }

    /**
     * Extrato consolidado por equipe (quadro).
     *
     * @return Collection<int, array{equipe: string, plantoes: int, horas: float, valor: float}>
     */
    public function consolidatedByTeam(Hospital $hospital, Carbon $from, Carbon $to, array $filters = []): Collection
    {
        $shifts = $this->baseQuery($hospital, $from, $to, $filters)->get();

        return $shifts
            ->groupBy(fn (Shift $s) => $s->schedule?->board?->name ?? 'Escala geral')
            ->map(function (Collection $group, string $equipe) {
                $valor = $group->sum(fn (Shift $s) => (float) $s->amount + $this->bonus($s));
                $horas = $group->sum(fn (Shift $s) => $s->starts_at->diffInMinutes($s->ends_at)) / 60;

                return [
                    'equipe' => $equipe,
                    'plantoes' => $group->count(),
                    'horas' => round($horas, 1),
                    'valor' => round($valor, 2),
                ];
            })
            ->sortByDesc('valor')
            ->values();
    }

    /**
     * Extrato analítico por turno (detalhe de cada plantão).
     *
     * @return Collection<int, Shift>
     */
    public function analyticByShift(Hospital $hospital, Carbon $from, Carbon $to, array $filters = []): Collection
    {
        return $this->baseQuery($hospital, $from, $to, $filters)
            ->orderBy('date')
            ->orderBy('starts_at')
            ->get();
    }

    /**
     * Totais gerais do período.
     *
     * @return array{plantoes: int, horas: float, valor: float, medicos: int}
     */
    public function totals(Hospital $hospital, Carbon $from, Carbon $to, array $filters = []): array
    {
        $shifts = $this->baseQuery($hospital, $from, $to, $filters)->get();

        return [
            'plantoes' => $shifts->count(),
            'horas' => round($shifts->sum(fn (Shift $s) => $s->starts_at->diffInMinutes($s->ends_at)) / 60, 1),
            'valor' => round($shifts->sum(fn (Shift $s) => (float) $s->amount + $this->bonus($s)), 2),
            'medicos' => $shifts->whereNotNull('user_id')->pluck('user_id')->unique()->count(),
        ];
    }

    /**
     * Query base com filtros aplicados.
     */
    private function baseQuery(Hospital $hospital, Carbon $from, Carbon $to, array $filters)
    {
        $includeBonus = $filters['include_bonus'] ?? true;

        return Shift::query()
            ->with(['doctor', 'schedule.board', 'tags'])
            ->where('hospital_id', $hospital->id)
            ->whereIn('status', self::PAYABLE)
            ->whereBetween('date', [$from->toDateString(), $to->toDateString()])
            ->when($filters['schedule_id'] ?? null, fn ($q, $id) => $q->where('schedule_id', $id))
            ->when($filters['board_id'] ?? null, fn ($q, $id) => $q->where('shift_board_id', $id))
            ->when($filters['user_id'] ?? null, fn ($q, $id) => $q->where('user_id', $id))
            ->when($filters['tag'] ?? null, fn ($q, $tag) => $q->whereHas('tags', fn ($t) => $t->where('name', $tag)));
    }

    /**
     * Bônus de um plantão (se incluído).
     */
    private function bonus(Shift $shift): float
    {
        return (float) ($shift->bonus_amount ?? 0);
    }
}
