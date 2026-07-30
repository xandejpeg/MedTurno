<?php

namespace App\Services;

use App\Models\Schedule;
use App\Models\User;
use Illuminate\Support\Facades\Http;

class WhatsAppService
{
    public function sendSchedulePublished(User $doctor, Schedule $schedule): void
    {
        $this->sendSchedulePublishedTo($doctor->name, $doctor->phone, $schedule);
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

    public function normalizeBrazilianPhone(?string $phone): ?string
    {
        $hasInternationalPrefix = str_starts_with(trim($phone ?? ''), '+');
        $digits = preg_replace('/\D+/', '', $phone ?? '');

        if ($digits === null || $digits === '') {
            return null;
        }

        if ($hasInternationalPrefix) {
            return strlen($digits) >= 8 && strlen($digits) <= 15
                ? $digits
                : null;
        }

        if (str_starts_with($digits, '0')) {
            $digits = substr($digits, 1);
        }

        if (str_starts_with($digits, '55') && in_array(strlen($digits), [12, 13], true)) {
            return $digits;
        }

        if (in_array(strlen($digits), [10, 11], true)) {
            return '55'.$digits;
        }

        return null;
    }
}