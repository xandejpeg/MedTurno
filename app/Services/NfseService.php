<?php

namespace App\Services;

use App\Models\Hospital;
use App\Models\Invoice;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

/**
 * Integração com provedor de NFS-e (genérica, configurável por ambiente).
 * Suporta provedores REST comuns (eNotas, NFE.io, FocusNFe) via configuração.
 */
class NfseService
{
    public function __construct(
        private InvoiceService $invoices,
    ) {}

    /**
     * Emite uma NFS-e para o período, usando o provedor configurado.
     * Retorna a Invoice registrada (com número se emitida).
     */
    public function issue(Hospital $hospital, Carbon $from, Carbon $to): Invoice
    {
        $base = $this->invoices->baseData($hospital, $from, $to);

        if (! $this->isConfigured()) {
            // Sem provedor configurado: registra como rascunho para emissão manual.
            return $this->invoices->register($hospital, $from, $to, $base['valor_total']);
        }

        try {
            $number = $this->sendToProvider($hospital, $base);

            return $this->invoices->register(
                $hospital,
                $from,
                $to,
                $base['valor_total'],
                $number,
                now(),
            );
        } catch (\Throwable $e) {
            Log::error('Falha ao emitir NFS-e.', ['hospital_id' => $hospital->id, 'exception' => $e]);

            // Registra como rascunho para não perder o dado.
            return $this->invoices->register($hospital, $from, $to, $base['valor_total'], null, null, 'Falha na emissão automática: '.$e->getMessage());
        }
    }

    /**
     * Verifica se o provedor de NFS-e está configurado.
     */
    public function isConfigured(): bool
    {
        return config('services.nfse.url') !== null
            && config('services.nfse.token') !== null;
    }

    /**
     * Envia a NFS-e para o provedor e retorna o número.
     */
    private function sendToProvider(Hospital $hospital, array $base): string
    {
        $url = rtrim((string) config('services.nfse.url'), '/');
        $token = (string) config('services.nfse.token');

        $response = Http::withToken($token)
            ->acceptJson()
            ->timeout(30)
            ->post($url.'/invoices', [
                'tomador' => $base['tomador'],
                'periodo' => $base['periodo'],
                'itens' => $base['itens'],
                'valor_total' => $base['valor_total'],
            ])
            ->throw()
            ->json();

        return (string) ($response['number'] ?? $response['numero'] ?? '');
    }

    /**
     * Cancela uma NFS-e no provedor e atualiza o registro local.
     */
    public function cancel(Invoice $invoice): Invoice
    {
        if ($invoice->number === null) {
            throw new \InvalidArgumentException('Esta nota não foi emitida ainda.');
        }

        if ($invoice->status === 'cancelada') {
            return $invoice; // idempotente
        }

        if (! $this->isConfigured()) {
            $invoice->update(['status' => 'cancelada', 'notes' => trim(($invoice->notes ?? '').' Cancelada manualmente (provedor não configurado).')]);

            return $invoice;
        }

        try {
            $url = rtrim((string) config('services.nfse.url'), '/');
            $token = (string) config('services.nfse.token');

            Http::withToken($token)
                ->acceptJson()
                ->timeout(30)
                ->delete($url.'/invoices/'.$invoice->number)
                ->throw();

            $invoice->update(['status' => 'cancelada']);

            return $invoice;
        } catch (\Throwable $e) {
            Log::error('Falha ao cancelar NFS-e.', ['invoice_id' => $invoice->id, 'exception' => $e]);

            throw new \RuntimeException('Não foi possível cancelar a nota no provedor: '.$e->getMessage());
        }
    }

    /**
     * Processa webhook do provedor (atualização de status da nota).
     *
     * @param array{number?: string, numero?: string, status?: string, event?: string} $payload
     */
    public function handleWebhook(array $payload): ?Invoice
    {
        $number = $payload['number'] ?? $payload['numero'] ?? null;

        if ($number === null) {
            return null;
        }

        $invoice = Invoice::where('number', $number)->first();

        if ($invoice === null) {
            return null;
        }

        $status = $payload['status'] ?? $payload['event'] ?? null;

        if ($status === null) {
            return $invoice;
        }

        $normalized = match (strtolower((string) $status)) {
            'cancelada', 'cancelled', 'canceled' => 'cancelada',
            'emitida', 'issued', 'authorized', 'autorizada' => 'emitida',
            default => $invoice->status,
        };

        if ($normalized !== $invoice->status) {
            $invoice->update(['status' => $normalized]);
        }

        return $invoice;
    }

    /**
     * Exporta dados fiscais de um período para XML/CSV (contabilidade).
     *
     * @return array{xml: string, csv: string}
     */
    public function exportForAccounting(Hospital $hospital, Carbon $from, Carbon $to): array
    {
        $base = $this->invoices->baseData($hospital, $from, $to);

        $xml = new \SimpleXMLElement('<nfse/>');
        $xml->addChild('tomador', htmlspecialchars($base['tomador']['name']));
        $xml->addChild('cnpj_tomador', htmlspecialchars($base['tomador']['cnpj'] ?? ''));
        $xml->addChild('periodo_inicio', $base['periodo']['inicio']);
        $xml->addChild('periodo_fim', $base['periodo']['fim']);
        $xml->addChild('valor_total', number_format($base['valor_total'], 2, '.', ''));

        $itens = $xml->addChild('itens');
        foreach ($base['itens'] as $item) {
            $itemNode = $itens->addChild('item');
            $itemNode->addChild('descricao', htmlspecialchars($item['descricao']));
            $itemNode->addChild('quantidade', (string) $item['quantidade']);
            $itemNode->addChild('valor_unitario', number_format($item['valor_unitario'], 2, '.', ''));
            $itemNode->addChild('valor_total', number_format($item['valor_total'], 2, '.', ''));
        }

        $csv = "descricao;quantidade;valor_unitario;valor_total\n";
        foreach ($base['itens'] as $item) {
            $csv .= implode(';', [
                '"'.str_replace('"', '""', $item['descricao']).'"',
                $item['quantidade'],
                number_format($item['valor_unitario'], 2, ',', ''),
                number_format($item['valor_total'], 2, ',', ''),
            ])."\n";
        }

        return [
            'xml' => $xml->asXML(),
            'csv' => $csv,
        ];
    }
}
