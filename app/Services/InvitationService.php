<?php

namespace App\Services;

use App\Enums\InvitationStatus;
use App\Enums\InvitationType;
use App\Enums\Role;
use App\Mail\ConviteMedico;
use App\Models\Hospital;
use App\Models\Invitation;
use App\Models\ShiftBoard;
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

            $invitation->plainToken = $token;

            Mail::to($email)->queue(new ConviteMedico($invitation, $token));

            return $invitation;
        });
    }

    /**
     * Cria (ou renova) o link de grupo do hospital: um link único e reutilizável
     * que o gestor manda no WhatsApp. Qualquer pessoa que abrir cria a conta e
     * entra como médico do hospital (e, opcionalmente, já num quadro).
     * Renovar cancela o link de grupo anterior do mesmo hospital/quadro.
     */
    public function createGroupLink(Hospital $hospital, User $gestor, ?ShiftBoard $board = null): Invitation
    {
        if ($board !== null && $board->hospital_id !== $hospital->id) {
            throw new \InvalidArgumentException('Este quadro não pertence ao hospital.');
        }

        return DB::transaction(function () use ($hospital, $gestor, $board) {
            Invitation::query()
                ->where('hospital_id', $hospital->id)
                ->where('type', InvitationType::Grupo)
                ->when(
                    $board === null,
                    fn ($q) => $q->whereNull('shift_board_id'),
                    fn ($q) => $q->where('shift_board_id', $board->id),
                )
                ->pending()
                ->update(['status' => InvitationStatus::Cancelado]);

            $token = Str::uuid()->toString();

            $invitation = Invitation::create([
                'hospital_id' => $hospital->id,
                'type' => InvitationType::Grupo,
                'shift_board_id' => $board?->id,
                'token_hash' => hash('sha256', $token),
                'plain_token' => $token,
                'created_by' => $gestor->id,
                'status' => InvitationStatus::Pendente,
                'expires_at' => null,
            ]);

            $invitation->plainToken = $token;

            return $invitation;
        });
    }

    /**
     * Aceita um convite de GRUPO: a pessoa preenche o próprio cadastro.
     * Cria o user (ou vincula, se o email já existe) e entra como médico.
     * O link continua válido pra outras pessoas (reutilizável).
     *
     * @param  array<string, mixed>  $data
     */
    public function acceptGroup(string $token, array $data): User
    {
        $invitation = $this->findUsableByToken($token);

        if ($invitation === null || ! $invitation->isGroup()) {
            throw new \InvalidArgumentException('Convite inválido ou expirado.');
        }

        return DB::transaction(function () use ($invitation, $data) {
            $user = User::where('email', $data['email'])->first();

            if ($user === null) {
                $user = User::create([
                    'name' => $data['name'],
                    'email' => $data['email'],
                    'role' => Role::Medico,
                    'gender' => $data['gender'] ?? 'nao_informado',
                    'phone' => $data['phone'] ?? null,
                    'cpf' => $data['cpf'] ?? null,
                    'crm' => $data['crm'] ?? null,
                    'crm_uf' => $data['crm_uf'] ?? null,
                    'nickname' => $data['nickname'] ?? null,
                    'cbo' => $data['cbo'] ?? null,
                    'council_type' => $data['council_type'] ?? null,
                    'internal_id' => $data['internal_id'] ?? null,
                    'hired_at' => $data['hired_at'] ?? null,
                    'photo_path' => $data['photo_path'] ?? null,
                    'password' => $data['password'],
                ]);
                $user->forceFill(['email_verified_at' => now()])->save();
            }

            $this->attachMembership($user, $invitation);

            return $user;
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

        if ($invitation->isGroup()) {
            throw new \InvalidArgumentException('Este é um link de grupo — use o cadastro completo.');
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

            $this->attachMembership($user, $invitation);

            $invitation->update([
                'status' => InvitationStatus::Aceito,
                'user_id' => $user->id,
                'accepted_at' => now(),
            ]);

            return $user;
        });
    }

    /**
     * Vincula o user ao hospital do convite como médico (registrando por qual
     * convite entrou) e, se o convite aponta pra um quadro, já o coloca lá.
     */
    private function attachMembership(User $user, Invitation $invitation): void
    {
        $membership = $user->hospitalMemberships()
            ->where('hospital_id', $invitation->hospital_id)
            ->where('role', Role::Medico->value)
            ->first();

        if ($membership !== null) {
            $membership->update(['active' => true]);
        } else {
            $user->hospitalMemberships()->create([
                'hospital_id' => $invitation->hospital_id,
                'invitation_id' => $invitation->id,
                'role' => Role::Medico,
            ]);
        }

        if ($invitation->shift_board_id !== null) {
            ShiftBoard::find($invitation->shift_board_id)
                ?->doctors()
                ->syncWithoutDetaching([$user->id]);
        }
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
