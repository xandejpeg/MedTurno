<?php

namespace App\Jobs;

use App\Models\Shift;
use App\Services\NotificationService;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;

class SendShiftReminder implements ShouldQueue
{
    use Queueable;

    public int $tries = 3;

    public function __construct(
        public int $shiftId,
        public int $hoursBefore,
    ) {}

    public function handle(NotificationService $notifications): void
    {
        $shift = Shift::with(['doctor', 'hospital'])->find($this->shiftId);

        if ($shift === null || $shift->doctor === null) {
            return;
        }

        $when = $shift->date->format('d/m/Y').' às '.$shift->starts_at->format('H:i');

        $notifications->send(
            $shift->doctor,
            'lembrete_plantao',
            'Lembrete de plantão',
            "Você tem plantão em {$when} no {$shift->hospital->name} (em {$this->hoursBefore}h).",
            route('medico.plantao', $shift, false),
            $shift->hospital,
        );
    }
}
