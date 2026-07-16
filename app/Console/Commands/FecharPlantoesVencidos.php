<?php

namespace App\Console\Commands;

use App\Enums\ScheduleStatus;
use App\Enums\ShiftStatus;
use App\Models\Shift;
use Illuminate\Console\Command;

class FecharPlantoesVencidos extends Command
{
    protected $signature = 'plantoes:fechar';

    protected $description = 'Fecha plantões já encerrados: confirmados viram concluídos, pendentes viram não cumpridos';

    public function handle(): int
    {
        $base = fn () => Shift::query()
            ->where('ends_at', '<', now())
            ->whereHas('schedule', fn ($q) => $q->where('status', ScheduleStatus::Publicada));

        $concluded = $base()->where('status', ShiftStatus::Confirmado)
            ->update(['status' => ShiftStatus::Concluido]);

        $missed = $base()->where('status', ShiftStatus::Pendente)
            ->update(['status' => ShiftStatus::NaoCumprido]);

        $this->info("Concluídos: {$concluded} · Não cumpridos: {$missed}");

        return self::SUCCESS;
    }
}
