<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Carbon;

#[Fillable(['hospital_id', 'weekday', 'period', 'reason'])]
class ShiftBlock extends Model
{
    /**
     * @return BelongsTo<Hospital, $this>
     */
    public function hospital(): BelongsTo
    {
        return $this->belongsTo(Hospital::class);
    }

    /**
     * Verifica se um plantão está bloqueado.
     */
    public static function isBlocked(int $hospitalId, Carbon|string $date, string $period): bool
    {
        $day = $date instanceof Carbon ? $date : Carbon::parse($date);

        return static::where('hospital_id', $hospitalId)
            ->where('weekday', $day->dayOfWeek)
            ->where(function ($q) use ($period) {
                $q->where('period', 'all')->orWhere('period', $period);
            })
            ->exists();
    }

    public function weekdayLabel(): string
    {
        return ['Domingo', 'Segunda', 'Terça', 'Quarta', 'Quinta', 'Sexta', 'Sábado'][$this->weekday] ?? '';
    }
}
