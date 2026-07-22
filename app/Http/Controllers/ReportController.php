<?php

namespace App\Http\Controllers;

use App\Enums\ScheduleStatus;
use App\Enums\ShiftStatus;
use App\Models\Shift;
use App\Models\ShiftTransfer;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Symfony\Component\HttpFoundation\Response;

class ReportController extends Controller
{
    /**
     * Relatório mensal em PDF com seções configuráveis.
     */
    public function monthly(Request $request): Response
    {
        $hospital = currentHospital();
        abort_unless($hospital !== null, 403);

        $data = $request->validate([
            'month' => ['required', 'regex:/^\d{4}-\d{2}$/'],
            'sections' => ['sometimes', 'array'],
            'sections.*' => ['string', 'in:resumo,financeiro,plantoes,descobertos,trocas'],
        ]);

        $sections = $data['sections'] ?? ['resumo', 'financeiro', 'plantoes', 'descobertos', 'trocas'];
        $start = Carbon::parse($data['month'].'-01')->startOfMonth();
        $end = $start->copy()->endOfMonth();

        $payable = [ShiftStatus::Confirmado, ShiftStatus::Concluido];
        $dead = [ShiftStatus::Cancelado, ShiftStatus::NaoCumprido];

        $shifts = Shift::query()
            ->where('hospital_id', $hospital->id)
            ->whereBetween('date', [$start, $end])
            ->whereHas('schedule', fn ($q) => $q->where('status', ScheduleStatus::Publicada))
            ->with(['doctor', 'schedule.board'])
            ->orderBy('date')->orderBy('starts_at')
            ->get();

        $active = $shifts->reject(fn ($s) => in_array($s->status, $dead, true));
        $assigned = $active->whereNotNull('user_id');
        $uncovered = $active->whereNull('user_id');

        $doctors = $assigned->groupBy('user_id')->map(function ($group) use ($payable) {
            $payableShifts = $group->filter(fn ($s) => in_array($s->status, $payable, true));

            return [
                'doctor' => $group->first()->doctor,
                'count' => $group->count(),
                'payableCount' => $payableShifts->count(),
                'total' => $group->sum(fn ($s) => (float) $s->amount),
                'payableTotal' => $payableShifts->sum(fn ($s) => (float) $s->amount),
            ];
        })->sortBy(fn ($row) => $row['doctor']->name)->values();

        $transfers = ShiftTransfer::query()
            ->whereHas('shift', fn ($q) => $q->where('hospital_id', $hospital->id)->whereBetween('date', [$start, $end]))
            ->with(['shift.schedule.board', 'fromDoctor', 'toDoctor'])
            ->latest()
            ->get();

        $pdf = Pdf::loadView('reports.monthly', [
            'hospital' => $hospital,
            'monthLabel' => $start->translatedFormat('F/Y'),
            'sections' => $sections,
            'shifts' => $shifts,
            'assigned' => $assigned,
            'uncovered' => $uncovered,
            'doctors' => $doctors,
            'transfers' => $transfers,
            'custoPrevisto' => $assigned->sum(fn ($s) => (float) $s->amount),
            'custoConfirmado' => $assigned->filter(fn ($s) => in_array($s->status, $payable, true))->sum(fn ($s) => (float) $s->amount),
            'horas' => $assigned->sum(fn ($s) => $s->starts_at->diffInMinutes($s->ends_at)) / 60,
            'payable' => $payable,
            'dead' => $dead,
            'generatedAt' => now(),
        ])->setPaper('a4');

        return $pdf->download('relatorio-doctorturn-'.$data['month'].'.pdf');
    }
}
