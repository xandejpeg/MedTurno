<?php

namespace App\Services;

use App\Enums\ScheduleStatus;
use App\Enums\ShiftOrigin;
use App\Enums\ShiftStatus;
use App\Jobs\SendSchedulePublishedWhatsApp;
use App\Mail\EscalaPublicada;
use App\Models\Hospital;
use App\Models\Recurrence;
use App\Models\Schedule;
use App\Models\Shift;
use App\Models\ShiftBoard;
use App\Models\User;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;
use Throwable;

class ScheduleService
{
    /**
     * Cria a escala do mês para um hospital: um calendário com 2 plantões por dia
     * (dia 07h–19h e noite 19h–07h), todos sem médico. O gestor preenche arrastando.
     */
    public function createMonthly(Hospital $hospital, int $year, int $month, User $creator): Schedule
    {
        if ($hospital->schedules()->where('year', $year)->where('month', $month)->exists()) {
            throw new \InvalidArgumentException('Já existe uma escala deste hospital para este mês.');
        }

        return DB::transaction(function () use ($hospital, $year, $month, $creator) {
            $schedule = Schedule::create([
                'hospital_id' => $hospital->id,
                'year' => $year,
                'month' => $month,
                'status' => ScheduleStatus::Rascunho,
                'version' => 1,
                'created_by' => $creator->id,
            ]);

            $date = Carbon::create($year, $month, 1);
            $lastDay = $date->copy()->endOfMonth();

            while ($date->lte($lastDay)) {
                foreach (['dia' => [7, 19, 0], 'noite' => [19, 7, 1]] as $period => [$startHour, $endHour, $endsNextDay]) {
                    $schedule->shifts()->create([
                        'hospital_id' => $hospital->id,
                        'date' => $date->toDateString(),
                        'starts_at' => $date->copy()->setTime($startHour, 0),
                        'ends_at' => $date->copy()->addDays($endsNextDay)->setTime($endHour, 0),
                        'period' => $period,
                        'user_id' => null,
                        'status' => ShiftStatus::SemMedico,
                        'amount' => $hospital->default_shift_amount,
                        'origin' => ShiftOrigin::Manual,
                    ]);
                }

                $date->addDay();
            }

            return $schedule;
        });
    }

    /**
     * Cria a escala rascunho do mês, populando shifts a partir dos templates
     * ativos e pré-atribuindo médicos com recorrência ativa.
     */
    public function createDraft(ShiftBoard $board, int $year, int $month, User $creator): Schedule
    {
        if ($board->schedules()->where('year', $year)->where('month', $month)->exists()) {
            throw new \InvalidArgumentException('Já existe uma escala deste quadro para este mês.');
        }

        return DB::transaction(function () use ($board, $year, $month, $creator) {
            $schedule = Schedule::create([
                'hospital_id' => $board->hospital_id,
                'shift_board_id' => $board->id,
                'year' => $year,
                'month' => $month,
                'status' => ScheduleStatus::Rascunho,
                'created_by' => $creator->id,
            ]);

            $templates = $board->templates()
                ->where('active', true)
                ->with(['recurrences' => fn ($q) => $q->where('active', true)])
                ->get()
                ->groupBy('weekday');

            $date = Carbon::create($year, $month, 1);
            $lastDay = $date->copy()->endOfMonth();

            while ($date->lte($lastDay)) {
                foreach ($templates->get($date->dayOfWeek, collect()) as $template) {
                    $startsAt = $date->copy()->setTimeFromTimeString($template->start_time);
                    $endsAt = $date->copy()
                        ->addDays($template->crosses_midnight ? 1 : 0)
                        ->setTimeFromTimeString($template->end_time);

                    /** @var list<Recurrence> $applicable */
                    $applicable = $template->recurrences
                        ->filter(fn (Recurrence $r) => $r->appliesOn($date))
                        ->take($template->slots)
                        ->values()
                        ->all();

                    for ($slot = 0; $slot < $template->slots; $slot++) {
                        $recurrence = $applicable[$slot] ?? null;

                        $schedule->shifts()->create([
                            'shift_template_id' => $template->id,
                            'hospital_id' => $board->hospital_id,
                            'shift_board_id' => $board->id,
                            'date' => $date->toDateString(),
                            'starts_at' => $startsAt,
                            'ends_at' => $endsAt,
                            'user_id' => $recurrence?->user_id,
                            'status' => $recurrence !== null ? ShiftStatus::Confirmado : ShiftStatus::SemMedico,
                            'confirmed_at' => $recurrence !== null ? now() : null,
                            'amount' => $recurrence !== null ? ($template->amount ?? $board->hospital->default_shift_amount) : null,
                            'origin' => $recurrence !== null ? ShiftOrigin::Recorrencia : ShiftOrigin::Manual,
                            'recurrence_id' => $recurrence?->id,
                        ]);
                    }
                }

                $date->addDay();
            }

            return $schedule;
        });
    }

