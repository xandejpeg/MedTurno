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
}
