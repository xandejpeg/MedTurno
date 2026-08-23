<?php

namespace App\Services;

use App\Models\Hospital;
use App\Models\Invoice;
use Illuminate\Support\Carbon;

class InvoiceService
{
    public function __construct(
        private FinancialReportService $financial,
    ) {}

    /**
     * Gera os dados base para emissão de uma NFS-e (itens, valores, período, tomador).
     *
     * @return array{tomador: array{name: string, cnpj: string|null}, periodo: array{inicio: string, fim: string}, itens: list<array{descricao: string, quantidade: int, valor_unitario: float, valor_total: float}>, valor_total: float}
     */
    public function baseData(Hospital $hospital, Carbon $from, Carbon $to): array
    {
        $byTeam = $this->financial->consolidatedByTeam($hospital, $from, $to, ['include_bonus' => true]);

        $itens = $byTeam->map(fn ($row) => [
            'descricao' => "Serviços de plantão médico — {$row['equipe']}",
            'quantidade' => $row['plantoes'],
            'valor_unitario' => $row['plantoes'] > 0 ? round($row['valor'] / $row['plantoes'], 2) : 0.0,
            'valor_total' => $row['valor'],
        ])->values()->all();

        $valorTotal = round(collect($itens)->sum('valor_total'), 2);

        return [
            'tomador' => [
                'name' => $hospital->name,
                'cnpj' => $hospital->cnpj,
            ],
            'periodo' => [
                'inicio' => $from->toDateString(),
                'fim' => $to->toDateString(),
            ],
            'itens' => $itens,
            'valor_total' => $valorTotal,
        ];
    }

    /**
     * Registra uma NFS emitida.
     */
    public function register(Hospital $hospital, Carbon $from, Carbon $to, float $amount, ?string $number = null, ?Carbon $issueDate = null, ?string $notes = null): Invoice
    {
        return Invoice::create([
            'hospital_id' => $hospital->id,
            'number' => $number,
            'issue_date' => $issueDate ?? now(),
            'period_start' => $from->toDateString(),
            'period_end' => $to->toDateString(),
            'amount' => $amount,
            'status' => $number !== null ? 'emitida' : 'rascunho',
            'notes' => $notes,
        ]);
    }
}
