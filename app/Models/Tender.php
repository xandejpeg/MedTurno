<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

#[Fillable(['title', 'orgao', 'numero', 'status', 'progress', 'notes'])]
class Tender extends Model
{
    /**
     * @return HasMany<TenderRequirement, $this>
     */
    public function requirements(): HasMany
    {
        return $this->hasMany(TenderRequirement::class)->orderBy('sort')->orderBy('id');
    }

    public function statusLabel(): string
    {
        return match ($this->status) {
            'analise' => 'Em análise',
            'aplicando' => 'Aplicando',
            'em_andamento' => 'Em andamento',
            'concluida' => 'Concluída',
            'descartada' => 'Descartada',
            default => ucfirst((string) $this->status),
        };
    }

    public function recalcProgress(): void
    {
        $reqs = $this->requirements()->get(['status']);
        $total = $reqs->count();

        if ($total === 0) {
            $this->update(['progress' => 0]);

            return;
        }

        $score = $reqs->sum(fn ($r) => match ($r->status) {
            'pronto' => 100,
            'na_aplicacao' => 75,
            'parcial' => 40,
            default => 0,
        });

        $this->update(['progress' => (int) round($score / $total)]);
    }
}
