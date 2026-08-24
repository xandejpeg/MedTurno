<?php

declare(strict_types=1);

namespace App\Services;

use App\Mail\EscalaPublicada;
use App\Models\Schedule;
use App\Models\Shift;
use App\Models\User;
use Throwable;

/**
 * Monta a PRÉVIA das notificações de um plantão/escala.
 *
 * Espelha exatamente o que ScheduleService::publish() enviaria, mas NUNCA
 * envia nada: não chama Mail::to(), não faz dispatch() de job e não escreve
 * em communication_logs. É seguro rodar em produção.
 */
class NotificationPreviewService
{
    /**
     * Prévia para todos os médicos com plantão na escala.
     *
     * @return array<int, array<string, mixed>>
     */
    public function forSchedule(Schedule $schedule): array
    {
        $schedule->loadMissing(['hospital', 'board']);

        $doctorIds = $schedule->shifts()
            ->whereNotNull('user_id')
            ->distinct()
            ->pluck('user_id');

        $doctors = User::whereIn('id', $doctorIds)->orderBy('name')->get();

        return $doctors
            ->map(fn (User $doctor) => $this->forDoctor($schedule, $doctor))
            ->all();
    }

    /**
     * Prévia completa (e-mail + WhatsApp + notificação interna) de um médico.
     *
     * @return array<string, mixed>
     */
    public function forDoctor(Schedule $schedule, User $doctor): array
    {
        $schedule->loadMissing(['hospital', 'board']);

        $greeting = greetingName($doctor);

        // mesmo cálculo do ScheduleService::publish()
        $escalaNome = $schedule->shift_board_id !== null
            ? $schedule->board->name
            : $schedule->hospital->name;

        // o WhatsAppService usa 'geral' quando não há quadro
        $scheduleNameZap = $schedule->shift_board_id !== null
            ? $schedule->board->name
            : 'geral';

        $shifts = Shift::query()
            ->where('schedule_id', $schedule->id)
            ->where('user_id', $doctor->id)
            ->orderBy('date')
            ->get();

        return [
            'doctor' => $doctor,
            'greeting' => $greeting,
            'shifts' => $shifts,
            'shiftsCount' => $shifts->count(),
            'email' => $this->emailPreview($schedule, $doctor, $greeting, $escalaNome),
            'whatsapp' => $this->whatsappPreview($schedule, $doctor, $greeting, $scheduleNameZap),
            'interna' => [
                'type' => 'escala_publicada',
                'title' => 'Escala publicada',
                'body' => "A escala {$escalaNome} — {$schedule->monthLabel()} foi publicada.",
                'link' => route('medico.escala', ['month' => sprintf('%d-%02d', $schedule->year, $schedule->month)], false),
            ],
        ];
    }

    /**
     * @return array<string, mixed>
     */
    private function emailPreview(Schedule $schedule, User $doctor, string $greeting, string $escalaNome): array
    {
        $mailable = new EscalaPublicada($schedule, $greeting);

        $html = null;
        $erro = null;

        try {
            // render() é puro: monta o HTML final sem tocar no transporte
            $html = $mailable->render();
        } catch (Throwable $e) {
            $erro = $e->getMessage();
        }

        return [
            'destinatario' => $doctor->email,
            'assunto' => "Sua escala de {$schedule->monthLabel()} está publicada — DoctorTurn",
            // texto igual ao gravado em communication_logs no envio real
            'corpoTexto' => "Olá, {$greeting}!\n\nA escala {$escalaNome} — {$schedule->monthLabel()} do hospital {$schedule->hospital->name} foi publicada.\n\nAcesse o DoctorTurn para ver seus plantões e confirmá-los.",
            'html' => $html,
            'erro' => $erro,
            'remetente' => config('mail.from.address'),
            'mailer' => config('mail.default'),
            'enviaria' => filled($doctor->email),
            'motivo' => filled($doctor->email) ? null : 'Usuário sem e-mail cadastrado.',
        ];
    }

    /**
     * @return array<string, mixed>
     */
    private function whatsappPreview(Schedule $schedule, User $doctor, string $greeting, string $scheduleName): array
    {
        $service = app(WhatsAppService::class);

        $telefoneNormalizado = null;
        $erroTelefone = null;

        try {
            $telefoneNormalizado = $service->normalizeBrazilianPhone($doctor->phone);
        } catch (Throwable $e) {
            $erroTelefone = $e->getMessage();
        }

        $habilitado = (bool) config('services.whatsapp.enabled');
        $temTelefone = $doctor->phone !== null && $telefoneNormalizado !== null;

        // corpo idêntico ao que o ScheduleService grava em communication_logs
        $corpo = "Olá, {$greeting}! A escala {$scheduleName} de {$schedule->hospital->name}, referente a {$schedule->monthLabel()}, foi publicada no *DoctorTurn*.\n\nAcesse a plataforma para consultar seus plantões e confirmar sua escala:\nhttps://doctorturn.com.br/medico";

        $motivo = null;

        if (! $habilitado) {
            $motivo = 'Integração de WhatsApp desativada (WHATSAPP_ENABLED=false).';
        } elseif ($doctor->phone === null) {
            $motivo = 'Usuário sem telefone cadastrado.';
        } elseif ($telefoneNormalizado === null) {
            $motivo = 'Telefone cadastrado é inválido para WhatsApp: '.$doctor->phone;
        }

        return [
            'template' => config('services.whatsapp.schedule_published_template'),
            'idioma' => config('services.whatsapp.language'),
            'telefoneBruto' => $doctor->phone,
            'telefoneNormalizado' => $telefoneNormalizado,
            'erroTelefone' => $erroTelefone,
            'corpo' => $corpo,
            // os 4 parâmetros posicionais do template aprovado na Meta
            'parametros' => [
                '{{1}}' => $greeting,
                '{{2}}' => $scheduleName,
                '{{3}}' => $schedule->hospital->name,
                '{{4}}' => $schedule->monthLabel(),
            ],
            'enviaria' => $habilitado && $temTelefone,
            'motivo' => $motivo,
        ];
    }
}