    /**
     * Cria um rascunho em outro mês e copia a distribuição pela mesma
     * ocorrência do dia da semana (ex.: segunda segunda-feira, plantão dia).
     */
    public function replicateToMonth(Schedule $source, int $year, int $month, User $creator): Schedule
    {
        if ($source->year === $year && $source->month === $month) {
            throw new \InvalidArgumentException('Escolha um mês diferente da escala atual.');
        }

        if ($source->hospital->schedules()->where('year', $year)->where('month', $month)->exists()) {
            throw new \InvalidArgumentException('Já existe uma escala deste hospital para este mês.');
        }

        return DB::transaction(function () use ($source, $year, $month, $creator) {
            $source->loadMissing(['hospital', 'board']);

            $target = $source->board !== null
                ? $this->createDraft($source->board, $year, $month, $creator)
                : $this->createMonthly($source->hospital, $year, $month, $creator);

            $sourceSlots = $this->replicationSlots($source);

            foreach ($this->replicationSlots($target) as $key => $targetShift) {
                $sourceShift = $sourceSlots[$key] ?? null;

                if ($sourceShift === null) {
                    continue;
                }

                $assigned = $sourceShift->user_id !== null;

                $targetShift->update([
                    'user_id' => $sourceShift->user_id,
                    'status' => $assigned ? ShiftStatus::Confirmado : ShiftStatus::SemMedico,
                    'confirmed_at' => $assigned ? now() : null,
                    'amount' => $sourceShift->amount,
                    'note' => $sourceShift->note,
                    'origin' => $sourceShift->origin,
                    'recurrence_id' => $sourceShift->recurrence_id,
                ]);
            }

            return $target->refresh();
        });
    }

    /**
     * @return array<string, Shift>
     */
    private function replicationSlots(Schedule $schedule): array
    {
        $slots = [];
        $slotIndexes = [];

        $shifts = $schedule->shifts()
            ->orderBy('date')
            ->orderBy('starts_at')
            ->orderBy('id')
            ->get();

        foreach ($shifts as $shift) {
            $occurrence = intdiv($shift->date->day - 1, 7) + 1;
            $duration = $shift->starts_at->diffInMinutes($shift->ends_at);
            $identity = $shift->shift_template_id !== null
                ? 'template:'.$shift->shift_template_id
                : implode(':', ['period', $shift->period ?? '-', $shift->starts_at->format('H:i'), $duration]);
            $baseKey = implode('|', [$shift->date->dayOfWeek, $occurrence, $identity]);
            $slot = $slotIndexes[$baseKey] ?? 0;
            $slotIndexes[$baseKey] = $slot + 1;
            $slots[$baseKey.'|'.$slot] = $shift;
        }

        return $slots;
    }

