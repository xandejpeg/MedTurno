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
#[Fillable(['user_id', 'shift_template_id', 'type', 'reference_date', 'active'])]
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
     * A recorrência se aplica nesta data? (mesmo dia da semana já garantido pelo template)
     */
    public function appliesOn(CarbonInterface $date): bool
    {
        if ($date->lt($this->reference_date)) {
            return false;
        }

        if ($this->type === RecurrenceType::Semanal) {
            return true;
        }

        return $this->reference_date->diffInDays($date) % 14 === 0;
    }
}
