<?php

namespace App\Services;

use App\Enums\InterestStatus;
use App\Enums\Role;
use App\Enums\ShiftStatus;
use App\Enums\TransferStatus;
use App\Enums\TransferType;
use App\Mail\TrocaAtualizada;
use App\Models\Shift;
use App\Models\ShiftInterest;
use App\Models\ShiftTransfer;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;

class TransferService
{
    public function __construct(
        private NotificationService $notifications,
    ) {}

    /**
     * Médico dono pede pra passar o plantão pra um colega específico.
     */
    public function requestDirect(Shift $shift, User $from, User $to, ?string $reason = null): ShiftTransfer
    {
        if ($shift->user_id !== $from->id) {
            throw new \InvalidArgumentException('Este plantão não é seu.');
        }

        if ($from->id === $to->id) {
            throw new \InvalidArgumentException('Escolha um colega diferente de você.');
        }

        if (! in_array($shift->status, [ShiftStatus::Pendente, ShiftStatus::Confirmado], true)) {
            throw new \InvalidArgumentException('Este plantão não pode entrar em troca.');
        }

        if ($shift->transfers()->active()->exists()) {
            throw new \InvalidArgumentException('Já existe uma troca em andamento pra este plantão.');
        }

        $isColleague = $to->hospitalMemberships()
            ->where('hospital_id', $shift->hospital_id)
            ->where('role', Role::Medico)
            ->where('active', true)
            ->exists();

        if (! $isColleague) {
            throw new \InvalidArgumentException('O colega escolhido não atua neste hospital.');
        }

        return DB::transaction(function () use ($shift, $from, $to, $reason) {
            $transfer = ShiftTransfer::create([
                'shift_id' => $shift->id,
                'type' => TransferType::Direta,
                'from_user_id' => $from->id,
                'to_user_id' => $to->id,
                'reason' => $reason,
                'status' => TransferStatus::AguardandoReceptor,
            ]);

            $shift->update(['status' => ShiftStatus::EmTroca]);

            $when = $shift->date->format('d/m/Y').' às '.$shift->starts_at->format('H:i');

            $this->notifications->send(
                $to,
                'troca_pendente',
                'Proposta de troca',
                "{$from->name} quer te passar o plantão de {$when}.",
                route('medico.trocas', absolute: false),
                $shift->hospital,
            );

            Mail::to($to->email)->queue(new TrocaAtualizada(
                'Proposta de troca de plantão',
                $to->name,
                [
                    "{$from->name} quer te passar o plantão de {$when} no {$shift->hospital->name}.",
                    $reason !== null && $reason !== '' ? "Motivo: {$reason}" : 'Aceite ou recuse no app.',
                ],
                route('medico.trocas'),
            ));

            return $transfer;
        });
    }

    /**
     * Receptor aceita — vai pra aprovação do gestor.
     */
    public function acceptByReceiver(ShiftTransfer $transfer, User $receiver): ShiftTransfer
    {
        $this->assertReceiverCanDecide($transfer, $receiver);

        $shift = $transfer->shift->load(['hospital', 'schedule']);
        $requiresApproval = (bool) ($shift->schedule?->swap_requires_approval ?? true);
        $when = $shift->date->format('d/m/Y').' às '.$shift->starts_at->format('H:i');

        if (! $requiresApproval) {
            return DB::transaction(function () use ($transfer, $receiver, $shift, $when) {
                $transfer->update([
                    'status' => TransferStatus::Aprovada,
                    'decided_by' => $receiver->id,
                    'decided_at' => now(),
                ]);

                $shift->update([
                    'user_id' => $transfer->to_user_id,
                    'status' => ShiftStatus::Pendente,
                    'confirmed_at' => null,
                ]);

                $this->notifications->send(
                    $transfer->fromDoctor,
                    'troca_aprovada',
                    'Troca concluída',
                    "{$receiver->name} assumiu o seu plantão de {$when}.",
                    route('medico.trocas', absolute: false),
                    $shift->hospital,
                );

                return $transfer;
            });
        }

        $transfer->update(['status' => TransferStatus::AguardandoGestor]);

        $this->notifications->notifyGestores(
            $shift->hospital,
            'troca_pendente',
            'Troca aguardando aprovação',
            "{$receiver->name} aceitou receber o plantão de {$when} de {$transfer->fromDoctor->name}.",
            route('gestor.trocas', absolute: false),
        );

        $this->notifyAdminsTrocaPendente($transfer, $receiver, $when);

        $this->notifications->send(
            $transfer->fromDoctor,
            'troca_aceita',
            'Colega aceitou a troca',
            "{$receiver->name} aceitou o plantão de {$when}. Falta o gestor aprovar.",
            route('medico.trocas', absolute: false),
            $shift->hospital,
        );

        return $transfer;
    }

