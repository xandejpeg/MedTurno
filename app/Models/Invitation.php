<?php

namespace App\Models;

use App\Enums\InvitationStatus;
use App\Enums\InvitationType;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Carbon;

/**
 * @property InvitationStatus $status
 * @property InvitationType $type
 * @property Carbon|null $expires_at
 * @property Carbon|null $accepted_at
 */
#[Fillable(['hospital_id', 'type', 'shift_board_id', 'email', 'name', 'phone', 'token_hash', 'plain_token', 'created_by', 'user_id', 'status', 'expires_at', 'accepted_at'])]
class Invitation extends Model
{
    /**
     * Token cru gerado na criação do convite. Não é persistido (o banco guarda
     * só o hash) — fica disponível em memória pra montar o link logo após convidar.
     */
    public ?string $plainToken = null;

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'status' => InvitationStatus::class,
            'type' => InvitationType::class,
            'expires_at' => 'datetime',
            'accepted_at' => 'datetime',
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
    public function shiftBoard(): BelongsTo
    {
        return $this->belongsTo(ShiftBoard::class);
    }

    /**
     * Vínculos (médicos) que entraram por este convite. Pra link de grupo,
     * conta quantas pessoas usaram o link.
     *
     * @return HasMany<HospitalMembership, $this>
     */
    public function memberships(): HasMany
    {
        return $this->hasMany(HospitalMembership::class);
    }

    /**
     * @return BelongsTo<User, $this>
     */
    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    /**
     * @return BelongsTo<User, $this>
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * @param  Builder<self>  $query
     * @return Builder<self>
     */
    public function scopePending(Builder $query): Builder
    {
        return $query->where('status', InvitationStatus::Pendente);
    }

    public function isUsable(): bool
    {
        if ($this->status !== InvitationStatus::Pendente) {
            return false;
        }

        return $this->expires_at === null || $this->expires_at->isFuture();
    }

    public function isGroup(): bool
    {
        return $this->type === InvitationType::Grupo;
    }
}
