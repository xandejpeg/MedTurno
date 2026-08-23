<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Carbon;

#[Fillable(['user_id', 'hospital_id', 'hours', 'period', 'starts_on', 'ends_on', 'on_swap', 'on_announce'])]
class HourLimit extends Model
{
    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'starts_on' => 'date',
            'ends_on' => 'date',
        ];
    }

    /**
     * @return BelongsTo<User, $this>
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * @return BelongsTo<Hospital, $this>
     */
    public function hospital(): BelongsTo
    {
        return $this->belongsTo(Hospital::class);
    }

    /**
     * Limite vigente de um médico num hospital, numa data.
     */
    public static function forDoctorOn(int $userId, int $hospitalId, Carbon|string $date): ?self
    {
        $day = $date instanceof Carbon ? $date->toDateString() : $date;

        return static::where('user_id', $userId)
            ->where('hospital_id', $hospitalId)
            ->whereDate('starts_on', '<=', $day)
            ->where(function ($q) use ($day) {
                $q->whereNull('ends_on')->orWhereDate('ends_on', '>=', $day);
            })
            ->orderByDesc('starts_on')
            ->first();
    }

    /**
     * Horas consumidas pelo médico no período vigente (semana ou mês da data).
     */
    public function consumedHours(Carbon|string $date): float
    {
        $day = $date instanceof Carbon ? $date->copy() : Carbon::parse($date);

        [$from, $to] = $this->period === 'weekly'
            ? [$day->copy()->startOfWeek(), $day->copy()->endOfWeek()]
            : [$day->copy()->startOfMonth(), $day->copy()->endOfMonth()];

        $minutes = Shift::where('user_id', $this->user_id)
            ->where('hospital_id', $this->hospital_id)
            ->whereBetween('date', [$from->toDateString(), $to->toDateString()])
            ->get()
            ->sum(fn (Shift $s) => $s->starts_at->diffInMinutes($s->ends_at));

        return round($minutes / 60, 1);
    }

    public function periodLabel(): string
    {
        return $this->period === 'weekly' ? 'semanal' : 'mensal';
    }
}
