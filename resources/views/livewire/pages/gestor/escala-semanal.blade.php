<?php

use App\Models\Shift;
use Illuminate\Support\Carbon;
use Livewire\Attributes\Layout;
use Livewire\Volt\Component;

new #[Layout('layouts.app')] class extends Component
{
    public string $weekStart = '';

    public function mount(): void
    {
        abort_unless(auth()->user()->isGestor() || auth()->user()->isGestorMunicipal(), 403);
        $this->weekStart = now()->startOfWeek()->toDateString();
    }

    public function previousWeek(): void
    {
        $this->weekStart = Carbon::parse($this->weekStart)->subWeek()->toDateString();
    }

    public function nextWeek(): void
    {
        $this->weekStart = Carbon::parse($this->weekStart)->addWeek()->toDateString();
    }

    /**
     * @return array<string, mixed>
     */
    public function with(): array
    {
        $hospital = currentHospital();
        $start = Carbon::parse($this->weekStart);
        $end = $start->copy()->endOfWeek();

        $shifts = Shift::with(['doctor', 'unit'])
            ->where('hospital_id', $hospital?->id)
            ->whereBetween('date', [$start->toDateString(), $end->toDateString()])
            ->orderBy('date')
            ->orderBy('starts_at')
            ->get()
            ->groupBy(fn ($s) => $s->date->toDateString());

        $days = [];
        $cursor = $start->copy();
        while ($cursor->lte($end)) {
            $days[] = [
                'date' => $cursor->copy(),
                'shifts' => $shifts->get($cursor->toDateString(), collect()),
            ];
            $cursor->addDay();
        }

        return [
            'hospital' => $hospital,
            'days' => $days,
            'weekLabel' => $start->format('d/m').' a '.$end->format('d/m/Y'),
        ];
    }
}; ?>

<div>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 leading-tight">Escala semanal</h2>
        <p class="text-sm text-gray-500">{{ $hospital?->name }}</p>
    </x-slot>

    <div class="py-6">
        <div class="max-w-6xl mx-auto sm:px-6 lg:px-8 space-y-6">
            <div class="bg-white shadow-sm sm:rounded-lg p-4 flex items-center justify-between">
                <button wire:click="previousWeek" type="button" class="px-3 py-1 rounded border border-gray-300 text-gray-600 hover:bg-gray-50">‹ Semana anterior</button>
                <span class="font-medium text-gray-900">{{ $weekLabel }}</span>
                <button wire:click="nextWeek" type="button" class="px-3 py-1 rounded border border-gray-300 text-gray-600 hover:bg-gray-50">Próxima semana ›</button>
            </div>

            <div class="grid grid-cols-1 sm:grid-cols-7 gap-2">
                @foreach ($days as $day)
                    <div class="bg-white shadow-sm rounded-lg border border-gray-100 p-2 min-h-[140px]">
                        <p class="text-xs font-semibold text-gray-500 capitalize">{{ $day['date']->translatedFormat('D d/m') }}</p>
                        <div class="mt-2 space-y-1">
                            @forelse ($day['shifts'] as $shift)
                                <div class="rounded-md border px-1.5 py-1 text-[10px] leading-tight {{ $shift->doctor ? 'border-teal-200 bg-teal-50 text-teal-800' : 'border-dashed border-gray-300 text-gray-400' }}">
                                    <span class="font-semibold">{{ $shift->starts_at->format('H:i') }}</span>
                                    <span class="block truncate">{{ $shift->doctor?->name ?? 'Sem médico' }}</span>
                                    @if ($shift->unit)
                                        <span class="block truncate text-gray-400">{{ $shift->unit->name }}</span>
                                    @endif
                                </div>
                            @empty
                                <p class="text-[10px] text-gray-300">—</p>
                            @endforelse
                        </div>
                    </div>
                @endforeach
            </div>
        </div>
    </div>
</div>
