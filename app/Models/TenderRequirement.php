<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

#[Fillable(['tender_id', 'category', 'title', 'description', 'status', 'sort'])]
class TenderRequirement extends Model
{
    /**
     * @return BelongsTo<Tender, $this>
     */
    public function tender(): BelongsTo
    {
        return $this->belongsTo(Tender::class);
    }

    public function statusLabel(): string
    {
        return match ($this->status) {
            'pronto' => 'Pronto',
            'na_aplicacao' => 'Na aplicação',
            'parcial' => 'Parcial',
            default => 'Faltando',
        };
    }
}
