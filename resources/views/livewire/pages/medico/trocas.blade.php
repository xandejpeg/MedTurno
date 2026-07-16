<?php

use App\Enums\TransferStatus;
use App\Models\ShiftTransfer;
use App\Services\TransferService;
use Livewire\Attributes\Layout;
use Livewire\Volt\Component;

new #[Layout('layouts.app')] class extends Component
{
    /**
     * @return array<string, mixed>
     */
    public function with(): array
    {
        $userId = auth()->id();

        return [
            'received' => ShiftTransfer::query()
                ->where('to_user_id', $userId)
                ->with(['shift.hospital', 'fromDoctor'])
                ->latest()
                ->limit(30)
                ->get(),
            'sent' => ShiftTransfer::query()
                ->where('from_user_id', $userId)
                ->with(['shift.hospital', 'toDoctor'])
                ->latest()
                ->limit(30)
                ->get(),
        ];
    }

    public function accept(int $transferId, TransferService $service): void
    {
        $transfer = ShiftTransfer::findOrFail($transferId);

        try {
            $service->acceptByReceiver($transfer, auth()->user());
            session()->flash('status', 'Troca aceita! Aguardando aprovação do gestor.');
        } catch (\InvalidArgumentException $e) {
            $this->addError('transfer', $e->getMessage());
        }
    }

    public function refuse(int $transferId, TransferService $service): void
    {
        $transfer = ShiftTransfer::findOrFail($transferId);

        try {
            $service->rejectByReceiver($transfer, auth()->user());
            session()->flash('status', 'Troca recusada.');
        } catch (\InvalidArgumentException $e) {
            $this->addError('transfer', $e->getMessage());
        }
    }

    public function cancel(int $transferId, TransferService $service): void
    {
        $transfer = ShiftTransfer::findOrFail($transferId);

        try {
            $service->cancelByOwner($transfer, auth()->user());
            session()->flash('status', 'Proposta cancelada.');
        } catch (\InvalidArgumentException $e) {
            $this->addError('transfer', $e->getMessage());
        }
    }
}; ?>

<div>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 leading-tight">Minhas trocas</h2>
    </x-slot>

    <div class="py-12 pb-24 sm:pb-12">
        <div class="max-w-4xl mx-auto sm:px-6 lg:px-8 space-y-6">

            @if (session('status'))
                <div class="bg-green-50 border border-green-200 text-green-800 rounded-lg p-4 text-sm">
                    {{ session('status') }}
                </div>
            @endif

            @error('transfer')
                <div class="bg-red-50 border border-red-200 text-red-800 rounded-lg p-4 text-sm">{{ $message }}</div>
            @enderror

            <div class="bg-white shadow-sm sm:rounded-lg overflow-hidden">
                <div class="p-6 border-b border-gray-100">
                    <h3 class="font-medium text-gray-900">Recebidas</h3>
                </div>
                @forelse ($received as $transfer)
                    <div class="p-6 {{ ! $loop->last ? 'border-b border-gray-100' : '' }}">
                        <div class="flex flex-wrap items-center justify-between gap-4">
                            <div>
                                <p class="font-medium text-gray-900">
                                    {{ $transfer->shift->date->format('d/m/Y') }} · {{ $transfer->shift->starts_at->format('H:i') }}–{{ $transfer->shift->ends_at->format('H:i') }}
                                </p>
                                <p class="text-sm text-gray-500">
                                    {{ $transfer->shift->hospital->name }} · de {{ $transfer->fromDoctor->name }}
                                </p>
                                @if ($transfer->reason)
                                    <p class="text-sm text-gray-400 mt-1">Motivo: {{ $transfer->reason }}</p>
                                @endif
                            </div>
                            <div class="flex items-center gap-2">
                                @if ($transfer->status === TransferStatus::AguardandoReceptor)
                                    <x-primary-button wire:click="accept({{ $transfer->id }})" type="button">Aceito</x-primary-button>
                                    <x-danger-button wire:click="refuse({{ $transfer->id }})" type="button">Recuso</x-danger-button>
                                @else
                                    <span class="text-xs px-2 py-1 rounded-full bg-gray-100 text-gray-700">{{ $transfer->status->label() }}</span>
                                @endif
                            </div>
                        </div>
                    </div>
                @empty
                    <p class="p-6 text-gray-500">Nenhuma proposta recebida.</p>
                @endforelse
            </div>

            <div class="bg-white shadow-sm sm:rounded-lg overflow-hidden">
                <div class="p-6 border-b border-gray-100">
                    <h3 class="font-medium text-gray-900">Enviadas</h3>
                </div>
                @forelse ($sent as $transfer)
                    <div class="p-6 {{ ! $loop->last ? 'border-b border-gray-100' : '' }}">
                        <div class="flex flex-wrap items-center justify-between gap-4">
                            <div>
                                <p class="font-medium text-gray-900">
                                    {{ $transfer->shift->date->format('d/m/Y') }} · {{ $transfer->shift->starts_at->format('H:i') }}–{{ $transfer->shift->ends_at->format('H:i') }}
                                </p>
                                <p class="text-sm text-gray-500">
                                    {{ $transfer->shift->hospital->name }} · para {{ $transfer->toDoctor?->name ?? '—' }}
                                </p>
                            </div>
                            <div class="flex items-center gap-2">
                                <span class="text-xs px-2 py-1 rounded-full
                                    @if ($transfer->status === TransferStatus::Aprovada) bg-green-100 text-green-800
                                    @elseif ($transfer->status->isActive()) bg-blue-100 text-blue-800
                                    @else bg-gray-100 text-gray-700 @endif">
                                    {{ $transfer->status->label() }}
                                </span>
                                @if ($transfer->status === TransferStatus::AguardandoReceptor)
                                    <x-secondary-button wire:click="cancel({{ $transfer->id }})" type="button">Cancelar</x-secondary-button>
                                @endif
                            </div>
                        </div>
                    </div>
                @empty
                    <p class="p-6 text-gray-500">Nenhuma proposta enviada.</p>
                @endforelse
            </div>
        </div>
    </div>
</div>
