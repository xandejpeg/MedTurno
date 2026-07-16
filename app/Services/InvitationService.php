<?php

namespace App\Services;

use App\Enums\InvitationStatus;
use App\Enums\Role;
use App\Mail\ConviteMedico;
use App\Models\Hospital;
use App\Models\Invitation;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Str;

class InvitationService
{
    /**
     * Convida um médico pro hospital: cria (ou reusa) o convite e envia o email.
     * Reenvio: invalida o convite pendente anterior e gera token novo.
     */
    public function invite(Hospital $hospital, User $gestor, string $name, string $email, ?string $phone = null): Invitation
    {
        return DB::transaction(function () use ($hospital, $gestor, $name, $email, $phone) {
            Invitation::query()
                ->where('hospital_id', $hospital->id)
                ->where('email', $email)
                ->pending()
                ->update(['status' => InvitationStatus::Cancelado]);

            $token = Str::uuid()->toString();

            $invitation = Invitation::create([
                'hospital_id' => $hospital->id,
                'email' => $email,
                'name' => $name,
                'phone' => $phone,
                'token_hash' => hash('sha256', $token),
                'created_by' => $gestor->id,
                'status' => InvitationStatus::Pendente,
                'expires_at' => now()->addDays(7),
            ]);

            Mail::to($email)->queue(new ConviteMedico($invitation, $token));

            return $invitation;
        });
    }

    /**
     * Aceita um convite. Cria o user se não existir; se existir, só vincula.
     * Retorna o user vinculado.
     */
    public function accept(string $token, ?string $password = null): User
    {
        $invitation = $this->findUsableByToken($token);

        if ($invitation === null) {
            throw new \InvalidArgumentException('Convite inválido ou expirado.');
        }

        return DB::transaction(function () use ($invitation, $password) {
            $user = User::where('email', $invitation->email)->first();

            if ($user === null) {
                if ($password === null) {
                    throw new \InvalidArgumentException('Senha obrigatória para novo usuário.');
                }

                $user = User::create([
                    'name' => $invitation->name,
                    'email' => $invitation->email,
                    'phone' => $invitation->phone,
                    'password' => $password,
                ]);
                $user->forceFill(['email_verified_at' => now()])->save();
            }

            $membership = $user->hospitalMemberships()
                ->where('hospital_id', $invitation->hospital_id)
                ->where('role', Role::Medico->value)
                ->first();

            if ($membership !== null) {
                $membership->update(['active' => true]);
            } else {
                $user->hospitalMemberships()->create([
                    'hospital_id' => $invitation->hospital_id,
                    'role' => Role::Medico,
                ]);
            }

            $invitation->update([
                'status' => InvitationStatus::Aceito,
                'user_id' => $user->id,
                'accepted_at' => now(),
            ]);

            return $user;
        });
    }

    public function findUsableByToken(string $token): ?Invitation
    {
        /** @var Invitation|null $invitation */
        $invitation = Invitation::query()
            ->where('token_hash', hash('sha256', $token))
            ->first();

        if ($invitation === null || ! $invitation->isUsable()) {
            return null;
        }

        return $invitation;
    }
}
