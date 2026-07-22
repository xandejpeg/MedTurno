<?php

namespace App\Models;

use App\Enums\ShiftOrigin;
use App\Enums\ShiftStatus;
use Database\Factories\ShiftFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Carbon;

/**
 * @property Carbon $date
 * @property Carbon $starts_at
 * @property Carbon $ends_at
 * @property ShiftStatus $status
 * @property string|null $amount
 * @property Carbon|null $confirmed_at
 * @property ShiftOrigin $origin
 */
#[Fillable([
    'schedule_id', 'shift_template_id', 'hospital_id', 'shift_board_id',
    'date', 'starts_at', 'ends_at', 'period', 'user_id', 'status', 'amount',
    'confirmed_at', 'note', 'origin', 'recurrence_id',
])]
class Shift extends Model
{
    /** @use HasFactory<ShiftFactory> */
    use HasFactory;

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'date' => 'date',
            'starts_at' => 'datetime',
            'ends_at' => 'datetime',
            'status' => ShiftStatus::class,
            'amount' => 'decimal:2',
            'confirmed_at' => 'datetime',
            'origin' => ShiftOrigin::class,
        ];
    }

    /**
     * @return BelongsTo<Schedule, $this>
     */
    public function schedule(): BelongsTo
    {
        return $this->belongsTo(Schedule::class);
    }

    /**
     * @return BelongsTo<Hospital, $this>
     */
    public function hospital(): BelongsTo
    {
        return $this->belongsTo(Hospital::class);
    }

    /**
     * @return BelongsTo<ShiftTemplate, $this>
     */
    public function template(): BelongsTo
    {
        return $this->belongsTo(ShiftTemplate::class, 'shift_template_id');
    }

    /**
     * @return BelongsTo<User, $this>
     */
    public function doctor(): BelongsTo
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    /**
     * @return BelongsTo<Recurrence, $this>
     */
    public function recurrence(): BelongsTo
    {
        return $this->belongsTo(Recurrence::class);
    }

    /**
     * @return HasMany<ShiftTransfer, $this>
     */
    public function transfers(): HasMany
    {
        return $this->hasMany(ShiftTransfer::class);
    }

    public function activeTransfer(): ?ShiftTransfer
    {
        return $this->transfers()->active()->first();
    }

    /**
     * @return HasMany<ShiftInterest, $this>
     */
    public function interests(): HasMany
    {
        return $this->hasMany(ShiftInterest::class);
    }
}
