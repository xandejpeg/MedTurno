<?php

namespace App\Services;

use App\Models\Checkin;
use App\Models\Shift;
use App\Models\User;

class CheckinService
{
    /**
     * Registra um check-in ou check-out.
     *
     * @param  'in'|'out'  $type
     * @param  'manual'|'gps'|'qrcode'  $method
     */
    public function record(Shift $shift, User $user, string $type, string $method = 'manual', ?float $lat = null, ?float $lng = null): Checkin
    {
        if ($shift->user_id !== $user->id) {
            throw new \InvalidArgumentException('Este plantão não é seu.');
        }

        if (! in_array($type, ['in', 'out'], true)) {
            throw new \InvalidArgumentException('Tipo inválido.');
        }

        $this->assertWindow($shift, $type);

        if ($method === 'gps') {
            $this->assertWithinRadius($shift, $lat, $lng);
        }

        return Checkin::create([
            'shift_id' => $shift->id,
            'user_id' => $user->id,
            'type' => $type,
            'checked_at' => now(),
            'latitude' => $lat,
            'longitude' => $lng,
            'method' => $method,
        ]);
    }

    private function assertWindow(Shift $shift, string $type): void
    {
        $hospital = $shift->hospital;
        $now = now();

        if ($type === 'in') {
            $before = (int) ($hospital->checkin_window_before_min ?? 30);
            $earliest = $shift->starts_at->copy()->subMinutes($before);
            if ($now->lt($earliest)) {
                throw new \InvalidArgumentException("Check-in só abre {$before} min antes do plantão.");
            }
            if ($now->gt($shift->ends_at)) {
                throw new \InvalidArgumentException('O plantão já terminou.');
            }
        } else {
            $after = (int) ($hospital->checkout_window_after_min ?? 30);
            $latest = $shift->ends_at->copy()->addMinutes($after);
            if ($now->lt($shift->starts_at)) {
                throw new \InvalidArgumentException('O plantão ainda não começou.');
            }
            if ($now->gt($latest)) {
                throw new \InvalidArgumentException("Check-out só fica aberto até {$after} min após o plantão.");
            }
        }
    }

    private function assertWithinRadius(Shift $shift, ?float $lat, ?float $lng): void
    {
        $hospital = $shift->hospital;

        if ($hospital->checkin_latitude === null || $hospital->checkin_longitude === null || $hospital->checkin_radius_m === null) {
            return; // sem geolocalização configurada, aceita
        }

        if ($lat === null || $lng === null) {
            throw new \InvalidArgumentException('Localização não informada.');
        }

        $distance = $this->distanceMeters(
            (float) $hospital->checkin_latitude,
            (float) $hospital->checkin_longitude,
            $lat,
            $lng,
        );

        if ($distance > $hospital->checkin_radius_m) {
            throw new \InvalidArgumentException('Você está fora do raio permitido para check-in.');
        }
    }

    /**
     * Distância em metros entre duas coordenadas (fórmula de Haversine).
     */
    private function distanceMeters(float $lat1, float $lng1, float $lat2, float $lng2): float
    {
        $earth = 6371000;
        $dLat = deg2rad($lat2 - $lat1);
        $dLng = deg2rad($lng2 - $lng1);
        $a = sin($dLat / 2) ** 2 + cos(deg2rad($lat1)) * cos(deg2rad($lat2)) * sin($dLng / 2) ** 2;

        return $earth * 2 * atan2(sqrt($a), sqrt(1 - $a));
    }

    /**
     * Gera o payload do QR Code de um plantão (para check-in por QR).
     */
    public function qrPayload(Shift $shift): string
    {
        return 'doctorturn://checkin/'.$shift->id.'/'.hash_hmac('sha256', (string) $shift->id, config('app.key'));
    }

    /**
     * Valida um payload de QR Code e retorna o shift_id se válido.
     */
    public function validateQrPayload(string $payload): ?int
    {
        if (! preg_match('#^doctorturn://checkin/(\d+)/([a-f0-9]{64})$#', $payload, $m)) {
            return null;
        }

        $expected = hash_hmac('sha256', $m[1], config('app.key'));

        return hash_equals($expected, $m[2]) ? (int) $m[1] : null;
    }
}
