<?php

namespace App\Services;

use App\Models\ShiftBoard;
use App\Models\ShiftTemplate;

class ShiftTemplateService
{
    /**
     * Verifica se o horário proposto sobrepõe algum template ativo do quadro.
     */
    public function overlaps(
        ShiftBoard $board,
        int $weekday,
        string $startTime,
        string $endTime,
        bool $crossesMidnight,
        ?int $ignoreTemplateId = null,
    ): bool {
        $proposed = ShiftTemplate::intervalsFor($weekday, $startTime, $endTime, $crossesMidnight);

        $existing = $board->templates()
            ->where('active', true)
            ->when($ignoreTemplateId !== null, fn ($q) => $q->whereKeyNot($ignoreTemplateId))
            ->get();

        foreach ($existing as $template) {
            foreach ($template->weeklyIntervals() as [$aStart, $aEnd]) {
                foreach ($proposed as [$bStart, $bEnd]) {
                    if ($aStart < $bEnd && $bStart < $aEnd) {
                        return true;
                    }
                }
            }
        }

        return false;
    }

    /**
     * Cria uma grade automática de templates (6h, 12h ou 24h) pra todos os dias da semana.
     * Pula turnos que sobreporiam templates existentes.
     *
     * @return array{created: int, skipped: int}
     */
    public function applyGrid(ShiftBoard $board, int $durationHours, string $startTime, int $slots, ?string $amount): array
    {
        if (! in_array($durationHours, [6, 12, 24], true)) {
            throw new \InvalidArgumentException('Duração inválida: só 6, 12 ou 24 horas.');
        }

        [$startHour, $startMinute] = array_map('intval', explode(':', $startTime));
        $shiftsPerDay = intdiv(24, $durationHours);

        $created = 0;
        $skipped = 0;

        foreach (range(0, 6) as $weekday) {
            for ($i = 0; $i < $shiftsPerDay; $i++) {
                $startTotal = $startHour * 60 + $startMinute + $i * $durationHours * 60;
                $endTotal = $startTotal + $durationHours * 60;

                $dayOffset = intdiv($startTotal, 1440);
                $realWeekday = ($weekday + $dayOffset) % 7;
                $startInDay = $startTotal % 1440;
                $endInDay = $endTotal % 1440;
                $crosses = $endInDay <= $startInDay;

                $start = sprintf('%02d:%02d', intdiv($startInDay, 60), $startInDay % 60);
                $end = sprintf('%02d:%02d', intdiv($endInDay, 60), $endInDay % 60);

                if ($this->overlaps($board, $realWeekday, $start, $end, $crosses)) {
                    $skipped++;

                    continue;
                }

                $board->templates()->create([
                    'weekday' => $realWeekday,
                    'start_time' => $start,
                    'end_time' => $end,
                    'crosses_midnight' => $crosses,
                    'slots' => $slots,
                    'amount' => $amount,
                ]);
                $created++;
            }
        }

        return ['created' => $created, 'skipped' => $skipped];
    }
}
