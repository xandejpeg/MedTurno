<?php

namespace App\Models;

use App\Enums\ScheduleStatus;
use Database\Factories\ScheduleFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Carbon;

/**
 * @property int $year
 * @property int $month
 * @property ScheduleStatus $status
 * @property int $version
 * @property Carbon|null $published_at
 * @property ShiftBoard|null $board
 */
#[Fillable(['hospital_id', 'shift_board_id', 'year', 'month', 'status', 'version', 'published_at', 'created_by', 'swap_requires_approval'])]
class Schedule extends Model
{
    /** @use HasFactory<ScheduleFactory> */
    use HasFactory;

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'year' => 'integer',
            'month' => 'integer',
            'status' => ScheduleStatus::class,
            'version' => 'integer',
            'published_at' => 'datetime',
            'swap_requires_approval' => 'boolean',
        ];
    }

    /**
     * @return BelongsTo<Hospital, $this>
     */
    public function hospital(): BelongsTo
    {
        return $this->belongsTo(Hospital::class);
    }

    /**
     * @return BelongsTo<ShiftBoard, $this>
     */
    public function board(): BelongsTo
    {
        return $this->belongsTo(ShiftBoard::class, 'shift_board_id');
    }

    /**
     * @return HasMany<Shift, $this>
     */
    public function shifts(): HasMany
    {
        return $this->hasMany(Shift::class);
    }

    /**
     * @return BelongsTo<User, $this>
     */
    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function monthLabel(): string
    {
        return sprintf('%02d/%d', $this->month, $this->year);
    }
}
