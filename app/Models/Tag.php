<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\MorphToMany;

#[Fillable(['hospital_id', 'name', 'color'])]
class Tag extends Model
{
    /**
     * @return BelongsTo<Hospital, $this>
     */
    public function hospital(): BelongsTo
    {
        return $this->belongsTo(Hospital::class);
    }

    /**
     * @return MorphToMany<User, $this>
     */
    public function users(): MorphToMany
    {
        return $this->morphedByMany(User::class, 'taggable');
    }

    /**
     * @return MorphToMany<Shift, $this>
     */
    public function shifts(): MorphToMany
    {
        return $this->morphedByMany(Shift::class, 'taggable');
    }

    /**
     * @return MorphToMany<Schedule, $this>
     */
    public function schedules(): MorphToMany
    {
        return $this->morphedByMany(Schedule::class, 'taggable');
    }

    /**
     * @return MorphToMany<ShiftBoard, $this>
     */
    public function boards(): MorphToMany
    {
        return $this->morphedByMany(ShiftBoard::class, 'taggable');
    }
}
