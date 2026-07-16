<?php

use App\Enums\ScheduleStatus;
use App\Enums\ShiftStatus;
use App\Models\Shift;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Url;
use Livewire\Volt\Component;

new #[Layout('layouts.app')] class extends Component
{
    #[Url]
    public string $month = '';

    public function mount(): void
    {
        abort_unless(currentHospital() !== null, 403);

        if ($this->month === '' || ! preg_match('/^\d{4}-\d{2}$/', $this->month)) {
            $this->month = now()->format('Y-m');
        }
    }

    public function previousMonth(): void
    {
        $this->month = \Illuminate\Support\Carbon::parse($this->month.'-01')->subMonth()->format('Y-m');
    }

    public function nextMonth(): void
    {
        $this->month = \Illuminate\Support\Carbon::parse($this->month.'-01')->addMonth()->format('Y-m');
    }

    /**
     * @return array<string, mixed>
     */
    public function with(): array
    {
        $hospital = currentHospital();
        $start = \Illuminate\Support\Carbon::parse($this->month.'-01')->startOfMonth();
        $end = $start->copy()->endOfMonth();

        $shifts = Shift::query()
            ->where('hospital_id', $hospital->id)
            ->whereNotNull('user_id')
            ->whereBetween('date', [$start, $end])
            ->whereHas('schedule', fn ($q) => $q->where('status', ScheduleStatus::Publicada))
            ->with(['doctor', 'schedule.board'])
            ->orderBy('date')
            ->get();

        $payable = [ShiftStatus::Confirmado, ShiftStatus::Concluido];

        $doctors = $shifts->groupBy('user_id')->map(function ($group) use ($payable) {
            $payableShifts = $group->filter(fn ($s) => in_array($s->status, $payable, true));

            return [
                'doctor' => $group->first()->doctor,
                'shifts' => $group->sortBy('date'),
                'total' => $payableShifts->sum(fn ($s) => (float) $s->amount),
                'payableCount' => $payableShifts->count(),
                'excludedCount' => $group->filter(fn ($s) => in_array($s->status, [ShiftStatus::Cancelado, ShiftStatus::NaoCumprido], true))->count(),
            ];
        })->sortBy(fn ($row) => $row['doctor']->name)->values();

        return [
            'doctors' => $doctors,
            'grandTotal' => $doctors->sum('total'),
            'monthLabel' => $start->translatedFormat('F/Y'),
            'payable' => $payable,
        ];
    }
}; ?>

<div>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 leading-tight">Faturamento — {{ currentHospital()?->name }}</h2>
    </x-slot>

    <div class="py-12">
        <div class="max-w-4xl mx-auto sm:px-6 lg:px-8 space-y-6">

            <div class="flex items-center justify-between">
                <x-secondary-button wire:click="previousMonth" type="button">← Anterior</x-secondary-button>
                <h3 class="text-lg font-semibold text-gray-900 capitalize">{{ $monthLabel }}</h3>
                <x-secondary-button wire:click="nextMonth" type="button">Próximo →</x-secondary-button>
            </div>

            <div class="bg-white shadow-sm sm:rounded-lg p-6">
                <p class="text-sm text-gray-500 mb-1">Total geral do mês (confirmados e concluídos)</p>
                <p class="text-3xl font-semibold text-gray-900">R$ {{ number_format($grandTotal, 2, ',', '.') }}</p>
            </div>

            <div class="bg-white shadow-sm sm:rounded-lg overflow-hidden">
                <div class="p-6 border-b border-gray-100">
                    <h3 class="font-medium text-gray-900">Por médico</h3>
                </div>
                @forelse ($doctors as $row)
                    <details class="border-b border-gray-100 last:border-b-0">
                        <summary class="p-6 cursor-pointer flex flex-wrap items-center justify-between gap-4 hover:bg-gray-50">
                            <div>
                                <p class="font-medium text-gray-900">{{ $row['doctor']->name }}</p>
                                <p class="text-sm text-gray-500">
                                    {{ $row['payableCount'] }} plantão(ões) a pagar
                                    @if ($row['excludedCount'] > 0)
                                        · {{ $row['excludedCount'] }} cancelado(s)/não cumprido(s)
                                    @endif
                                </p>
                            </div>
                            <p class="text-lg font-semibold text-gray-900">R$ {{ number_format($row['total'], 2, ',', '.') }}</p>
                        </summary>
                        <div class="px-6 pb-6 space-y-2">
                            @foreach ($row['shifts'] as $shift)
                                <div class="flex flex-wrap items-center justify-between gap-2 text-sm bg-gray-50 rounded px-4 py-2">
                                    <span class="text-gray-700">
                                        {{ $shift->date->format('d/m') }} · {{ $shift->starts_at->format('H:i') }}–{{ $shift->ends_at->format('H:i') }}
                                        · {{ $shift->schedule->board->name }}
                                    </span>
                                    <span class="flex items-center gap-3">
                                        <span class="text-xs px-2 py-0.5 rounded-full
                                            @if (in_array($shift->status, $payable, true)) bg-green-100 text-green-800
                                            @elseif (in_array($shift->status, [ShiftStatus::Cancelado, ShiftStatus::NaoCumprido], true)) bg-red-100 text-red-800
                                            @else bg-amber-100 text-amber-800 @endif">
                                            {{ $shift->status->label() }}
                                        </span>
                                        <span class="font-medium text-gray-900 {{ ! in_array($shift->status, $payable, true) ? 'line-through text-gray-400' : '' }}">
                                            R$ {{ number_format((float) $shift->amount, 2, ',', '.') }}
                                        </span>
                                    </span>
                                </div>
                            @endforeach
                        </div>
                    </details>
                @empty
                    <p class="p-6 text-gray-500">Nenhum plantão atribuído neste mês.</p>
                @endforelse
            </div>
        </div>
    </div>
</div>