    /**
     * Notifica gestores do hospital e administradores da plataforma sobre uma troca
     * pendente, no app, por e-mail e por WhatsApp.
     */
    private function notifyAdminsTrocaPendente(ShiftTransfer $transfer, User $receiver, string $when): void
    {
        $shift = $transfer->shift;
        $body = "{$receiver->name} aceitou receber o plantão de {$when} de {$transfer->fromDoctor->name} ({$shift->hospital->name}).";

        $gestores = User::query()
            ->whereHas('hospitalMemberships', fn ($q) => $q
                ->where('hospital_id', $shift->hospital_id)
                ->where('role', Role::Gestor)
                ->where('active', true))
            ->get();

        $admins = User::where('is_admin', true)->get();

        $recipients = $gestores->merge($admins)->unique('id');

        foreach ($recipients as $person) {
            $this->notifications->send(
                $person,
                'troca_pendente',
                'Troca aguardando aprovação',
                $body,
                route('gestor.trocas', absolute: false),
                $shift->hospital,
            );

            try {
                Mail::to($person->email)->queue(new \App\Mail\TrocaPendente($transfer, $person->name));
                \App\Models\CommunicationLog::create([
                    'user_id' => $person->id,
                    'channel' => 'email',
                    'recipient' => $person->email,
                    'subject' => 'Troca aguardando aprovação',
                    'body' => "Olá, {$person->name}!\n\nHá uma troca de plantão aguardando aprovação no {$shift->hospital->name}.\n\nPlantão: {$when}\nDe: {$transfer->fromDoctor->name}\nPara: {$receiver->name}\n\nAcesse o DoctorTurn para revisar.",
                    'status' => 'enviado',
                ]);
            } catch (\Throwable $e) {
                Log::error('Falha ao enfileirar e-mail de troca pendente.', ['user_id' => $person->id, 'exception' => $e]);
            }

            if (config('services.whatsapp.enabled') && $person->phone !== null) {
                try {
                    $template = config('services.whatsapp.swap_pending_template');
                    \App\Jobs\SendWhatsAppTemplate::dispatch($person->phone, $template, [
                        $person->name,
                        $when,
                        $transfer->fromDoctor->name,
                        $receiver->name,
                        $shift->hospital->name,
                    ]);
                    \App\Models\CommunicationLog::create([
                        'user_id' => $person->id,
                        'channel' => 'whatsapp',
                        'recipient' => $person->phone,
                        'template' => $template,
                        'body' => "Olá, {$person->name}! Há uma troca aguardando aprovação no *DoctorTurn*.\n\nPlantão: {$when}\nDe: {$transfer->fromDoctor->name}\nPara: {$receiver->name}\nHospital: {$shift->hospital->name}",
                        'status' => 'enviado',
                    ]);
                } catch (\Throwable $e) {
                    Log::error('Falha ao enfileirar WhatsApp de troca pendente.', ['user_id' => $person->id, 'exception' => $e]);
                }
            }
        }
    }

