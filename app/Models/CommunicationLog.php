<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

#[Fillable(['user_id', 'schedule_id', 'channel', 'recipient', 'subject', 'template', 'body', 'status', 'error'])]
class CommunicationLog extends Model
{
    /**
     * @return BelongsTo<User, $this>
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * @return BelongsTo<Schedule, $this>
     */
    public function schedule(): BelongsTo
    {
        return $this->belongsTo(Schedule::class);
    }

    public function channelLabel(): string
    {
        return match ($this->channel) {
            'email' => 'E-mail',
            'whatsapp' => 'WhatsApp',
            'interna' => 'Notificação interna',
            default => ucfirst((string) $this->channel),
        };
    }
}
