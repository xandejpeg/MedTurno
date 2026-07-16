<?php

use App\Enums\TransferStatus;
use App\Enums\TransferType;
use App\Models\ShiftInterest;
use App\Models\ShiftTransfer;
use App\Services\TransferService;
use Livewire\Attributes\Layout;
use Livewire\Volt\Component;

new #[Layout('layouts.app')] class extends Component
{
    public function mount(): void
    {
        abort_unless(currentHospital() !== null, 403);
    }

    /**
     * @return array<string, mixed>
     */
    public function with(): array
    {
        $hospital = currentHospital();

        $base = fn () => ShiftTransfer::query()
            ->whereHas('shift', fn ($q) => $q->where('hospital_id', $hospital->id))
            ->with(['shift.hospital', 'fromDoctor', 'toDoctor']);

        return [
            'pending' => $base()->where('status', TransferStatus::AguardandoGestor)->oldest()->get(),
            'waiting' => $base()->where('status', TransferStatus::AguardandoReceptor)->where('type', TransferType::Direta)->oldest()->get(),
            'mural' => $base()->where('status', TransferStatus::AguardandoReceptor)->where('type', TransferType::Mural)
                ->with(['shift.interests' => fn ($q) => $q->pending()->with('doctor')])->oldest()->get(),
            'decided' => $base()->whereNotIn('status', [TransferStatus::AguardandoGestor, TransferStatus::AguardandoReceptor])->latest()->limit(20)->get(),
        ];
    }

    public function approveInterest(int $interestId, TransferService $service): void
    {
        $interest = ShiftInterest::findOrFail($interestId);

        try {
            $service->approveInterest($interest, auth()->user());
            session()->flash('status', 'Interessado aprovado — o plantão passou pro novo médico.');
        } catch (\InvalidArgumentException $e) {
            $this->addError('transfer', $e->getMessage());
        }
    }

    public function rejectInterest(int $interestId, TransferService $service): void
    {
        $interest = ShiftInterest::findOrFail($interestId);

        try {
            $service->rejectInterest($interest, auth()->user());
            session()->flash('status', 'Interesse rejeitado.');
        } catch (\InvalidArgumentException $e) {
            $this->addError('transfer', $e->getMessage());
        }
    }

    public function approve(int $transferId, TransferService $service): void
    {
        $transfer = ShiftTransfer::findOrFail($transferId);

        try {
            $service->approve($transfer, auth()->user());
            session()->flash('status', 'Troca aprovada — o plantão passou pro novo médico.');
        } catch (\InvalidArgumentException $e) {
            $this->addError('transfer', $e->getMessage());
        }
    }

    public function reject(int $transferId, TransferService $service): void
    {
        $transfer = ShiftTransfer::findOrFail($transferId);

        try {
            $service->reject($transfer, auth()->user());
            session()->flash('status', 'Troca recusada — o plantão continua com o médico original.');
        } catch (\InvalidArgumentException $e) {
            $this->addError('transfer', $e->getMessage());
        }
    }
}; ?>

