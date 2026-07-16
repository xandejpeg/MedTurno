<?php

use App\Enums\ScheduleStatus;
use App\Enums\ShiftStatus;
use App\Models\Hospital;
use App\Models\Shift;
use Illuminate\Support\Carbon;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Url;
use Livewire\Volt\Component;

new #[Layout('layouts.app')] class extends Component
{
    #[Url]
    public string $month = '';

    #[Url]
    public string $hospitalFilter = '';

    public function mount(): void
    {
        if ($this->month === '' || ! preg_match('/^\d{4}-\d{2}$/', $this->month)) {
            $this->month = now()->format('Y-m');
        }
    }

    public function previousMonth(): void
    {
        $this->month = Carbon::createFromFormat('Y-m', $this->month)->subMonth()->format('Y-m');
    }

    public function nextMonth(): void
    {
        $this->month = Carbon::createFromFormat('Y-m', $this->month)->addMonth()->format('Y-m');
    }

    /**
     * @return array<string, mixed>
     */
    public function with(): array
    {
        $user = auth()->user();
        $firstDay = Carbon::createFromFormat('Y-m', $this->month)->startOfMonth();

        $hospitals = Hospital::query()
            ->whereHas('memberships', fn ($q) => $q->where('user_id', $user->id)->where('active', true))
            ->orderBy('name')
            ->get();

        $shifts = Shift::query()
            ->where('user_id', $user->id)
            ->whereHas('schedule', fn ($q) => $q->where('status', ScheduleStatus::Publicada))
            ->whereYear('date', $firstDay->year)
            ->whereMonth('date', $firstDay->month)
            ->when($this->hospitalFilter !== '', fn ($q) => $q->where('hospital_id', (int) $this->hospitalFilter))
            ->with(['hospital'])
            ->orderBy('starts_at')
            ->get();

        $shiftsByDate = $shifts->groupBy(fn (Shift $s) => $s->date->toDateString());

        $days = collect(range(1, $firstDay->daysInMonth))
            ->map(fn (int $d) => $firstDay->copy()->day($d));

        $cells = collect(array_fill(0, $firstDay->dayOfWeek, null))
            ->concat($days);
        $cells = $cells->concat(array_fill(0, (7 - $cells->count() % 7) % 7, null));

        return [
            'firstDay' => $firstDay,
            'weeks' => $cells->chunk(7),
            'shiftsByDate' => $shiftsByDate,
            'hospitals' => $hospitals,
            'shifts' => $shifts,
        ];
    }
}; ?>

<div>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 leading-tight">Minha escala</h2>
    </x-slot>

    <div class="py-12 pb-24 sm:pb-12">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8 space-y-6">

            <div class="bg-white shadow-sm sm:rounded-lg p-4 flex flex-wrap items-center gap-4">
                <div class="flex items-center gap-2">
                    <button wire:click="previousMonth" type="button" class="px-3 py-1 rounded border border-gray-300 text-gray-600 hover:bg-gray-50">‹</button>
                    <span class="font-medium text-gray-900 w-24 text-center">{{ $firstDay->format('m/Y') }}</span>
                    <button wire:click="nextMonth" type="button" class="px-3 py-1 rounded border border-gray-300 text-gray-600 hover:bg-gray-50">›</button>
                </div>

                @if ($hospitals->count() > 1)
                    <select wire:model.live="hospitalFilter" class="border-gray-300 rounded-md shadow-sm text-sm">
                        <option value="">Todos os hospitais</option>
                        @foreach ($hospitals as $hospital)
                            <option value="{{ $hospital->id }}">{{ $hospital->name }}</option>
                        @endforeach
                    </select>
                @endif

                <div class="flex items-center gap-3 text-xs text-gray-500 ml-auto">
                    <span class="flex items-center gap-1"><span class="w-3 h-3 rounded bg-amber-400"></span> Pendente</span>
                    <span class="flex items-center gap-1"><span class="w-3 h-3 rounded bg-green-500"></span> Confirmado</span>
                    <span class="flex items-center gap-1"><span class="w-3 h-3 rounded bg-blue-500"></span> Em troca</span>
                    <span class="flex items-center gap-1"><span class="w-3 h-3 rounded bg-red-500"></span> No mural</span>
                </div>
            </div>

            {{-- Calendário (desktop) --}}
            <div class="hidden sm:block bg-white shadow-sm sm:rounded-lg p-4">
                <div class="grid grid-cols-7 gap-1 text-center text-xs font-medium text-gray-500 mb-1">
                    @foreach (['Dom', 'Seg', 'Ter', 'Qua', 'Qui', 'Sex', 'Sáb'] as $dow)
                        <div class="py-1">{{ $dow }}</div>
                    @endforeach
                </div>
                @foreach ($weeks as $week)
                    <div class="grid grid-cols-7 gap-1 mb-1">
                        @foreach ($week as $day)
                            <div class="min-h-20 rounded border {{ $day === null ? 'bg-gray-50 border-transparent' : 'border-gray-200' }} p-1">
                                @if ($day !== null)
                                    <p class="text-xs text-gray-400">{{ $day->day }}</p>
                                    @foreach ($shiftsByDate->get($day->toDateString(), collect()) as $shift)
                                        <a href="{{ route('medico.plantao', $shift) }}" wire:navigate
                                           class="block mt-1 px-1 py-0.5 rounded text-[11px] leading-tight text-white truncate
                                               @if ($shift->status === ShiftStatus::Confirmado) bg-green-500
                                               @elseif ($shift->status === ShiftStatus::Pendente) bg-amber-400
                                               @elseif ($shift->status === ShiftStatus::EmTroca) bg-blue-500
                                               @elseif ($shift->status === ShiftStatus::Disponivel) bg-red-500
                                               @else bg-gray-400 @endif">
                                            {{ $shift->starts_at->format('H:i') }} {{ $shift->hospital->name }}
                                        </a>
                                    @endforeach
                                @endif
                            </div>
                        @endforeach
                    </div>
                @endforeach
            </div>

            {{-- Lista (mobile) --}}
            <div class="sm:hidden space-y-2">
                @forelse ($shifts as $shift)
                    <a href="{{ route('medico.plantao', $shift) }}" wire:navigate class="block bg-white shadow-sm rounded-lg p-4">
                        <div class="flex items-center justify-between">
                            <div>
                                <p class="font-medium text-gray-900">
                                    {{ $shift->date->format('d/m') }} · {{ $shift->starts_at->format('H:i') }}–{{ $shift->ends_at->format('H:i') }}
                                </p>
                                <p class="text-sm text-gray-500">{{ $shift->hospital->name }}</p>
                            </div>
                            <span class="text-xs px-2 py-1 rounded-full
                                @if ($shift->status === ShiftStatus::Confirmado) bg-green-100 text-green-800
                                @elseif ($shift->status === ShiftStatus::Pendente) bg-amber-100 text-amber-800
                                @elseif ($shift->status === ShiftStatus::EmTroca) bg-blue-100 text-blue-800
                                @else bg-gray-100 text-gray-700 @endif">
                                {{ $shift->status->label() }}
                            </span>
                        </div>
                    </a>
                @empty
                    <p class="bg-white shadow-sm rounded-lg p-6 text-gray-500">Nenhum plantão em {{ $firstDay->format('m/Y') }}.</p>
                @endforelse
            </div>
        </div>
    </div>
</div>
