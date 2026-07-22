<?php

namespace App\Services;

use App\Enums\ScheduleStatus;
use App\Enums\ShiftOrigin;
use App\Enums\ShiftStatus;
use App\Mail\EscalaPublicada;
use App\Models\Hospital;
use App\Models\Recurrence;
use App\Models\Schedule;
use App\Models\ShiftBoard;
use App\Models\User;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Mail;

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

        $doctors = User::query()
            ->whereIn('id', $schedule->shifts()->whereNotNull('user_id')->distinct()->pluck('user_id'))
            ->get();

        $notifications = app(NotificationService::class);

        $escalaNome = $schedule->shift_board_id !== null
            ? $schedule->board->name
            : $schedule->hospital->name;

        foreach ($doctors as $doctor) {
            Mail::to($doctor->email)->queue(new EscalaPublicada($schedule, $doctor->name));

            $notifications->send(
                $doctor,
                'escala_publicada',
                'Escala publicada',
                "A escala {$escalaNome} — {$schedule->monthLabel()} foi publicada.",
                null,
                $schedule->hospital,
            );
        }

        return $schedule;
    }
}