    /**
     * Receptor recusa — plantão volta pro dono no estado anterior.
     */
    public function rejectByReceiver(ShiftTransfer $transfer, User $receiver): ShiftTransfer
    {
        $this->assertReceiverCanDecide($transfer, $receiver);

        return DB::transaction(function () use ($transfer, $receiver) {
            $transfer->update([
                'status' => TransferStatus::Recusada,
                'decided_by' => $receiver->id,
                'decided_at' => now(),
            ]);

            $shift = $this->restoreShift($transfer);
            $when = $shift->date->format('d/m/Y').' às '.$shift->starts_at->format('H:i');

            $this->notifications->send(
                $transfer->fromDoctor,
                'troca_recusada',
                'Troca recusada',
                "{$receiver->name} recusou o plantão de {$when}. O plantão continua com você.",
                route('medico.trocas', absolute: false),
                $shift->hospital,
            );

            Mail::to($transfer->fromDoctor->email)->queue(new TrocaAtualizada(
                'Troca recusada',
                $transfer->fromDoctor->name,
                ["{$receiver->name} recusou o plantão de {$when}. O plantão continua com você."],
                route('medico.trocas'),
            ));

            return $transfer;
        });
    }

    /**
     * Dono cancela antes do receptor responder.
     */
    public function cancelByOwner(ShiftTransfer $transfer, User $owner): ShiftTransfer
    {
        if ($transfer->from_user_id !== $owner->id) {
            throw new \InvalidArgumentException('Esta proposta não é sua.');
        }

        if ($transfer->status !== TransferStatus::AguardandoReceptor) {
            throw new \InvalidArgumentException('Esta proposta não pode mais ser cancelada.');
        }

        return DB::transaction(function () use ($transfer, $owner) {
            $transfer->update([
                'status' => TransferStatus::Cancelada,
                'decided_by' => $owner->id,
                'decided_at' => now(),
            ]);

            $shift = $this->restoreShift($transfer);
            $when = $shift->date->format('d/m/Y').' às '.$shift->starts_at->format('H:i');

            $this->notifications->send(
                $transfer->toDoctor,
                'troca_cancelada',
                'Proposta cancelada',
                "{$owner->name} cancelou a proposta do plantão de {$when}.",
                route('medico.trocas', absolute: false),
                $shift->hospital,
            );

            return $transfer;
        });
    }

    /**
     * Gestor aprova — plantão passa pro receptor como pendente.
     */
    public function approve(ShiftTransfer $transfer, User $gestor): ShiftTransfer
    {
        $this->assertGestorCanDecide($transfer, $gestor);

        return DB::transaction(function () use ($transfer, $gestor) {
            $updated = ShiftTransfer::query()
                ->whereKey($transfer->id)
                ->where('status', TransferStatus::AguardandoGestor)
                ->update([
                    'status' => TransferStatus::Aprovada,
                    'decided_by' => $gestor->id,
                    'decided_at' => now(),
                ]);

            if ($updated === 0) {
                throw new \InvalidArgumentException('Esta troca já foi decidida.');
            }

            $transfer->refresh();

            $shift = $transfer->shift;
            $shift->update([
                'user_id' => $transfer->to_user_id,
                'status' => ShiftStatus::Pendente,
                'confirmed_at' => null,
            ]);

            $shift->load('hospital');
            $when = $shift->date->format('d/m/Y').' às '.$shift->starts_at->format('H:i');

            $this->notifications->send(
                $transfer->fromDoctor,
                'troca_aprovada',
                'Troca aprovada',
                "A troca do plantão de {$when} foi aprovada. O plantão agora é de {$transfer->toDoctor->name}.",
                route('medico.trocas', absolute: false),
                $shift->hospital,
            );

            $this->notifications->send(
                $transfer->toDoctor,
                'troca_aprovada',
                'Plantão é seu — confirme',
                "A troca foi aprovada. O plantão de {$when} agora é seu, confirme no app.",
                route('medico.plantao', $shift->id, false),
                $shift->hospital,
            );

            Mail::to($transfer->toDoctor->email)->queue(new TrocaAtualizada(
                'Plantão transferido pra você',
                $transfer->toDoctor->name,
                ["A troca foi aprovada. O plantão de {$when} no {$shift->hospital->name} agora é seu — confirme no app."],
                route('medico.plantao', $shift->id),
            ));

            return $transfer;
        });
    }