<div>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 leading-tight">Trocas — {{ currentHospital()?->name }}</h2>
    </x-slot>

    <div class="py-12">
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
                    <h3 class="font-medium text-gray-900">Aguardando sua aprovação</h3>
                </div>
                @forelse ($pending as $transfer)
                    <div class="p-6 {{ ! $loop->last ? 'border-b border-gray-100' : '' }}">
                        <div class="flex flex-wrap items-center justify-between gap-4">
                            <div>
                                <p class="font-medium text-gray-900">
                                    {{ $transfer->shift->date->format('d/m/Y') }} · {{ $transfer->shift->starts_at->format('H:i') }}–{{ $transfer->shift->ends_at->format('H:i') }}
                                </p>
                                <p class="text-sm text-gray-500">
                                    {{ $transfer->fromDoctor->name }} → {{ $transfer->toDoctor?->name }}
                                </p>
                                @if ($transfer->reason)
                                    <p class="text-sm text-gray-400 mt-1">Motivo: {{ $transfer->reason }}</p>
                                @endif
                            </div>
                            <div class="flex items-center gap-2">
                                <x-primary-button wire:click="approve({{ $transfer->id }})" type="button">Aprovar</x-primary-button>
                                <x-danger-button wire:click="reject({{ $transfer->id }})" type="button">Recusar</x-danger-button>
                            </div>
                        </div>
                    </div>
                @empty
                    <p class="p-6 text-gray-500">Nenhuma troca aguardando aprovação.</p>
                @endforelse
            </div>

            @if ($waiting->isNotEmpty())
                <div class="bg-white shadow-sm sm:rounded-lg overflow-hidden">
                    <div class="p-6 border-b border-gray-100">
                        <h3 class="font-medium text-gray-900">Aguardando o colega responder</h3>
                    </div>
                    @foreach ($waiting as $transfer)
                        <div class="p-6 {{ ! $loop->last ? 'border-b border-gray-100' : '' }}">
                            <p class="font-medium text-gray-900">
                                {{ $transfer->shift->date->format('d/m/Y') }} · {{ $transfer->shift->starts_at->format('H:i') }}–{{ $transfer->shift->ends_at->format('H:i') }}
                            </p>
                            <p class="text-sm text-gray-500">{{ $transfer->fromDoctor->name }} → {{ $transfer->toDoctor?->name }}</p>
                        </div>
                    @endforeach
                </div>
            @endif

            @if ($mural->isNotEmpty())
                <div class="bg-white shadow-sm sm:rounded-lg overflow-hidden">
                    <div class="p-6 border-b border-gray-100">
                        <h3 class="font-medium text-gray-900">Mural — plantões anunciados</h3>
                    </div>
                    @foreach ($mural as $transfer)
                        <div class="p-6 {{ ! $loop->last ? 'border-b border-gray-100' : '' }} space-y-3">
                            <div>
                                <p class="font-medium text-gray-900">
                                    {{ $transfer->shift->date->format('d/m/Y') }} · {{ $transfer->shift->starts_at->format('H:i') }}–{{ $transfer->shift->ends_at->format('H:i') }}
                                </p>
                                <p class="text-sm text-gray-500">Anunciado por {{ $transfer->fromDoctor->name }}</p>
                            </div>
                            @forelse ($transfer->shift->interests as $interest)
                                <div class="flex flex-wrap items-center justify-between gap-3 bg-gray-50 rounded-lg px-4 py-3">
                                    <p class="text-sm font-medium text-gray-900">{{ $interest->doctor->name }}</p>
                                    <div class="flex items-center gap-2">
                                        <x-primary-button wire:click="approveInterest({{ $interest->id }})" wire:confirm="Escolher {{ $interest->doctor->name }}? Os outros interessados serão rejeitados." type="button">Aprovar</x-primary-button>
                                        <x-danger-button wire:click="rejectInterest({{ $interest->id }})" type="button">Rejeitar</x-danger-button>
                                    </div>
                                </div>
                            @empty
                                <p class="text-sm text-gray-500">Nenhum interessado ainda.</p>
                            @endforelse
                        </div>
                    @endforeach
                </div>
            @endif

            @if ($decided->isNotEmpty())
                <div class="bg-white shadow-sm sm:rounded-lg overflow-hidden">
                    <div class="p-6 border-b border-gray-100">
                        <h3 class="font-medium text-gray-900">Histórico</h3>
                    </div>
                    @foreach ($decided as $transfer)
                        <div class="p-6 flex flex-wrap items-center justify-between gap-4 {{ ! $loop->last ? 'border-b border-gray-100' : '' }}">
                            <div>
                                <p class="font-medium text-gray-900">
                                    {{ $transfer->shift->date->format('d/m/Y') }} · {{ $transfer->shift->starts_at->format('H:i') }}–{{ $transfer->shift->ends_at->format('H:i') }}
                                </p>
                                <p class="text-sm text-gray-500">{{ $transfer->fromDoctor->name }} → {{ $transfer->toDoctor?->name }}</p>
                            </div>
                            <span class="text-xs px-2 py-1 rounded-full
                                @if ($transfer->status === TransferStatus::Aprovada) bg-green-100 text-green-800
                                @else bg-gray-100 text-gray-700 @endif">
                                {{ $transfer->status->label() }}
                            </span>
                        </div>
                    @endforeach
                </div>
            @endif
        </div>
    </div>
</div>
