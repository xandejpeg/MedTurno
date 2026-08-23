<?php

namespace App\Models;

use App\Enums\RecurrenceType;
use Carbon\CarbonInterface;
use Database\Factories\RecurrenceFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Carbon;

/**
 * @property RecurrenceType $type
 * @property Carbon $reference_date
 * @property bool $active
 */
#[Fillable(['user_id', 'shift_template_id', 'type', 'reference_date', 'active', 'day_of_month', 'interval_days', 'week_of_month'])]
class Recurrence extends Model
{
    /** @use HasFactory<RecurrenceFactory> */
    use HasFactory;

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'type' => RecurrenceType::class,
            'reference_date' => 'date',
            'active' => 'boolean',
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
     * @return BelongsTo<ShiftTemplate, $this>
     */
    public function template(): BelongsTo
    {
        return $this->belongsTo(ShiftTemplate::class, 'shift_template_id');
    }

    /**
     * A recorrência se aplica nesta data?
     */
    public function appliesOn(CarbonInterface $date): bool
    {
        if ($date->lt($this->reference_date)) {
            return false;
        }

        return match ($this->type) {
            RecurrenceType::Semanal => $this->appliesWeekly($date),
            RecurrenceType::Quinzenal => $this->appliesWeekly($date) && $this->reference_date->diffInDays($date) % 14 === 0,
            RecurrenceType::Mensal => $this->appliesMonthly($date),
            RecurrenceType::DiaDoMes => $this->appliesDayOfMonth($date),
            RecurrenceType::IntervaloDias => $this->appliesInterval($date),
            RecurrenceType::SemanaDoMes => $this->appliesWeekOfMonth($date),
        };
    }

    private function appliesWeekly(CarbonInterface $date): bool
    {
        return $date->dayOfWeek === $this->reference_date->dayOfWeek;
    }

    private function appliesMonthly(CarbonInterface $date): bool
    {
        // Mesmo dia da semana e mesma ocorrência no mês (ex.: 2ª terça).
        return $date->dayOfWeek === $this->reference_date->dayOfWeek
            && $this->weekOfMonth($date) === $this->weekOfMonth($this->reference_date);
    }

    private function appliesDayOfMonth(CarbonInterface $date): bool
    {
        return $this->day_of_month !== null && (int) $date->day === (int) $this->day_of_month;
    }

    private function appliesInterval(CarbonInterface $date): bool
    {
        if ($this->interval_days === null || $this->interval_days < 1) {
            return false;
        }

        return $this->reference_date->diffInDays($date) % $this->interval_days === 0;
    }

    private function appliesWeekOfMonth(CarbonInterface $date): bool
    {
        if ($this->week_of_month === null) {
            return false;
        }

        return $date->dayOfWeek === $this->reference_date->dayOfWeek
            && $this->weekOfMonth($date) === (int) $this->week_of_month;
    }

    private function weekOfMonth(CarbonInterface $date): int
    {
        return (int) intdiv($date->day - 1, 7) + 1;
    }
}