    /**
     * Gestor recusa — plantão volta pro dono no estado anterior.
     */
    public function reject(ShiftTransfer $transfer, User $gestor): ShiftTransfer
    {
        $this->assertGestorCanDecide($transfer, $gestor);

        return DB::transaction(function () use ($transfer, $gestor) {
            $transfer->update([
                'status' => TransferStatus::Recusada,
                'decided_by' => $gestor->id,
                'decided_at' => now(),
            ]);

            $shift = $this->restoreShift($transfer);
            $when = $shift->date->format('d/m/Y').' às '.$shift->starts_at->format('H:i');

            foreach ([$transfer->fromDoctor, $transfer->toDoctor] as $doctor) {
                $this->notifications->send(
                    $doctor,
                    'troca_recusada',
                    'Troca recusada pelo gestor',
                    "O gestor recusou a troca do plantão de {$when}. O plantão continua com {$transfer->fromDoctor->name}.",
                    route('medico.trocas', absolute: false),
                    $shift->hospital,
                );
            }

            return $transfer;
        });
    }

    /**
     * Médico dono anuncia o plantão no mural do quadro.
     */
    public function announce(Shift $shift, User $owner, ?string $reason = null): ShiftTransfer
    {
        if ($shift->user_id !== $owner->id) {
            throw new \InvalidArgumentException('Este plantão não é seu.');
        }

        if (! in_array($shift->status, [ShiftStatus::Pendente, ShiftStatus::Confirmado], true)) {
            throw new \InvalidArgumentException('Este plantão não pode ser anunciado.');
        }

        if ($shift->transfers()->active()->exists()) {
            throw new \InvalidArgumentException('Já existe uma troca em andamento pra este plantão.');
        }

        return DB::transaction(function () use ($shift, $owner, $reason) {
            $transfer = ShiftTransfer::create([
                'shift_id' => $shift->id,
                'type' => TransferType::Mural,
                'from_user_id' => $owner->id,
                'reason' => $reason,
                'status' => TransferStatus::AguardandoReceptor,
            ]);

            $shift->update(['status' => ShiftStatus::Disponivel]);

            $when = $shift->date->format('d/m/Y').' às '.$shift->starts_at->format('H:i');

            $colleagues = $shift->schedule->board->doctors()->whereKeyNot($owner->id)->get();

            foreach ($colleagues as $colleague) {
                $this->notifications->send(
                    $colleague,
                    'mural_anuncio',
                    'Plantão disponível no mural',
                    "{$owner->name} anunciou o plantão de {$when} no mural.",
                    route('medico.mural', absolute: false),
                    $shift->hospital,
                );
            }

            return $transfer;
        });
    }

    /**
     * Colega do quadro manifesta interesse num plantão anunciado.
     */
    public function expressInterest(Shift $shift, User $medico): ShiftInterest
    {
        if ($shift->user_id === $medico->id) {
            throw new \InvalidArgumentException('Você não pode ter interesse no seu próprio plantão.');
        }

        if ($shift->status !== ShiftStatus::Disponivel) {
            throw new \InvalidArgumentException('Este plantão não está mais disponível.');
        }

        $isBoardMember = $shift->schedule->board->doctors()->whereKey($medico->id)->exists();

        if (! $isBoardMember) {
            throw new \InvalidArgumentException('Você não participa deste quadro.');
        }

        $interest = ShiftInterest::updateOrCreate(
            ['shift_id' => $shift->id, 'user_id' => $medico->id],
            ['status' => InterestStatus::Pendente],
        );

        $when = $shift->date->format('d/m/Y').' às '.$shift->starts_at->format('H:i');

        $this->notifications->notifyGestores(
            $shift->hospital,
            'mural_interesse',
            'Interesse em plantão do mural',
            "{$medico->name} tem interesse no plantão de {$when} anunciado por {$shift->doctor?->name}.",
            route('gestor.trocas', absolute: false),
        );

        return $interest;
    }

