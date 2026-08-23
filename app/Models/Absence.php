<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Carbon;

#[Fillable(['user_id', 'hospital_id', 'starts_on', 'ends_on', 'reason', 'scope'])]
class Absence extends Model
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
     * Verifica se a ausência cobre uma data, opcionalmente num hospital.
     */
    public function coversDate(Carbon|string $date, ?int $hospitalId = null): bool
    {
        $day = $date instanceof Carbon ? $date->toDateString() : $date;

        if ($day < $this->starts_on->toDateString() || $day > $this->ends_on->toDateString()) {
            return false;
        }

        if ($this->scope === 'all') {
            return true;
        }

        return $hospitalId === null || $this->hospital_id === $hospitalId;
    }

    public function scopeLabel(): string
    {
        return $this->scope === 'all' ? 'Todas as escalas' : 'Nesta escala';
    }
}
