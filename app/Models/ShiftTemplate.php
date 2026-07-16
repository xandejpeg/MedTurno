<?php

namespace App\Models;

use Database\Factories\ShiftTemplateFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * @property int $weekday
 * @property string $start_time
 * @property string $end_time
 * @property bool $crosses_midnight
 * @property int $slots
 * @property string|null $amount
 * @property string|null $label
 * @property bool $active
 */
#[Fillable(['shift_board_id', 'weekday', 'start_time', 'end_time', 'crosses_midnight', 'slots', 'amount', 'label', 'active'])]
class ShiftTemplate extends Model
{
    /** @use HasFactory<ShiftTemplateFactory> */
    use HasFactory;

    public const WEEKDAYS = [
        0 => 'Domingo',
        1 => 'Segunda',
        2 => 'Terça',
        3 => 'Quarta',
        4 => 'Quinta',
        5 => 'Sexta',
        6 => 'Sábado',
    ];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'weekday' => 'integer',
            'crosses_midnight' => 'boolean',
            'slots' => 'integer',
            'amount' => 'decimal:2',
            'active' => 'boolean',
        ];
    }

    /**
     * @return BelongsTo<ShiftBoard, $this>
     */
    public function board(): BelongsTo
    {
        return $this->belongsTo(ShiftBoard::class, 'shift_board_id');
    }

    /**
     * @return HasMany<Recurrence, $this>
     */
    public function recurrences(): HasMany
    {
        return $this->hasMany(Recurrence::class);
    }

    public function weekdayLabel(): string
    {
        return self::WEEKDAYS[$this->weekday];
    }

    /**
     * Intervalos em minutos na linha do tempo semanal [0, 10080).
     * Turnos que atravessam a meia-noite podem gerar dois intervalos (quebra no fim da semana).
     *
     * @return list<array{0: int, 1: int}>
     */
    public function weeklyIntervals(): array
    {
        return self::intervalsFor($this->weekday, $this->start_time, $this->end_time, $this->crosses_midnight);
    }

    /**
     * @return list<array{0: int, 1: int}>
     */
    public static function intervalsFor(int $weekday, string $startTime, string $endTime, bool $crossesMidnight): array
    {
        $startMin = self::toMinutes($startTime);
        $endMin = self::toMinutes($endTime);

        $start = $weekday * 1440 + $startMin;
        $duration = $endMin - $startMin + ($crossesMidnight ? 1440 : 0);
        $end = $start + $duration;

        if ($end <= 10080) {
            return [[$start, $end]];
        }

        return [[$start, 10080], [0, $end - 10080]];
    }

    private static function toMinutes(string $time): int
    {
        [$h, $m] = explode(':', $time);

        return (int) $h * 60 + (int) $m;
    }
}