    /**
     * Médico retira o interesse antes da decisão do gestor.
     */
    public function withdrawInterest(Shift $shift, User $medico): void
    {
        $updated = ShiftInterest::query()
            ->where('shift_id', $shift->id)
            ->where('user_id', $medico->id)
            ->where('status', InterestStatus::Pendente)
            ->update(['status' => InterestStatus::Retirado]);

        if ($updated === 0) {
            throw new \InvalidArgumentException('Você não tem interesse pendente neste plantão.');
        }
    }

    /**
     * Dono cancela o anúncio — plantão volta ao estado anterior e interesses são encerrados.
     */
    public function cancelAnnouncement(ShiftTransfer $transfer, User $owner): ShiftTransfer
    {
        if ($transfer->from_user_id !== $owner->id) {
            throw new \InvalidArgumentException('Este anúncio não é seu.');
        }

        if ($transfer->type !== TransferType::Mural || $transfer->status !== TransferStatus::AguardandoReceptor) {
            throw new \InvalidArgumentException('Este anúncio não pode mais ser cancelado.');
        }

        return DB::transaction(function () use ($transfer, $owner) {
            $transfer->update([
                'status' => TransferStatus::Cancelada,
                'decided_by' => $owner->id,
                'decided_at' => now(),
            ]);

            $shift = $this->restoreShift($transfer);
            $when = $shift->date->format('d/m/Y').' às '.$shift->starts_at->format('H:i');

            $interested = $shift->interests()->pending()->with('doctor')->get();

            $shift->interests()->pending()->update(['status' => InterestStatus::CanceladoAuto]);

            foreach ($interested as $interest) {
                $this->notifications->send(
                    $interest->doctor,
                    'mural_cancelado',
                    'Anúncio cancelado',
                    "O anúncio do plantão de {$when} foi cancelado pelo dono.",
                    route('medico.mural', absolute: false),
                    $shift->hospital,
                );
            }

            return $transfer;
        });
    }

