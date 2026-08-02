<?php

namespace App\Services;

use App\Models\Checkin;
use App\Models\Shift;
use Illuminate\Support\Carbon;

class CheckinTreatmentService
{
    /**
     * Ajusta o horário de um check-in/check-out.
     */
    public function adjust(Checkin $checkin, Carbon $newTime): Checkin
    {
        $checkin->update(['checked_at' => $newTime]);

        return $checkin;
    }

    /**
     * Restaura o horário planejado do plantão como check-in/out.
     */
    public function restorePlanned(Shift $shift): void
    {
        $shift->checkins()->where('type', 'in')->update(['checked_at' => $shift->starts_at]);
        $shift->checkins()->where('type', 'out')->update(['checked_at' => $shift->ends_at]);
    }

    /**
     * Consolida (oficializa) os horários de um plantão, impedindo nova alteração.
     */
    public function consolidate(Shift $shift): void
    {
        $shift->update(['consolidated_at' => now()]);
    }

    /**
     * Verifica se um plantão está consolidado.
     */
    public function isConsolidated(Shift $shift): bool
    {
        return $shift->consolidated_at !== null;
    }
}
