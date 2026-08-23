<?php

namespace App\Services;

use Barryvdh\DomPDF\Facade\Pdf;

class PdfReportGenerator
{
    /**
     * Gera o PDF do roadmap completo.
     */
    public function roadmap(): string
    {
        $html = view('reports.pdf.roadmap')->render();
        $path = storage_path('app/reports/roadmap-doctorturn.pdf');
        @mkdir(dirname($path), 0755, true);
        Pdf::loadHTML($html)->setPaper('a4')->save($path);

        return $path;
    }

    /**
     * Gera o PDF da parte financeira.
     */
    public function financeiro(): string
    {
        $html = view('reports.pdf.financeiro')->render();
        $path = storage_path('app/reports/financeiro-doctorturn.pdf');
        @mkdir(dirname($path), 0755, true);
        Pdf::loadHTML($html)->setPaper('a4')->save($path);

        return $path;
    }

    /**
     * Gera o PDF da escala (calendário com médicos por plantão).
     */
    public function escala(\App\Models\Schedule $schedule): string
    {
        $html = view('reports.pdf.escala', ['schedule' => $schedule])->render();
        $path = storage_path("app/reports/escala-{$schedule->year}-{$schedule->month}.pdf");
        @mkdir(dirname($path), 0755, true);
        Pdf::loadHTML($html)->setPaper('a4')->save($path);

        return $path;
    }

    /**
     * Gera o PDF de presença (check-in/out por médico e período).
     */
    public function presenca(\App\Models\Hospital $hospital, \Illuminate\Support\Carbon $from, \Illuminate\Support\Carbon $to): string
    {
        $shifts = \App\Models\Shift::query()
            ->where('hospital_id', $hospital->id)
            ->whereBetween('date', [$from, $to])
            ->whereHas('schedule', fn ($q) => $q->where('status', \App\Enums\ScheduleStatus::Publicada))
            ->with(['doctor', 'checkins.user'])
            ->orderBy('date')->orderBy('starts_at')
            ->get();

        $html = view('reports.pdf.presenca', [
            'hospital' => $hospital,
            'from' => $from,
            'to' => $to,
            'shifts' => $shifts,
        ])->render();
        $path = storage_path("app/reports/presenca-{$from->format('Y-m')}.pdf");
        @mkdir(dirname($path), 0755, true);
        Pdf::loadHTML($html)->setPaper('a4')->save($path);

        return $path;
    }

    /**
     * Gera o PDF de aderência a licitação (requisitos e status por edital).
     */
    public function aderencia(\App\Models\Tender $tender): string
    {
        $html = view('reports.pdf.aderencia', ['tender' => $tender])->render();
        $path = storage_path("app/reports/aderencia-{$tender->numero}.pdf");
        @mkdir(dirname($path), 0755, true);
        Pdf::loadHTML($html)->setPaper('a4')->save($path);

        return $path;
    }
}
