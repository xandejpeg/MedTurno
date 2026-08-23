<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

#[Fillable(['hospital_id', 'number', 'issue_date', 'period_start', 'period_end', 'amount', 'status', 'notes'])]
class Invoice extends Model
{
    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'issue_date' => 'date',
            'period_start' => 'date',
            'period_end' => 'date',
            'amount' => 'decimal:2',
        ];
    }

    /**
     * @return BelongsTo<Hospital, $this>
     */
    public function hospital(): BelongsTo
    {
        return $this->belongsTo(Hospital::class);
    }

    public function statusLabel(): string
    {
        return match ($this->status) {
            'emitida' => 'Emitida',
            'cancelada' => 'Cancelada',
            default => 'Rascunho',
        };
    }
}
