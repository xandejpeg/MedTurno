<?php

use App\Models\Invoice;
use App\Services\InvoiceService;
use Illuminate\Support\Carbon;
use Livewire\Attributes\Layout;
use Livewire\Volt\Component;

new #[Layout('layouts.app')] class extends Component
{
    public string $from = '';

    public string $to = '';

    public string $number = '';

    public string $notes = '';

    public function mount(): void
    {
        abort_unless(auth()->user()->isGestor(), 403);
        $this->from = now()->startOfMonth()->toDateString();
        $this->to = now()->endOfMonth()->toDateString();
    }

    public function registerInvoice(InvoiceService $service): void
    {
        $hospital = currentHospital();
        abort_unless($hospital !== null, 403);

        $data = $this->validate([
            'from' => ['required', 'date'],
            'to' => ['required', 'date', 'after_or_equal:from'],
            'number' => ['nullable', 'string', 'max:30'],
            'notes' => ['nullable', 'string', 'max:500'],
        ]);

        $base = $service->baseData($hospital, Carbon::parse($data['from']), Carbon::parse($data['to']));

        $service->register(
            $hospital,
            Carbon::parse($data['from']),
            Carbon::parse($data['to']),
            $base['valor_total'],
            $data['number'] !== '' ? $data['number'] : null,
            $data['number'] !== '' ? now() : null,
            $data['notes'] !== '' ? $data['notes'] : null,
        );

        $this->reset(['number', 'notes']);
        session()->flash('status', 'NFS registrada.');
    }

    public function issueNfse(\App\Services\NfseService $service): void
    {
        $hospital = currentHospital();
        abort_unless($hospital !== null, 403);

        $data = $this->validate([
            'from' => ['required', 'date'],
            'to' => ['required', 'date', 'after_or_equal:from'],
        ]);

        $invoice = $service->issue($hospital, \Illuminate\Support\Carbon::parse($data['from']), \Illuminate\Support\Carbon::parse($data['to']));

        if ($invoice->number !== null) {
            session()->flash('status', "NFS-e emitida com sucesso — número {$invoice->number}.");
        } else {
            session()->flash('status', 'NFS registrada como rascunho (provedor não configurado ou falha na emissão).');
        }
    }

    public function cancelInvoice(int $invoiceId, \App\Services\NfseService $service): void
    {
        $invoice = Invoice::where('hospital_id', currentHospital()?->id)->findOrFail($invoiceId);

        try {
            $service->cancel($invoice);
            session()->flash('status', "NFS {$invoice->number} cancelada com sucesso.");
        } catch (\Throwable $e) {
            session()->flash('status', 'Erro ao cancelar: '.$e->getMessage());
        }
    }

    /**
     * @return array<string, mixed>
     */
    public function with(InvoiceService $service): array
    {
        $hospital = currentHospital();

        $base = $service->baseData($hospital, Carbon::parse($this->from), Carbon::parse($this->to));

        $invoices = Invoice::where('hospital_id', $hospital?->id)
            ->orderByDesc('issue_date')
            ->limit(50)
            ->get();

        return [
            'hospital' => $hospital,
            'base' => $base,
            'invoices' => $invoices,
        ];
    }
}; ?>

