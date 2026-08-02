<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

#[Fillable(['shift_id', 'user_id', 'type', 'checked_at', 'latitude', 'longitude', 'method'])]
class Checkin extends Model
{
    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'checked_at' => 'datetime',
        ];
    }

    /**
     * @return BelongsTo<Shift, $this>
     */
    public function shift(): BelongsTo
    {
        return $this->belongsTo(Shift::class);
    }

    /**
     * @return BelongsTo<User, $this>
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function typeLabel(): string
    {
        return $this->type === 'in' ? 'Entrada' : 'Saída';
    }

    public function methodLabel(): string
    {
        return match ($this->method) {
            'gps' => 'GPS',
            'qrcode' => 'QR Code',
            default => 'Manual',
        };
    }
}
