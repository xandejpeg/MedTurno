<?php

namespace App\Models;

use Database\Factories\HospitalFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

#[Fillable(['name', 'cnpj', 'address', 'phone', 'default_shift_amount', 'active'])]
class Hospital extends Model
{
    /** @use HasFactory<HospitalFactory> */
    use HasFactory;

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'active' => 'boolean',
            'default_shift_amount' => 'decimal:2',
        ];
    }

    /**
     * @return HasMany<HospitalMembership, $this>
     */
    public function memberships(): HasMany
    {
        return $this->hasMany(HospitalMembership::class);
    }

    /**
     * @return HasMany<Invitation, $this>
     */
    public function invitations(): HasMany
    {
        return $this->hasMany(Invitation::class);
    }

    /**
     * @return HasMany<ShiftBoard, $this>
     */
    public function shiftBoards(): HasMany
    {
        return $this->hasMany(ShiftBoard::class);
    }

    /**
     * @return HasMany<Schedule, $this>
     */
    public function schedules(): HasMany
    {
        return $this->hasMany(Schedule::class);
    }
}
