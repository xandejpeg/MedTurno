<?php

namespace App\Models;

use App\Enums\Role;
use App\Models\Concerns\HasTags;
use Database\Factories\UserFactory;
use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Hidden;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;

/**
 * @property Role|null $role
 */
#[Fillable(['name', 'email', 'password', 'role', 'phone', 'cpf', 'photo_path', 'crm', 'crm_uf', 'specialty', 'gender', 'calendar_token'])]
#[Hidden(['password', 'remember_token', 'calendar_token'])]
class User extends Authenticatable implements MustVerifyEmail
{
    /** @use HasFactory<UserFactory> */
    use HasFactory, HasTags, Notifiable;

    protected static function booted(): void
    {
        static::creating(function (User $user) {
            if ($user->calendar_token === null) {
                $user->calendar_token = \Illuminate\Support\Str::random(48);
            }
        });
    }

    public function calendarFeedUrl(): string
    {
        return route('calendario.feed', ['user' => $this->id, 'token' => $this->calendar_token]);
    }

    /**
     * Cadastro está completo quando os campos obrigatórios do médico estão preenchidos.
     * (foto é opcional, então não conta.)
     */
    public function cadastroCompleto(): bool
    {
        foreach ([$this->name, $this->email, $this->phone, $this->cpf, $this->crm] as $field) {
            if ($field === null || trim((string) $field) === '') {
                return false;
            }
        }

        return true;
    }

    public function isGestor(): bool
    {
        return $this->role === Role::Gestor;
    }

    public function isMedico(): bool
    {
        return $this->role === Role::Medico;
    }

    public function isAdmin(): bool
    {
        return (bool) $this->is_admin;
    }

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
            'role' => Role::class,
            'is_admin' => 'boolean',
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
     * @return HasMany<Invitation, $this>
     */
    public function createdInvitations(): HasMany
    {
        return $this->hasMany(Invitation::class, 'created_by');
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

    /**
     * @return BelongsToMany<Hospital, $this>
     */
    public function managedHospitalsHistory(): BelongsToMany
    {
        return $this->belongsToMany(Hospital::class, 'hospital_memberships')
            ->wherePivot('role', Role::Gestor->value)
            ->withPivot('active');
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
     * @return HasMany<Absence, $this>
     */
    public function absences(): HasMany
    {
        return $this->hasMany(Absence::class);
    }

    /**
     * Verifica se o usuário está ausente numa data, opcionalmente num hospital.
     */
    public function isAbsentOn(\Illuminate\Support\Carbon|string $date, ?int $hospitalId = null): bool
    {
        return $this->absences()
            ->whereDate('starts_on', '<=', $date)
            ->whereDate('ends_on', '>=', $date)
            ->get()
            ->contains(fn (Absence $a) => $a->coversDate($date, $hospitalId));
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
