<?php

use App\Enums\ScheduleStatus;
use App\Enums\ShiftStatus;
use App\Models\Schedule;
use App\Models\Shift;
use App\Services\ScheduleService;
use App\Services\ShiftService;
use Illuminate\Support\Carbon;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Locked;
use Livewire\Volt\Component;

new #[Layout('layouts.app')] class extends Component
{
    #[Locked]
    public int $scheduleId;

    public ?int $editingShiftId = null;

    public string $doctorId = '';

    public string $note = '';

    public string $amount = '';

    public function mount(Schedule $schedule): void
    {
        $this->authorize('update', $schedule->hospital);
        $this->scheduleId = $schedule->id;
    }

    public function schedule(): Schedule
    {
        $schedule = Schedule::with(['hospital', 'board'])->findOrFail($this->scheduleId);
        $this->authorize('update', $schedule->hospital);

        return $schedule;
    }

    /**
     * @return array<string, mixed>
     */
    public function with(): array
    {
        $schedule = $this->schedule();

        $shiftsByDate = $schedule->shifts()
            ->with(['doctor', 'template'])
            ->orderBy('starts_at')
            ->get()
            ->groupBy(fn (Shift $s) => $s->date->toDateString());

        $firstDay = Carbon::create($schedule->year, $schedule->month, 1);
        $days = collect(range(1, $firstDay->daysInMonth))
            ->map(fn (int $d) => $firstDay->copy()->day($d));

        $cells = collect(array_fill(0, $firstDay->dayOfWeek, null))
            ->concat($days);
        $cells = $cells->concat(array_fill(0, (7 - $cells->count() % 7) % 7, null));

        $editingShift = $this->editingShiftId !== null
            ? $schedule->shifts()->with(['doctor', 'template'])->whereKey($this->editingShiftId)->first()
            : null;

        $conflicts = collect();
        if ($editingShift !== null && $this->doctorId !== '' && (int) $this->doctorId !== $editingShift->user_id) {
            $doctor = $schedule->board->doctors()->whereKey((int) $this->doctorId)->first();
            if ($doctor !== null) {
                $conflicts = app(ShiftService::class)->conflictsFor(
                    $doctor, $editingShift->starts_at, $editingShift->ends_at, $editingShift->id
                );
            }
        }

        return [
            'schedule' => $schedule,
            'weeks' => $cells->chunk(7),
            'shiftsByDate' => $shiftsByDate,
            'boardDoctors' => $schedule->board->doctors()->orderBy('name')->get(),
            'editingShift' => $editingShift,
            'conflicts' => $conflicts,
            'unassignedCount' => $schedule->shifts()->whereNull('user_id')->count(),
        ];
    }

    public function openShift(int $shiftId): void
    {
        $shift = $this->schedule()->shifts()->whereKey($shiftId)->firstOrFail();

        $this->editingShiftId = $shift->id;
        $this->doctorId = $shift->user_id !== null ? (string) $shift->user_id : '';
        $this->note = $shift->note ?? '';
        $this->amount = $shift->amount !== null ? (string) $shift->amount : '';
        $this->resetValidation();
    }

    public function closeShift(): void
    {
        $this->reset(['editingShiftId', 'doctorId', 'note', 'amount']);
        $this->resetValidation();
    }

    public function saveShift(ShiftService $service): void
    {
        $this->validate(
            ['amount' => ['nullable', 'numeric', 'min:0', 'max:99999']],
            [], ['amount' => 'valor'],
        );

        $schedule = $this->schedule();
        $shift = $schedule->shifts()->whereKey($this->editingShiftId)->firstOrFail();

        if ($this->doctorId !== '') {
            $doctor = $schedule->board->doctors()->whereKey((int) $this->doctorId)->firstOrFail();

            if ($shift->user_id !== $doctor->id) {
                $service->assignDoctor($shift, $doctor);
            }
        } elseif ($shift->user_id !== null) {
            $service->unassignDoctor($shift);
        }

        $shift->update([
            'note' => $this->note !== '' ? $this->note : null,
            'amount' => $this->amount !== '' ? $this->amount : $shift->amount,
        ]);

        $this->closeShift();
    }

    public function removeDoctor(ShiftService $service): void
    {
        $shift = $this->schedule()->shifts()->whereKey($this->editingShiftId)->firstOrFail();
        $service->unassignDoctor($shift);
        $this->closeShift();
    }

    public function publish(ScheduleService $service): void
    {
        $service->publish($this->schedule());

        session()->flash('escala-publicada', 'Escala publicada! Os médicos foram avisados por email.');
    }
}; ?>

