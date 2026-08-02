<?php

namespace App\Console\Commands;

use App\Enums\ScheduleStatus;
use App\Enums\ShiftStatus;
use App\Jobs\SendShiftReminder;
use App\Models\Shift;
use Illuminate\Console\Command;

class SendShiftReminders extends Command
{
    protected $signature = 'reminders:send';

    protected $description = 'Envia lembretes de plantão programados (12h e 24h antes).';

    public function handle(): int
    {
        $hoursBeforeList = [24, 12];
        $sent = 0;

        foreach ($hoursBeforeList as $hoursBefore) {
            $from = now()->addHours($hoursBefore)->startOfHour();
            $to = $from->copy()->endOfHour();

            $shifts = Shift::with('doctor')
                ->whereNotNull('user_id')
                ->whereIn('status', [ShiftStatus::Pendente, ShiftStatus::Confirmado])
                ->whereHas('schedule', fn ($q) => $q->where('status', ScheduleStatus::Publicada->value))
                ->whereBetween('starts_at', [$from, $to])
                ->get();

            foreach ($shifts as $shift) {
                // Evita lembrete duplicado: só envia se ainda não existe notificação de lembrete para este plantão/hora.
                $alreadySent = $shift->doctor->notifications()
                    ->where('type', 'lembrete_plantao')
                    ->where('body', 'like', "%{$shift->date->format('d/m/Y')}%")
                    ->where('body', 'like', "%{$hoursBefore}h%")
                    ->exists();

                if (! $alreadySent) {
                    SendShiftReminder::dispatch($shift->id, $hoursBefore);
                    $sent++;
                }
            }
        }

        $this->info("Lembretes enfileirados: {$sent}");

        return self::SUCCESS;
    }
}
