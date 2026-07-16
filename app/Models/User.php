<?php

namespace App\Models;

use App\Enums\Role;
use Database\Factories\UserFactory;
use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Hidden;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;

#[Fillable(['name', 'email', 'password', 'phone', 'crm', 'crm_uf', 'specialty'])]
#[Hidden(['password', 'remember_token'])]
class User extends Authenticatable implements MustVerifyEmail
{
    /** @use HasFactory<UserFactory> */
    use HasFactory, Notifiable;

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
            'active' => 'boolean',
            'last_seen_at' => 'datetime',
        ];
    }

    /**
     * @return HasMany<HospitalMembership, $this>
     */
    public function hospitalMemberships(): HasMany
    {
        return $this->hasMany(HospitalMembership::class);
    }

    /**
     * Hospitais em que o usuário é gestor (vínculo ativo).
     *
     * @return BelongsToMany<Hospital, $this>
     */
    public function managedHospitals(): BelongsToMany
    {
        return $this->belongsToMany(Hospital::class, 'hospital_memberships')
            ->wherePivot('role', Role::Gestor->value)
            ->wherePivot('active', true);
    }

    public function isGestorOf(Hospital $hospital): bool
    {
        return $this->hospitalMemberships()
            ->where('hospital_id', $hospital->id)
            ->where('role', Role::Gestor->value)
            ->where('active', true)
            ->exists();
    }

    /**
     * Hospitais em que o usuário é médico (vínculo ativo).
     *
     * @return BelongsToMany<Hospital, $this>
     */
    public function doctorHospitals(): BelongsToMany
    {
        return $this->belongsToMany(Hospital::class, 'hospital_memberships')
            ->wherePivot('role', Role::Medico->value)
            ->wherePivot('active', true);
    }

    /**
     * @return HasMany<Notification, $this>
     */
    public function notifications(): HasMany
    {
        return $this->hasMany(Notification::class);
    }

    /**
     * @return HasMany<ShiftInterest, $this>
     */
    public function shiftInterests(): HasMany
    {
        return $this->hasMany(ShiftInterest::class);
    }
}
