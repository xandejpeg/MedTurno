<?php

namespace App\Jobs;

use App\Models\Schedule;
use App\Models\User;
use App\Services\WhatsAppService;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;

class SendSchedulePublishedWhatsApp implements ShouldQueue
{
    use Queueable;

    public int $tries = 3;

    /**
     * @var list<int>
     */
    public array $backoff = [60, 300, 900];

    public function __construct(
        public int $scheduleId,
        public ?int $doctorId = null,
        public bool $administrativeCopy = false,
    ) {}

    public function handle(WhatsAppService $whatsApp): void
    {
        $schedule = Schedule::with(['hospital', 'board'])->findOrFail($this->scheduleId);

        if ($this->administrativeCopy) {
            $name = config('services.notification_copy.name');
            $phone = config('services.notification_copy.phone');

            if (! is_string($name) || $name === '' || ! is_string($phone) || $phone === '') {
                return;
            }

            $whatsApp->sendSchedulePublishedTo($name, $phone, $schedule);

            return;
        }

        if ($this->doctorId === null) {
            return;
        }

        $doctor = User::findOrFail($this->doctorId);

        if ($doctor->phone === null) {
            return;
        }

        $whatsApp->sendSchedulePublished($doctor, $schedule);
    }
}