    /**
     * Publica a escala (rascunho → publicada). Se já publicada, incrementa a versão.
     * Envia email pra cada médico com plantão na escala.
     */
    public function publish(Schedule $schedule): Schedule
    {
        if (! in_array($schedule->status, [ScheduleStatus::Rascunho, ScheduleStatus::Publicada], true)) {
            throw new \InvalidArgumentException('Esta escala não pode ser publicada.');
        }

        DB::transaction(function () use ($schedule) {
            $schedule->update([
                'status' => ScheduleStatus::Publicada,
                'version' => $schedule->published_at !== null ? $schedule->version + 1 : $schedule->version,
                'published_at' => now(),
            ]);
        });

        $schedule->refresh()->load(['hospital', 'board']);

        if (config('services.notification_test.enabled')) {
            $this->queueControlledPublicationNotifications($schedule);

            return $schedule;
        }

        $doctors = User::query()
            ->whereIn('id', $schedule->shifts()->whereNotNull('user_id')->distinct()->pluck('user_id'))
            ->get();

        $notifications = app(NotificationService::class);

        $escalaNome = $schedule->shift_board_id !== null
            ? $schedule->board->name
            : $schedule->hospital->name;

        foreach ($doctors as $doctor) {
            try {
                Mail::to($doctor->email)->queue(new EscalaPublicada($schedule, $doctor->name));
                \App\Models\CommunicationLog::create([
                    'user_id' => $doctor->id,
                    'schedule_id' => $schedule->id,
                    'channel' => 'email',
                    'recipient' => $doctor->email,
                    'subject' => "Sua escala de {$schedule->monthLabel()} está publicada",
                    'body' => "Olá, {$doctor->name}!\n\nA escala {$escalaNome} — {$schedule->monthLabel()} do hospital {$schedule->hospital->name} foi publicada.\n\nAcesse o DoctorTurn para ver seus plantões e confirmá-los.",
                    'status' => 'enviado',
                ]);
            } catch (Throwable $exception) {
                Log::error('Falha ao enfileirar e-mail de escala publicada.', [
                    'schedule_id' => $schedule->id,
                    'doctor_id' => $doctor->id,
                    'exception' => $exception,
                ]);
            }

            try {
                $notifications->send(
                    $doctor,
                    'escala_publicada',
                    'Escala publicada',
                    "A escala {$escalaNome} — {$schedule->monthLabel()} foi publicada.",
                    route('medico.escala', ['month' => sprintf('%d-%02d', $schedule->year, $schedule->month)], false),
                    $schedule->hospital,
                );
            } catch (Throwable $exception) {
                Log::error('Falha ao criar notificação interna de escala publicada.', [
                    'schedule_id' => $schedule->id,
                    'doctor_id' => $doctor->id,
                    'exception' => $exception,
                ]);
            }

            if (config('services.whatsapp.enabled') && $doctor->phone !== null) {
                try {
                    SendSchedulePublishedWhatsApp::dispatch($schedule->id, $doctor->id);
                    $scheduleName = $schedule->shift_board_id !== null ? $schedule->board->name : 'geral';
                    \App\Models\CommunicationLog::create([
                        'user_id' => $doctor->id,
                        'schedule_id' => $schedule->id,
                        'channel' => 'whatsapp',
                        'recipient' => $doctor->phone,
                        'template' => config('services.whatsapp.schedule_published_template'),
                        'body' => "Olá, {$doctor->name}! A escala {$scheduleName} de {$schedule->hospital->name}, referente a {$schedule->monthLabel()}, foi publicada no *DoctorTurn*.\n\nAcesse a plataforma para consultar seus plantões e confirmar sua escala:\nhttps://doctorturn.com.br/medico",
                        'status' => 'enviado',
                    ]);
                } catch (Throwable $exception) {
                    Log::error('Falha ao enfileirar WhatsApp de escala publicada.', [
                        'schedule_id' => $schedule->id,
                        'doctor_id' => $doctor->id,
                        'exception' => $exception,
                    ]);
                }
            }
        }

        if (config('services.notification_copy.enabled')) {
            $copyName = config('services.notification_copy.name');
            $copyEmail = config('services.notification_copy.email');
            $copyPhone = config('services.notification_copy.phone');

            if (is_string($copyName) && $copyName !== '' && is_string($copyEmail) && $copyEmail !== '') {
                try {
                    Mail::to($copyEmail)->queue(new EscalaPublicada($schedule, $copyName, true));
                } catch (Throwable $exception) {
                    Log::error('Falha ao enfileirar cópia administrativa da escala publicada.', [
                        'schedule_id' => $schedule->id,
                        'exception' => $exception,
                    ]);
                }
            }

            if (config('services.whatsapp.enabled') && is_string($copyPhone) && $copyPhone !== '') {
                try {
                    SendSchedulePublishedWhatsApp::dispatch($schedule->id, administrativeCopy: true);
                } catch (Throwable $exception) {
                    Log::error('Falha ao enfileirar cópia administrativa por WhatsApp.', [
                        'schedule_id' => $schedule->id,
                        'exception' => $exception,
                    ]);
                }
            }
        }

        return $schedule;
    }

    private function queueControlledPublicationNotifications(Schedule $schedule): void
    {
        $recipientName = config('services.notification_test.recipient_name');
        $recipientName = is_string($recipientName) && $recipientName !== ''
            ? $recipientName
            : 'Teste DoctorTurn';

        $emails = config('services.notification_test.emails', []);

        if (is_array($emails)) {
            foreach (array_unique($emails) as $email) {
                if (! is_string($email) || filter_var($email, FILTER_VALIDATE_EMAIL) === false) {
                    continue;
                }

                try {
                    Mail::to($email)->queue(new EscalaPublicada($schedule, $recipientName, true));
                } catch (Throwable $exception) {
                    Log::error('Falha ao enfileirar e-mail controlado de escala publicada.', [
                        'schedule_id' => $schedule->id,
                        'exception' => $exception,
                    ]);
                }
            }
        }

        if (! config('services.whatsapp.enabled')) {
            return;
        }

        $phones = config('services.notification_test.phones', []);

        if (! is_array($phones)) {
            return;
        }

        $whatsApp = app(WhatsAppService::class);

        foreach (array_unique($phones) as $phone) {
            if (! is_string($phone) || $whatsApp->normalizeBrazilianPhone($phone) === null) {
                continue;
            }

            try {
                SendSchedulePublishedWhatsApp::dispatch(
                    $schedule->id,
                    recipientName: $recipientName,
                    recipientPhone: $phone,
                );
            } catch (Throwable $exception) {
                Log::error('Falha ao enfileirar WhatsApp controlado de escala publicada.', [
                    'schedule_id' => $schedule->id,
                    'exception' => $exception,
                ]);
            }
        }
    }
}
