<?php

namespace App\Http\Controllers;

use App\Enums\ScheduleStatus;
use App\Models\Shift;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;

class CalendarFeedController extends Controller
{
    /**
     * Feed iCal (ics) da escala do médico, para assinar no Google/Apple/Outlook.
     * URL: /calendario/{user}/{token}.ics
     */
    public function __invoke(Request $request, User $user, string $token)
    {
        abort_unless(hash_equals($user->calendar_token, $token), 403);

        $shifts = Shift::with('hospital')
            ->where('user_id', $user->id)
            ->whereHas('schedule', fn ($q) => $q->where('status', ScheduleStatus::Publicada->value))
            ->whereDate('date', '>=', now()->subMonths(1)->toDateString())
            ->orderBy('starts_at')
            ->get();

        $lines = [
            'BEGIN:VCALENDAR',
            'VERSION:2.0',
            'PRODID:-//DoctorTurn//Escala//PT-BR',
            'CALSCALE:GREGORIAN',
            'METHOD:PUBLISH',
            'X-WR-CALNAME:DoctorTurn — '.$user->name,
            'X-WR-TIMEZONE:America/Sao_Paulo',
        ];

        foreach ($shifts as $shift) {
            $lines = array_merge($lines, $this->event($shift));
        }

        $lines[] = 'END:VCALENDAR';

        return response(implode("\r\n", $lines), 200, [
            'Content-Type' => 'text/calendar; charset=utf-8',
            'Content-Disposition' => 'attachment; filename="escala.ics"',
        ]);
    }

    /**
     * @return list<string>
     */
    private function event(Shift $shift): array
    {
        $uid = 'shift-'.$shift->id.'@doctorturn.com.br';
        $summary = $this->escape('Plantão — '.$shift->hospital->name);
        $description = $this->escape(sprintf(
            'Plantão %s (%s–%s) no %s.',
            $shift->period === 'dia' ? 'diurno' : 'noturno',
            $shift->starts_at->format('H:i'),
            $shift->ends_at->format('H:i'),
            $shift->hospital->name,
        ));

        return [
            'BEGIN:VEVENT',
            'UID:'.$uid,
            'DTSTAMP:'.$this->stamp(now()),
            'DTSTART:'.$this->stamp($shift->starts_at),
            'DTEND:'.$this->stamp($shift->ends_at),
            'SUMMARY:'.$summary,
            'DESCRIPTION:'.$description,
            'LOCATION:'.$this->escape($shift->hospital->name),
            'STATUS:CONFIRMED',
            'END:VEVENT',
        ];
    }

    private function stamp(Carbon $date): string
    {
        return $date->copy()->utc()->format('Ymd\THis\Z');
    }

    private function escape(string $text): string
    {
        return str_replace(["\r\n", "\n", ',', ';'], ['\\n', '\\n', '\\,', '\\;'], $text);
    }
}
