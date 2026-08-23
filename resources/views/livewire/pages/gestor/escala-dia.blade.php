<?php

use App\Enums\ShiftStatus;
use App\Models\Shift;
use Illuminate\Support\Carbon;
use Livewire\Attributes\Layout;
use Livewire\Volt\Component;

new #[Layout('layouts.app')] class extends Component
{
    public string $date = '';

    public string $unitId = '';

    public function mount(): void
    {
        abort_unless(auth()->user()->isGestor(), 403);
        $this->date = now()->toDateString();
    }

    /**
     * @return array<string, mixed>
     */
    public function with(): array
    {
        $hospital = currentHospital();

        $units = $hospital?->units()->orderBy('name')->get() ?? collect();

        $shifts = Shift::with(['doctor', 'unit'])
            ->where('hospital_id', $hospital?->id)
            ->whereDate('date', $this->date)
            ->when($this->unitId !== '', fn ($q) => $q->where('unit_id', (int) $this->unitId))
            ->orderBy('starts_at')
            ->get();

        $grouped = $shifts->groupBy(fn ($s) => $s->unit?->name ?? 'Sem unidade');

        return [
            'hospital' => $hospital,
            'units' => $units,
            'grouped' => $grouped,
            'dateLabel' => Carbon::parse($this->date)->translatedFormat('d/m/Y (l)'),
            'totalWorking' => $shifts->whereNotNull('user_id')->count(),
        ];
    }
}; ?>

<div>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 leading-tight">Escala do dia</h2>
        <p class="text-sm text-gray-500">{{ $hospital?->name }}</p>
    </x-slot>

    <div class="py-6">
        <div class="max-w-5xl mx-auto sm:px-6 lg:px-8 space-y-6">
            <div class="bg-white shadow-sm sm:rounded-lg p-4 flex flex-wrap items-end gap-4">
                <div>
                    <x-input-label for="date" value="Data" />
                    <x-text-input wire:model.live="date" id="date" type="date" class="mt-1 block w-full" />
                </div>
                <div>
                    <x-input-label for="unitId" value="Unidade (UBS)" />
                    <select wire:model.live="unitId" id="unitId" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-teal-500 focus:ring-teal-500">
                        <option value="">Todas</option>
                        @foreach ($units as $unit)
                            <option value="{{ $unit->id }}">{{ $unit->name }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="ml-auto text-sm text-gray-600">
                    <span class="font-semibold text-teal-700">{{ $totalWorking }}</span> médico(s) trabalhando
                </div>
            </div>

            <p class="text-sm text-gray-500 capitalize">{{ $dateLabel }}</p>

            @forelse ($grouped as $unitName => $unitShifts)
                <div class="bg-white shadow-sm sm:rounded-lg">
                    <p class="border-b border-gray-100 px-6 py-3 text-sm font-semibold text-gray-800">{{ $unitName }}</p>
                    <ul class="divide-y divide-gray-50">
                        @foreach ($unitShifts as $shift)
                            <li class="flex items-center justify-between gap-3 px-6 py-3">
                                <div class="min-w-0">
                                    <p class="text-sm font-medium text-gray-900">{{ $shift->doctor?->name ?? 'Sem médico' }}</p>
                                    <p class="text-xs text-gray-500">{{ $shift->starts_at->format('H:i') }}–{{ $shift->ends_at->format('H:i') }}</p>
                                </div>
                                <div class="flex items-center gap-3">
                                    @if ($shift->doctor?->phone)
                                        <a href="tel:{{ $shift->doctor->phone }}" class="text-xs text-teal-600 hover:underline">{{ $shift->doctor->phone }}</a>
                                    @endif
                                    <span class="inline-flex rounded-full px-2 py-0.5 text-xs font-medium {{ $shift->user_id ? 'bg-green-100 text-green-700' : 'bg-gray-100 text-gray-500' }}">
                                        {{ $shift->status->label() }}
                                    </span>
                                </div>
                            </li>
                        @endforeach
                    </ul>
                </div>
            @empty
                <div class="bg-white shadow-sm sm:rounded-lg p-8 text-center text-sm text-gray-400">Nenhum plantão nesta data.</div>
            @endforelse
        </div>
    </div>
</div>
