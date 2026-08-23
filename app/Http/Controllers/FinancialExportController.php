<?php

namespace App\Http\Controllers;

use App\Services\ExcelReportGenerator;
use App\Services\FinancialReportService;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;

class FinancialExportController extends Controller
{
    public function __invoke(Request $request, FinancialReportService $service, ExcelReportGenerator $excel)
    {
        abort_unless(auth()->user()->isGestor(), 403);

        $hospital = currentHospital();
        abort_unless($hospital !== null, 403);

        $from = Carbon::parse($request->query('from', now()->startOfMonth()->toDateString()));
        $to = Carbon::parse($request->query('to', now()->endOfMonth()->toDateString()));
        $view = $request->query('view', 'medico');

        $filters = array_filter([
            'schedule_id' => $request->query('schedule_id'),
            'user_id' => $request->query('user_id'),
            'tag' => $request->query('tag'),
            'include_bonus' => $request->boolean('include_bonus', true),
        ]);

        [$headings, $rows] = match ($view) {
            'equipe' => $this->teamRows($service, $hospital, $from, $to, $filters),
            'turno' => $this->shiftRows($service, $hospital, $from, $to, $filters),
            default => $this->doctorRows($service, $hospital, $from, $to, $filters),
        };

        $path = $excel->generate("financeiro-{$view}-{$from->format('Ymd')}-{$to->format('Ymd')}.xlsx", $headings, $rows);

        return response()->download($path)->deleteFileAfterSend();
    }

    private function doctorRows($service, $hospital, $from, $to, $filters): array
    {
        $rows = $service->consolidatedByDoctor($hospital, $from, $to, $filters)
            ->map(fn ($r) => [$r['doctor']?->name, $r['plantoes'], $r['horas'], $r['valor']])
            ->all();

        return [['Médico', 'Plantões', 'Horas', 'Valor (R$)'], $rows];
    }

    private function teamRows($service, $hospital, $from, $to, $filters): array
    {
        $rows = $service->consolidatedByTeam($hospital, $from, $to, $filters)
            ->map(fn ($r) => [$r['equipe'], $r['plantoes'], $r['horas'], $r['valor']])
            ->all();

        return [['Equipe', 'Plantões', 'Horas', 'Valor (R$)'], $rows];
    }

    private function shiftRows($service, $hospital, $from, $to, $filters): array
    {
        $rows = $service->analyticByShift($hospital, $from, $to, $filters)
            ->map(fn ($s) => [
                $s->date->format('d/m/Y'),
                $s->starts_at->format('H:i').'–'.$s->ends_at->format('H:i'),
                $s->schedule?->board?->name ?? 'Geral',
                $s->doctor?->name ?? '—',
                (float) $s->amount + (float) $s->bonus_amount,
            ])
            ->all();

        return [['Data', 'Horário', 'Equipe', 'Médico', 'Valor (R$)'], $rows];
    }
}