<div>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 leading-tight">Notas Fiscais de Serviços</h2>
        <p class="text-sm text-gray-500">{{ $hospital?->name }}</p>
    </x-slot>

    <div class="py-6">
        <div class="max-w-5xl mx-auto sm:px-6 lg:px-8 space-y-6">
            @if (session('status'))
                <div class="rounded-lg bg-teal-50 text-teal-800 px-4 py-3 text-sm">{{ session('status') }}</div>
            @endif

            {{-- Base para NFS --}}
            <div class="bg-white shadow-sm sm:rounded-lg p-6">
                <h3 class="text-sm font-semibold text-gray-800 mb-4">Base para emissão de NFS-e</h3>
                <div class="grid grid-cols-2 gap-4 mb-4">
                    <div>
                        <x-input-label for="from" value="De" />
                        <x-text-input wire:model.live="from" id="from" type="date" class="mt-1 block w-full" />
                    </div>
                    <div>
                        <x-input-label for="to" value="Até" />
                        <x-text-input wire:model.live="to" id="to" type="date" class="mt-1 block w-full" />
                    </div>
                </div>

                <div class="rounded-lg border border-gray-200 bg-gray-50 p-4">
                    <p class="text-xs font-semibold uppercase text-gray-500">Tomador</p>
                    <p class="text-sm text-gray-900">{{ $base['tomador']['name'] }}{{ $base['tomador']['cnpj'] ? ' — '.$base['tomador']['cnpj'] : '' }}</p>

                    <p class="mt-3 text-xs font-semibold uppercase text-gray-500">Itens</p>
                    <table class="mt-1 w-full text-sm">
                        <thead>
                            <tr class="text-left text-xs text-gray-400">
                                <th class="py-1">Descrição</th>
                                <th class="py-1 text-right">Qtd</th>
                                <th class="py-1 text-right">Vlr unit.</th>
                                <th class="py-1 text-right">Vlr total</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach ($base['itens'] as $item)
                                <tr class="border-t border-gray-100">
                                    <td class="py-1.5">{{ $item['descricao'] }}</td>
                                    <td class="py-1.5 text-right">{{ $item['quantidade'] }}</td>
                                    <td class="py-1.5 text-right">R$ {{ number_format($item['valor_unitario'], 2, ',', '.') }}</td>
                                    <td class="py-1.5 text-right font-medium">R$ {{ number_format($item['valor_total'], 2, ',', '.') }}</td>
                                </tr>
                            @endforeach
                        </tbody>
                        <tfoot>
                            <tr class="border-t border-gray-200 font-semibold">
                                <td colspan="3" class="py-2 text-right">Total</td>
                                <td class="py-2 text-right text-teal-700">R$ {{ number_format($base['valor_total'], 2, ',', '.') }}</td>
                            </tr>
                        </tfoot>
                    </table>
                </div>

                <div class="mt-4 flex flex-wrap items-center justify-between gap-3">
                    <form wire:submit="registerInvoice" class="grid grid-cols-1 sm:grid-cols-3 gap-4 flex-1">
                        <div>
                            <x-input-label for="number" value="Número da NFS (se já emitida)" />
                            <x-text-input wire:model="number" id="number" type="text" class="mt-1 block w-full" placeholder="Ex.: 12345" />
                        </div>
                        <div class="sm:col-span-2">
                            <x-input-label for="notes" value="Observações" />
                            <x-text-input wire:model="notes" id="notes" type="text" class="mt-1 block w-full" />
                        </div>
                        <div class="sm:col-span-3 flex justify-end">
                            <x-secondary-button type="submit">Registrar manualmente</x-secondary-button>
                        </div>
                    </form>
                    <x-primary-button wire:click="issueNfse" wire:confirm="Emitir a NFS-e para o período selecionado?" type="button" class="shrink-0">
                        Emitir NFS-e
                    </x-primary-button>
                </div>
            </div>

            {{-- NFS registradas --}}
            <div class="bg-white shadow-sm sm:rounded-lg">
                <p class="border-b border-gray-100 px-6 py-3 text-sm font-semibold text-gray-700">NFS registradas</p>
                <ul class="divide-y divide-gray-50">
                    @forelse ($invoices as $invoice)
                        <li class="flex items-center justify-between gap-3 px-6 py-3">
                            <div class="min-w-0">
                                <p class="text-sm font-medium text-gray-900">
                                    {{ $invoice->number ? 'NFS '.$invoice->number : 'Rascunho' }}
                                    — R$ {{ number_format((float) $invoice->amount, 2, ',', '.') }}
                                </p>
                                <p class="text-xs text-gray-500">
                                    {{ $invoice->period_start->format('d/m/Y') }} a {{ $invoice->period_end->format('d/m/Y') }}
                                    @if ($invoice->issue_date) · emitida em {{ $invoice->issue_date->format('d/m/Y') }} @endif
                                </p>
                            </div>
                            <div class="flex items-center gap-2">
                                <span class="inline-flex rounded-full px-2 py-0.5 text-xs font-medium {{ $invoice->status === 'emitida' ? 'bg-green-100 text-green-700' : ($invoice->status === 'cancelada' ? 'bg-red-100 text-red-700' : 'bg-gray-100 text-gray-500') }}">
                                    {{ $invoice->statusLabel() }}
                                </span>
                                @if ($invoice->status === 'emitida' && $invoice->number !== null)
                                    <button wire:click="cancelInvoice({{ $invoice->id }})" wire:confirm="Cancelar a NFS {{ $invoice->number }}?" type="button"
                                            class="rounded-md bg-red-50 px-2 py-1 text-xs font-medium text-red-700 hover:bg-red-100">
                                        Cancelar
                                    </button>
                                @endif
                            </div>
                        </li>
                    @empty
                        <li class="px-6 py-8 text-center text-sm text-gray-400">Nenhuma NFS registrada.</li>
                    @endforelse
                </ul>
            </div>
        </div>
    </div>
</div>
