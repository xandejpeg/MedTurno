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

        // Lembretes de check-in (30min antes do início) e check-out (30min antes do fim)
        $checkinSent = 0;
        $checkoutSent = 0;

        $checkinFrom = now()->addMinutes(30)->startOfMinute();
        $checkinTo = $checkinFrom->copy()->endOfMinute();

        $checkinShifts = Shift::with('doctor')
            ->whereNotNull('user_id')
            ->whereIn('status', [ShiftStatus::Pendente, ShiftStatus::Confirmado])
            ->whereHas('schedule', fn ($q) => $q->where('status', ScheduleStatus::Publicada->value))
            ->whereBetween('starts_at', [$checkinFrom, $checkinTo])
            ->get();

        foreach ($checkinShifts as $shift) {
            $alreadySent = $shift->doctor->notifications()
                ->where('type', 'lembrete_checkin')
                ->where('body', 'like', "%{$shift->date->format('d/m/Y')}%")
                ->exists();

            if (! $alreadySent) {
                app(\App\Services\NotificationService::class)->send(
                    $shift->doctor,
                    'lembrete_checkin',
                    'Lembrete de check-in',
                    "Seu plantão de {$shift->date->format('d/m/Y')} às {$shift->starts_at->format('H:i')} no {$shift->hospital->name} começa em 30 minutos. Não esqueça de fazer o check-in!",
                    route('medico.plantao', $shift, false),
                    $shift->hospital,
                );
                $checkinSent++;
            }
        }

        $checkoutFrom = now()->addMinutes(30)->startOfMinute();
        $checkoutTo = $checkoutFrom->copy()->endOfMinute();

        $checkoutShifts = Shift::with('doctor')
            ->whereNotNull('user_id')
            ->whereIn('status', [ShiftStatus::Confirmado])
            ->whereHas('schedule', fn ($q) => $q->where('status', ScheduleStatus::Publicada->value))
            ->whereBetween('ends_at', [$checkoutFrom, $checkoutTo])
            ->get();

        foreach ($checkoutShifts as $shift) {
            $alreadySent = $shift->doctor->notifications()
                ->where('type', 'lembrete_checkout')
                ->where('body', 'like', "%{$shift->date->format('d/m/Y')}%")
                ->exists();

            if (! $alreadySent) {
                app(\App\Services\NotificationService::class)->send(
                    $shift->doctor,
                    'lembrete_checkout',
                    'Lembrete de check-out',
                    "Seu plantão de {$shift->date->format('d/m/Y')} termina em 30 minutos. Não esqueça de fazer o check-out!",
                    route('medico.plantao', $shift, false),
                    $shift->hospital,
                );
                $checkoutSent++;
            }
        }

        $this->info("Lembretes enfileirados: {$sent} | Check-in: {$checkinSent} | Check-out: {$checkoutSent}");

        return self::SUCCESS;
    }
}
