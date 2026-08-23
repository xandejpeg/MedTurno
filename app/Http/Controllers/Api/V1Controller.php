<?php

namespace App\Http\Controllers\Api;

use App\Enums\Role;
use App\Enums\ScheduleStatus;
use App\Http\Controllers\Controller;
use App\Models\Schedule;
use App\Models\Shift;
use App\Models\User;
use Illuminate\Http\Request;

class V1Controller extends Controller
{
    /**
     * GET /api/v1/escalas — escalas do hospital autenticado.
     */
    public function schedules(Request $request)
    {
        $hospital = $request->attributes->get('hospital');

        $schedules = Schedule::where('hospital_id', $hospital->id)
            ->orderByDesc('year')
            ->orderByDesc('month')
            ->get(['id', 'year', 'month', 'status', 'version', 'published_at', 'created_by']);

        return response()->json(['data' => $schedules]);
    }

    /**
     * GET /api/v1/escalas/{schedule}/plantoes — plantões de uma escala.
     */
    public function shifts(Request $request, Schedule $schedule)
    {
        $hospital = $request->attributes->get('hospital');
        abort_unless($schedule->hospital_id === $hospital->id, 404);

        $shifts = $schedule->shifts()
            ->with(['doctor:id,name,email,phone,crm', 'unit:id,name'])
            ->orderBy('date')
            ->orderBy('starts_at')
            ->get()
            ->map(fn (Shift $s) => [
                'id' => $s->id,
                'date' => $s->date->toDateString(),
                'starts_at' => $s->starts_at->toIso8601String(),
                'ends_at' => $s->ends_at->toIso8601String(),
                'period' => $s->period,
                'status' => $s->status->value,
                'amount' => $s->amount,
                'unit' => $s->unit?->name,
                'doctor' => $s->doctor ? [
                    'id' => $s->doctor->id,
                    'name' => $s->doctor->name,
                    'email' => $s->doctor->email,
                    'phone' => $s->doctor->phone,
                    'crm' => $s->doctor->crm,
                ] : null,
            ]);

        return response()->json(['data' => $shifts]);
    }

    /**
     * GET /api/v1/profissionais — médicos do hospital.
     */
    public function professionals(Request $request)
    {
        $hospital = $request->attributes->get('hospital');

        $doctors = User::query()
            ->whereHas('hospitalMemberships', fn ($q) => $q->where('hospital_id', $hospital->id)->where('role', Role::Medico)->where('active', true))
            ->with(['tags:id,name'])
            ->get(['id', 'name', 'email', 'phone', 'cpf', 'crm', 'crm_uf', 'specialty', 'gender'])
            ->map(fn (User $u) => [
                'id' => $u->id,
                'name' => $u->name,
                'email' => $u->email,
                'phone' => $u->phone,
                'cpf' => $u->cpf,
                'crm' => $u->crm,
                'crm_uf' => $u->crm_uf,
                'specialty' => $u->specialty,
                'gender' => $u->gender,
                'tags' => $u->tags->pluck('name'),
            ]);

        return response()->json(['data' => $doctors]);
    }

    /**
     * GET /api/v1/checkins — registros de check-in/check-out do hospital.
     */
    public function checkins(Request $request)
    {
        $hospital = $request->attributes->get('hospital');

        $checkins = \App\Models\Checkin::with(['shift:id,date,starts_at,ends_at', 'user:id,name'])
            ->whereHas('shift', fn ($q) => $q->where('hospital_id', $hospital->id))
            ->orderByDesc('checked_at')
            ->limit(500)
            ->get()
            ->map(fn ($c) => [
                'id' => $c->id,
                'type' => $c->type,
                'method' => $c->method,
                'checked_at' => $c->checked_at->toIso8601String(),
                'shift' => $c->shift ? [
                    'id' => $c->shift->id,
                    'date' => $c->shift->date->toDateString(),
                    'starts_at' => $c->shift->starts_at->toIso8601String(),
                    'ends_at' => $c->shift->ends_at->toIso8601String(),
                ] : null,
                'doctor' => $c->user?->name,
            ]);

        return response()->json(['data' => $checkins]);
    }
}
