<?php

namespace App\Services;

use App\Models\Schedule;
use App\Models\User;
use App\Support\PhoneNumber;
use Illuminate\Support\Facades\Http;

class WhatsAppService
{
    public function sendSchedulePublished(User $doctor, Schedule $schedule): void
    {
        $this->sendSchedulePublishedTo(firstName($doctor->name), $doctor->phone, $schedule);
    }

    public function sendSchedulePublishedTo(string $recipientName, ?string $recipientPhone, Schedule $schedule): void
    {
        $phone = $this->normalizeBrazilianPhone($recipientPhone);

        if ($phone === null) {
            throw new \InvalidArgumentException('O destinatário não possui um celular válido para WhatsApp.');
        }

        $phoneNumberId = config('services.whatsapp.phone_number_id');
        $token = config('services.whatsapp.token');
        $template = config('services.whatsapp.schedule_published_template');

        if (! is_string($phoneNumberId) || $phoneNumberId === '' || ! is_string($token) || $token === '' || ! is_string($template) || $template === '') {
            throw new \RuntimeException('A integração com WhatsApp não está configurada.');
        }

        $scheduleName = $schedule->shift_board_id !== null
            ? $schedule->board->name
            : 'geral';

        Http::withToken($token)
            ->acceptJson()
            ->timeout(15)
            ->post('https://graph.facebook.com/'.config('services.whatsapp.graph_version').'/'.$phoneNumberId.'/messages', [
                'messaging_product' => 'whatsapp',
                'recipient_type' => 'individual',
                'to' => $phone,
                'type' => 'template',
                'template' => [
                    'name' => $template,
                    'language' => ['code' => config('services.whatsapp.language')],
                    'components' => [[
                        'type' => 'body',
                        'parameters' => [
                            ['type' => 'text', 'text' => $recipientName],
                            ['type' => 'text', 'text' => $scheduleName],
                            ['type' => 'text', 'text' => $schedule->hospital->name],
                            ['type' => 'text', 'text' => $schedule->monthLabel()],
                        ],
                    ]],
                ],
            ])
            ->throw();
    }

    /**
     * Envia um template de WhatsApp com parâmetros posicionais genéricos.
     *
     * @param  list<string>  $parameters
     */
    public function sendTemplate(?string $recipientPhone, string $template, array $parameters): void
    {
        $phone = $this->normalizeBrazilianPhone($recipientPhone);

        if ($phone === null) {
            throw new \InvalidArgumentException('O destinatário não possui um celular válido para WhatsApp.');
        }

        $phoneNumberId = config('services.whatsapp.phone_number_id');
        $token = config('services.whatsapp.token');

        if (! is_string($phoneNumberId) || $phoneNumberId === '' || ! is_string($token) || $token === '' || $template === '') {
            throw new \RuntimeException('A integração com WhatsApp não está configurada.');
        }

        $params = array_map(
            fn ($value) => ['type' => 'text', 'text' => (string) $value],
            array_values($parameters),
        );

        Http::withToken($token)
            ->acceptJson()
            ->timeout(15)
            ->post('https://graph.facebook.com/'.config('services.whatsapp.graph_version').'/'.$phoneNumberId.'/messages', [
                'messaging_product' => 'whatsapp',
                'recipient_type' => 'individual',
                'to' => $phone,
                'type' => 'template',
                'template' => [
                    'name' => $template,
                    'language' => ['code' => config('services.whatsapp.language')],
                    'components' => [[
                        'type' => 'body',
                        'parameters' => $params,
                    ]],
                ],
            ])
            ->throw();
    }

    public function normalizeBrazilianPhone(?string $phone): ?string
    {
        $normalized = PhoneNumber::normalizeStored($phone);

        if ($normalized === null) {
            return null;
        }

        return ltrim($normalized, '+');
    }
}