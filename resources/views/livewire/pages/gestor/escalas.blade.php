<?php

use App\Models\Schedule;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Url;
use Livewire\Volt\Component;

new #[Layout('layouts.app')] class extends Component
{
    #[Url]
    public string $boardFilter = '';

    #[Url]
    public string $statusFilter = '';

    /**
     * @return array<string, mixed>
     */
    public function with(): array
    {
        $hospital = currentHospital();

        return [
            'hospital' => $hospital,
            'boards' => $hospital
                ? $hospital->shiftBoards()->orderBy('name')->get()
                : collect(),
            'schedules' => $hospital
                ? Schedule::query()
                    ->where('hospital_id', $hospital->id)
                    ->when($this->boardFilter !== '', fn ($q) => $q->where('shift_board_id', (int) $this->boardFilter))
                    ->when($this->statusFilter !== '', fn ($q) => $q->where('status', $this->statusFilter))
                    ->with(['board', 'hospital'])
                    ->withCount([
                        'shifts',
                        'shifts as unassigned_shifts_count' => fn ($q) => $q->whereNull('user_id'),
                    ])
                    ->orderByDesc('year')
                    ->orderByDesc('month')
                    ->get()
                : collect(),
        ];
    }
}; ?>

<div>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 leading-tight">
            Escalas
            @if (currentHospital())
                <span class="text-gray-400 font-normal">— {{ currentHospital()->name }}</span>
            @endif
        </h2>
    </x-slot>

    <div class="py-12">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8 space-y-6">

            <div class="flex flex-wrap items-end justify-between gap-4">
                <div class="flex gap-4">
                    <div>
                        <x-input-label for="boardFilter" value="Quadro" />
                        <select wire:model.live="boardFilter" id="boardFilter" class="block mt-1 border-gray-300 focus:border-teal-500 focus:ring-teal-500 rounded-md shadow-sm">
                            <option value="">Todos</option>
                            @foreach ($boards as $board)
                                <option value="{{ $board->id }}">{{ $board->name }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div>
                        <x-input-label for="statusFilter" value="Status" />
                        <select wire:model.live="statusFilter" id="statusFilter" class="block mt-1 border-gray-300 focus:border-teal-500 focus:ring-teal-500 rounded-md shadow-sm">
                            <option value="">Todos</option>
                            @foreach (\App\Enums\ScheduleStatus::cases() as $status)
                                <option value="{{ $status->value }}">{{ $status->label() }}</option>
                            @endforeach
                        </select>
                    </div>
                </div>

                <a href="{{ route('gestor.escalas.nova') }}" wire:navigate>
                    <x-primary-button type="button">Nova escala</x-primary-button>
                </a>
            </div>

            @if (session('escala-criada'))
                <div class="bg-green-50 text-green-800 text-sm rounded-lg p-4">{{ session('escala-criada') }}</div>
            @endif

            <div class="bg-white shadow-sm sm:rounded-lg overflow-hidden">
                @forelse ($schedules as $schedule)
                    <div class="flex items-center justify-between p-6 {{ ! $loop->last ? 'border-b border-gray-100' : '' }}">
                        <div>
                            <p class="font-medium text-gray-900">
                                {{ $schedule->board?->name ?? $schedule->hospital->name }} — {{ $schedule->monthLabel() }}
                                <span class="ml-2 text-xs rounded px-2 py-0.5
                                    {{ match ($schedule->status->value) {
                                        'rascunho' => 'text-amber-700 bg-amber-50',
                                        'publicada' => 'text-green-700 bg-green-50',
                                        default => 'text-gray-600 bg-gray-100',
                                    } }}">
                                    {{ $schedule->status->label() }}
                                </span>
                            </p>
                            <p class="text-sm text-gray-500">
                                {{ $schedule->shifts_count }} {{ $schedule->shifts_count === 1 ? 'plantão' : 'plantões' }}
                                · {{ $schedule->unassigned_shifts_count }} sem médico
                                · v{{ $schedule->version }}
                            </p>
                        </div>
                        <a href="{{ $schedule->board !== null ? route('gestor.escala', $schedule) : route('gestor.escala.montar', $schedule) }}" wire:navigate>
                            <x-primary-button type="button">Abrir</x-primary-button>
                        </a>
                    </div>
                @empty
                    <p class="p-6 text-gray-500">Nenhuma escala ainda. Crie a primeira em "Nova escala".</p>
                @endforelse
            </div>
        </div>
    </div>
</div>
