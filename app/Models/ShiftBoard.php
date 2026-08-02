<?php

namespace App\Models;

use App\Models\Concerns\HasTags;
use Database\Factories\ShiftBoardFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;

#[Fillable(['hospital_id', 'name', 'description', 'color', 'active'])]
class ShiftBoard extends Model
{
    /** @use HasFactory<ShiftBoardFactory> */
    use HasFactory, HasTags;

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'active' => 'boolean',
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
     * @return HasMany<ShiftTemplate, $this>
     */
    public function templates(): HasMany
    {
        return $this->hasMany(ShiftTemplate::class);
    }

    /**
     * @return HasMany<Schedule, $this>
     */
    public function schedules(): HasMany
    {
        return $this->hasMany(Schedule::class);
    }

    /**
     * @return BelongsToMany<User, $this>
     */
    public function doctors(): BelongsToMany
    {
        return $this->belongsToMany(User::class, 'shift_board_memberships')
            ->withTimestamps();
    }
}