    /**
     * Gestor escolhe um interessado — os demais são rejeitados automaticamente.
     */
    public function approveInterest(ShiftInterest $interest, User $gestor): ShiftInterest
    {
        $shift = $interest->shift;

        if (! $gestor->isGestorOf($shift->hospital)) {
            throw new \InvalidArgumentException('Você não é gestor deste hospital.');
        }

        $transfer = $shift->transfers()
            ->where('type', TransferType::Mural)
            ->where('status', TransferStatus::AguardandoReceptor)
            ->first();

        if ($transfer === null) {
            throw new \InvalidArgumentException('Este anúncio não está mais ativo.');
        }

        return DB::transaction(function () use ($interest, $shift, $transfer, $gestor) {
            $updated = ShiftInterest::query()
                ->whereKey($interest->id)
                ->where('status', InterestStatus::Pendente)
                ->update(['status' => InterestStatus::Aprovado]);

            if ($updated === 0) {
                throw new \InvalidArgumentException('Este interesse já foi decidido.');
            }

            $interest->refresh();

            $transfer->update([
                'status' => TransferStatus::Aprovada,
                'to_user_id' => $interest->user_id,
                'decided_by' => $gestor->id,
                'decided_at' => now(),
            ]);

            $losers = $shift->interests()->pending()->whereKeyNot($interest->id)->with('doctor')->get();

            $shift->interests()->pending()->whereKeyNot($interest->id)->update(['status' => InterestStatus::RejeitadoAuto]);

            $previousOwner = $shift->doctor;

            $shift->update([
                'user_id' => $interest->user_id,
                'status' => ShiftStatus::Pendente,
                'confirmed_at' => null,
            ]);

            $shift->load('hospital');
            $when = $shift->date->format('d/m/Y').' às '.$shift->starts_at->format('H:i');
            $winner = $interest->doctor;

            $this->notifications->send(
                $winner,
                'mural_aprovado',
                'Plantão é seu — confirme',
                "Você foi escolhido pro plantão de {$when}. Confirme no app.",
                route('medico.plantao', $shift->id, false),
                $shift->hospital,
            );

            Mail::to($winner->email)->queue(new TrocaAtualizada(
                'Plantão do mural é seu',
                $winner->name,
                ["Você foi escolhido pro plantão de {$when} no {$shift->hospital->name} — confirme no app."],
                route('medico.plantao', $shift->id),
            ));

            if ($previousOwner !== null) {
                $this->notifications->send(
                    $previousOwner,
                    'mural_aprovado',
                    'Plantão repassado',
                    "O plantão de {$when} que você anunciou agora é de {$winner->name}.",
                    route('medico.trocas', absolute: false),
                    $shift->hospital,
                );
            }

            foreach ($losers as $loser) {
                $this->notifications->send(
                    $loser->doctor,
                    'mural_perdeu',
                    'Plantão foi pra outro colega',
                    "O gestor escolheu outro médico pro plantão de {$when}.",
                    route('medico.mural', absolute: false),
                    $shift->hospital,
                );
            }

            return $interest;
        });
    }

    /**
     * Gestor rejeita um interessado específico.
     */
    public function rejectInterest(ShiftInterest $interest, User $gestor): ShiftInterest
    {
        $shift = $interest->shift;

        if (! $gestor->isGestorOf($shift->hospital)) {
            throw new \InvalidArgumentException('Você não é gestor deste hospital.');
        }

        $updated = ShiftInterest::query()
            ->whereKey($interest->id)
            ->where('status', InterestStatus::Pendente)
            ->update(['status' => InterestStatus::Rejeitado]);

        if ($updated === 0) {
            throw new \InvalidArgumentException('Este interesse já foi decidido.');
        }

        $interest->refresh();
        $when = $shift->date->format('d/m/Y').' às '.$shift->starts_at->format('H:i');

        $this->notifications->send(
            $interest->doctor,
            'mural_rejeitado',
            'Interesse não aprovado',
            "O gestor não aprovou seu interesse no plantão de {$when}.",
            route('medico.mural', absolute: false),
            $shift->hospital,
        );

        return $interest;
    }

    /**
     * Devolve o plantão pro dono original no estado anterior à troca.
     */
    private function restoreShift(ShiftTransfer $transfer): Shift
    {
        $shift = $transfer->shift;

        $shift->update([
            'user_id' => $transfer->from_user_id,
            'status' => $shift->confirmed_at !== null ? ShiftStatus::Confirmado : ShiftStatus::Pendente,
        ]);

        return $shift->load('hospital');
    }

    private function assertReceiverCanDecide(ShiftTransfer $transfer, User $receiver): void
    {
        if ($transfer->to_user_id !== $receiver->id) {
            throw new \InvalidArgumentException('Esta proposta não é pra você.');
        }

        if ($transfer->status !== TransferStatus::AguardandoReceptor) {
            throw new \InvalidArgumentException('Esta proposta já foi respondida.');
        }
    }

    private function assertGestorCanDecide(ShiftTransfer $transfer, User $gestor): void
    {
        if (! $gestor->isGestorOf($transfer->shift->hospital)) {
            throw new \InvalidArgumentException('Você não é gestor deste hospital.');
        }

        if ($transfer->status !== TransferStatus::AguardandoGestor) {
            throw new \InvalidArgumentException('Esta troca não está aguardando aprovação.');
        }
    }
}