<div>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 leading-tight">Editor de escala</h2>
    </x-slot>

    <div class="py-12">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8 space-y-6">

            <div class="flex flex-wrap items-center justify-between gap-4">
                <div>
                    <a href="{{ route('gestor.escalas') }}" wire:navigate class="text-sm text-gray-500 hover:text-gray-700">← Escalas</a>
                    <h3 class="text-2xl font-semibold text-gray-900">
                        {{ $schedule->board->name }} — {{ $schedule->monthLabel() }}
                        <span class="ml-2 text-xs align-middle rounded px-2 py-0.5
                            {{ $schedule->status === \App\Enums\ScheduleStatus::Rascunho ? 'text-amber-700 bg-amber-50' : 'text-green-700 bg-green-50' }}">
                            {{ $schedule->status->label() }}@if ($schedule->version > 1) · v{{ $schedule->version }}@endif
                        </span>
                    </h3>
                    <p class="text-sm text-gray-500">
                        {{ $schedule->hospital->name }} · {{ $unassignedCount }} {{ $unassignedCount === 1 ? 'plantão sem médico' : 'plantões sem médico' }}
                    </p>
                </div>

                <x-primary-button wire:click="publish" wire:confirm="{{ $schedule->status === \App\Enums\ScheduleStatus::Publicada ? 'Publicar nova versão? Os médicos serão avisados de novo.' : 'Publicar a escala? Os médicos serão avisados por email.' }}">
                    {{ $schedule->status === \App\Enums\ScheduleStatus::Publicada ? 'Publicar nova versão' : 'Publicar' }}
                </x-primary-button>
            </div>

            @if (session('escala-publicada'))
                <div class="bg-green-50 text-green-800 text-sm rounded-lg p-4">{{ session('escala-publicada') }}</div>
            @endif

            <div class="flex items-center gap-3 text-xs text-gray-500">
                <span class="flex items-center gap-1"><span class="w-3 h-3 rounded bg-red-100 border border-red-300"></span> Vago</span>
                <span class="flex items-center gap-1"><span class="w-3 h-3 rounded bg-teal-100 border border-teal-300"></span> Atribuído</span>
                <span class="flex items-center gap-1"><span class="w-3 h-3 rounded bg-green-100 border border-green-300"></span> Confirmado</span>
            </div>

            {{-- MonthGrid (desktop) --}}
            <div class="hidden sm:block bg-white shadow-sm sm:rounded-lg overflow-hidden">
                <div class="grid grid-cols-7 border-b border-gray-200 bg-gray-50 text-xs font-medium text-gray-500">
                    @foreach (['Dom', 'Seg', 'Ter', 'Qua', 'Qui', 'Sex', 'Sáb'] as $dayName)
                        <div class="px-2 py-2 text-center">{{ $dayName }}</div>
                    @endforeach
                </div>
                @foreach ($weeks as $week)
                    <div class="grid grid-cols-7 {{ ! $loop->last ? 'border-b border-gray-100' : '' }}">
                        @foreach ($week as $day)
                            <div class="min-h-24 p-1.5 {{ ! $loop->last ? 'border-r border-gray-100' : '' }} {{ $day === null ? 'bg-gray-50' : '' }}">
                                @if ($day !== null)
                                    <p class="text-xs text-gray-400 mb-1">{{ $day->day }}</p>
                                    <div class="space-y-1">
                                        @foreach ($shiftsByDate->get($day->toDateString(), collect()) as $shift)
                                            <button wire:click="openShift({{ $shift->id }})"
                                                class="w-full text-left text-xs rounded px-1.5 py-1 truncate
                                                {{ $shift->user_id === null
                                                    ? 'bg-red-50 text-red-700 hover:bg-red-100'
                                                    : ($shift->status === \App\Enums\ShiftStatus::Confirmado
                                                        ? 'bg-green-50 text-green-700 hover:bg-green-100'
                                                        : 'bg-teal-50 text-teal-700 hover:bg-teal-100') }}">
                                                {{ $shift->starts_at->format('H:i') }}
                                                {{ $shift->doctor?->name ?? 'Vago' }}
                                            </button>
                                        @endforeach
                                    </div>
                                @endif
                            </div>
                        @endforeach
                    </div>
                @endforeach
            </div>

            {{-- DayList (mobile) --}}
            <div class="sm:hidden space-y-3">
                @foreach ($shiftsByDate as $dateString => $shifts)
                    <div class="bg-white shadow-sm rounded-lg p-4">
                        <p class="font-medium text-gray-900 mb-2">{{ \Illuminate\Support\Carbon::parse($dateString)->format('d/m') }} — {{ \App\Models\ShiftTemplate::WEEKDAYS[\Illuminate\Support\Carbon::parse($dateString)->dayOfWeek] }}</p>
                        <div class="space-y-1.5">
                            @foreach ($shifts as $shift)
                                <button wire:click="openShift({{ $shift->id }})"
                                    class="w-full text-left text-sm rounded px-3 py-2
                                    {{ $shift->user_id === null ? 'bg-red-50 text-red-700' : 'bg-teal-50 text-teal-700' }}">
                                    {{ $shift->starts_at->format('H:i') }}–{{ $shift->ends_at->format('H:i') }}
                                    · {{ $shift->doctor?->name ?? 'Vago' }}
                                </button>
                            @endforeach
                        </div>
                    </div>
                @endforeach
            </div>

            {{-- Modal editar plantão --}}
            @if ($editingShift !== null)
                <div class="fixed inset-0 z-50 flex items-center justify-center p-4">
                    <div class="fixed inset-0 bg-gray-900/50" wire:click="closeShift"></div>
                    <div class="relative bg-white rounded-lg shadow-xl w-full max-w-md p-6 space-y-4">
                        <div>
                            <h4 class="text-lg font-medium text-gray-900">
                                {{ $editingShift->date->format('d/m/Y') }} · {{ $editingShift->starts_at->format('H:i') }}–{{ $editingShift->ends_at->format('H:i') }}
                            </h4>
                            <p class="text-sm text-gray-500">
                                {{ $editingShift->template?->label ?? 'Plantão' }}
                                · {{ $editingShift->status->label() }}
                                @if ($editingShift->amount) · R$ {{ number_format((float) $editingShift->amount, 2, ',', '.') }} @endif
                            </p>
                        </div>

                        <div>
                            <x-input-label for="doctorId" value="Médico" />
                            <select wire:model.live="doctorId" id="doctorId" class="block mt-1 w-full border-gray-300 focus:border-teal-500 focus:ring-teal-500 rounded-md shadow-sm">
                                <option value="">— Sem médico —</option>
                                @foreach ($boardDoctors as $doctor)
                                    <option value="{{ $doctor->id }}">{{ $doctor->name }}</option>
                                @endforeach
                            </select>
                        </div>

                        @if ($conflicts->isNotEmpty())
                            <div class="bg-amber-50 text-amber-800 text-sm rounded-lg p-3">
                                <p class="font-medium">⚠ Conflito de horário:</p>
                                @foreach ($conflicts as $conflict)
                                    <p>{{ $conflict->starts_at->format('d/m H:i') }}–{{ $conflict->ends_at->format('H:i') }} em {{ $conflict->hospital->name }}</p>
                                @endforeach
                            </div>
                        @endif

                        <div>
                            <x-input-label for="note" value="Observação" />
                            <x-text-input wire:model="note" id="note" class="block mt-1 w-full" type="text" />
                        </div>

                        <div>
                            <x-input-label for="amount" value="Valor (R$)" />
                            <x-text-input wire:model="amount" id="amount" class="block mt-1 w-full" type="number" step="0.01" min="0" />
                            <x-input-error :messages="$errors->get('amount')" class="mt-2" />
                        </div>

                        <div class="flex justify-between">
                            <div>
                                @if ($editingShift->user_id !== null)
                                    <x-danger-button wire:click="removeDoctor">Tirar médico</x-danger-button>
                                @endif
                            </div>
                            <div class="flex gap-2">
                                <x-secondary-button wire:click="closeShift">Cancelar</x-secondary-button>
                                <x-primary-button wire:click="saveShift">Salvar</x-primary-button>
                            </div>
                        </div>
                    </div>
                </div>
            @endif
        </div>
    </div>
</div>
