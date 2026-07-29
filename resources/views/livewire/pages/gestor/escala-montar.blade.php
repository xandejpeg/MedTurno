<?php

use App\Enums\Role;
use App\Enums\ScheduleStatus;
use App\Enums\ShiftStatus;
use App\Models\Schedule;
use App\Services\ScheduleService;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;
use Livewire\Attributes\Layout;
use Livewire\Volt\Component;

new #[Layout('layouts.app')] class extends Component
{
    public Schedule $schedule;

    public string $replicaMonth = '';

    public function mount(Schedule $schedule): void
    {
        abort_unless(auth()->user()->isGestorOf($schedule->hospital), 403);

        $this->schedule = $schedule;
        $this->replicaMonth = Carbon::create($schedule->year, $schedule->month, 1)->addMonth()->format('Y-m');
    }

    public function assign(int $shiftId, int $userId): void
    {
        $shift = $this->schedule->shifts()->whereKey($shiftId)->firstOrFail();

        $isDoctor = $this->schedule->hospital->memberships()
            ->where('user_id', $userId)
            ->where('role', Role::Medico->value)
            ->where('active', true)
            ->exists();

        abort_unless($isDoctor, 422);

        $shift->update([
            'user_id' => $userId,
            'status' => ShiftStatus::Confirmado,
            'confirmed_at' => now(),
        ]);
    }

    public function unassign(int $shiftId): void
    {
        $shift = $this->schedule->shifts()->whereKey($shiftId)->firstOrFail();

        $shift->update([
            'user_id' => null,
            'status' => ShiftStatus::SemMedico,
            'confirmed_at' => null,
        ]);
    }

    public function publish(ScheduleService $service): void
    {
        $service->publish($this->schedule);

        session()->flash('status', 'Escala publicada. Os avisos aos médicos serão processados em segundo plano.');

        $this->redirect(route('gestor.hospital', $this->schedule->hospital), navigate: true);
    }

    public function replicate(ScheduleService $service): void
    {
        $this->validate([
            'replicaMonth' => ['required', 'date_format:Y-m'],
        ], attributes: ['replicaMonth' => 'mês de destino']);

        [$year, $month] = array_map('intval', explode('-', $this->replicaMonth));

        try {
            $target = $service->replicateToMonth($this->schedule, $year, $month, auth()->user());
        } catch (\InvalidArgumentException $exception) {
            $this->addError('replicaMonth', $exception->getMessage());

            return;
        }

        session()->flash('status', 'Escala replicada. Confira o novo mês antes de publicar.');
        $this->redirect(route('gestor.escala.montar', $target), navigate: true);
    }

    /**
     * @return array<string, mixed>
     */
    public function with(): array
    {
        $shifts = $this->schedule->shifts()->with('doctor')->get()
            ->groupBy(fn ($s) => $s->date->toDateString());

        $start = Carbon::create($this->schedule->year, $this->schedule->month, 1);
        $end = $start->copy()->endOfMonth();

        $days = [];
        $cursor = $start->copy();

        while ($cursor->lte($end)) {
            /** @var Collection<int, \App\Models\Shift> $dayShifts */
            $dayShifts = $shifts->get($cursor->toDateString(), collect());

            $days[] = [
                'date' => $cursor->copy(),
                'dia' => $dayShifts->firstWhere('period', 'dia'),
                'noite' => $dayShifts->firstWhere('period', 'noite'),
            ];

            $cursor->addDay();
        }

        $doctors = $this->schedule->hospital->memberships()
            ->where('role', Role::Medico->value)
            ->where('active', true)
            ->with('user')
            ->get()
            ->pluck('user')
            ->filter()
            ->sortBy('name')
            ->values();

        $total = $this->schedule->shifts()->count();
        $preenchidos = $this->schedule->shifts()->whereNotNull('user_id')->count();

        return [
            'days' => $days,
            'doctors' => $doctors,
            'leadingBlanks' => (int) $start->dayOfWeek,
            'monthLabel' => ucfirst($start->translatedFormat('F \d\e Y')),
            'isPublished' => $this->schedule->status === ScheduleStatus::Publicada,
            'total' => $total,
            'preenchidos' => $preenchidos,
        ];
    }
}; ?>

<div x-data="{ dragging: null }">
    <x-slot name="header">
        <div class="flex items-center justify-between gap-4">
            <div class="flex items-center gap-3">
                <a href="{{ route('gestor.hospital', $schedule->hospital) }}" wire:navigate class="text-gray-400 hover:text-gray-600" title="Voltar">
                    <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7"/></svg>
                </a>
                <div>
                    <h2 class="font-semibold text-xl text-gray-800 leading-tight">{{ $monthLabel }}</h2>
                    <p class="text-sm text-gray-500">{{ $schedule->hospital->name }}</p>
                </div>
            </div>
            <div class="flex flex-wrap items-end justify-end gap-2">
                @if ($isPublished)
                    <span class="inline-flex items-center gap-1 rounded-full bg-teal-100 text-teal-700 px-3 py-1 text-sm font-medium">Publicada</span>
                @endif
                <div>
                    <label for="replicaMonth" class="block text-xs text-gray-500 mb-1">Replicar para</label>
                    <input wire:model="replicaMonth" id="replicaMonth" type="month" class="rounded-md border-gray-300 text-sm focus:border-teal-500 focus:ring-teal-500">
                </div>
                <x-secondary-button wire:click="replicate" wire:confirm="Criar um novo rascunho repetindo esta escala no mês escolhido?">
                    Replicar
                </x-secondary-button>
                <x-primary-button wire:click="publish" wire:confirm="Publicar a escala e avisar os médicos com plantão?">
                    {{ $isPublished ? 'Republicar' : 'Publicar escala' }}
                </x-primary-button>
            </div>
        </div>
    </x-slot>

    <div class="py-6">
        <div class="max-w-[1400px] mx-auto sm:px-6 lg:px-8">
            @if (session('status'))
                <div class="mb-4 rounded-lg bg-teal-50 text-teal-800 px-4 py-3 text-sm">{{ session('status') }}</div>
            @endif

            <x-input-error :messages="$errors->get('replicaMonth')" class="mb-4" />

            <div class="mb-4 flex items-center gap-3">
                <div class="flex-1 h-2 rounded-full bg-gray-100 overflow-hidden">
                    <div class="h-full bg-teal-500 transition-all" style="width: {{ $total > 0 ? round($preenchidos / $total * 100) : 0 }}%"></div>
                </div>
                <span class="text-sm text-gray-500 whitespace-nowrap">{{ $preenchidos }}/{{ $total }} preenchidos</span>
            </div>

            <div class="grid grid-cols-1 lg:grid-cols-[1fr_260px] gap-6">
                <!-- Calendário -->
                <div class="bg-white shadow-sm sm:rounded-lg p-3 sm:p-5 overflow-x-auto">
                    <div class="grid grid-cols-7 gap-1.5 min-w-[720px]">
                        @foreach (['Dom','Seg','Ter','Qua','Qui','Sex','Sáb'] as $wd)
                            <div class="text-center text-xs font-semibold text-gray-400 pb-1">{{ $wd }}</div>
                        @endforeach

                        @for ($i = 0; $i < $leadingBlanks; $i++)
                            <div></div>
                        @endfor

                        @foreach ($days as $day)
                            <div class="rounded-lg border border-gray-100 bg-gray-50/50 p-1 flex flex-col gap-1 min-h-[112px]">
                                <div class="text-xs font-semibold text-gray-500 text-right pr-1">{{ $day['date']->day }}</div>

                                @foreach ([
                                    'dia' => [
                                        'hours' => '07–19h',
                                        'badge' => 'bg-amber-100 text-amber-700',
                                        'filledBorder' => 'border-amber-200',
                                        'dash' => 'border-amber-300 bg-amber-50/40 text-amber-600',
                                        'plus' => 'bg-amber-100 hover:bg-amber-200 text-amber-700',
                                    ],
                                    'noite' => [
                                        'hours' => '19–07h',
                                        'badge' => 'bg-indigo-100 text-indigo-700',
                                        'filledBorder' => 'border-indigo-200',
                                        'dash' => 'border-indigo-300 bg-indigo-50/40 text-indigo-600',
                                        'plus' => 'bg-indigo-100 hover:bg-indigo-200 text-indigo-700',
                                    ],
                                ] as $period => $c)
                                    @php $shift = $day[$period]; @endphp
                                    @if ($shift)
                                        @if ($shift->doctor)
                                            <div class="group relative rounded-md bg-white border {{ $c['filledBorder'] }} px-1.5 py-1 text-xs shadow-sm">
                                                <div class="flex items-center gap-1">
                                                    <span class="inline-flex h-5 w-5 shrink-0 items-center justify-center rounded-full {{ $c['badge'] }} text-[9px] font-bold">
                                                        {{ \Illuminate\Support\Str::of($shift->doctor->name)->explode(' ')->take(2)->map(fn ($p) => \Illuminate\Support\Str::substr($p, 0, 1))->implode('') }}
                                                    </span>
                                                    <span class="truncate font-medium text-gray-700" title="{{ $shift->doctor->name }}">{{ \Illuminate\Support\Str::of($shift->doctor->name)->explode(' ')->first() }}</span>
                                                </div>
                                                @unless ($isPublished)
                                                    <button wire:click="unassign({{ $shift->id }})" class="absolute -top-1.5 -right-1.5 hidden group-hover:flex h-4 w-4 items-center justify-center rounded-full bg-red-500 text-white text-[10px] leading-none" title="Remover">×</button>
                                                @endunless
                                            </div>
                                        @else
                                            <div x-data="{ open: false }"
                                                 @dragover.prevent
                                                 @drop.prevent="if (dragging) { $wire.assign({{ $shift->id }}, dragging); dragging = null }"
                                                 class="relative rounded-md border border-dashed {{ $c['dash'] }} px-1 py-1 text-[10px]">
                                                <div class="flex items-center justify-between">
                                                    <span>{{ $c['hours'] }}</span>
                                                    @unless ($isPublished)
                                                        <button @click="open = !open" class="h-4 w-4 rounded-full {{ $c['plus'] }} font-bold leading-none">+</button>
                                                    @endunless
                                                </div>
                                                <div x-show="open" x-cloak @click.outside="open = false" class="absolute z-20 top-full left-0 mt-1 w-40 max-h-48 overflow-y-auto rounded-md bg-white shadow-lg ring-1 ring-black/5 py-1">
                                                    @forelse ($doctors as $doctor)
                                                        <button wire:click="assign({{ $shift->id }}, {{ $doctor->id }})" @click="open = false" class="block w-full text-left px-2 py-1 text-xs text-gray-700 hover:bg-gray-100 truncate">{{ $doctor->name }}</button>
                                                    @empty
                                                        <span class="block px-2 py-1 text-xs text-gray-400">Sem médicos</span>
                                                    @endforelse
                                                </div>
                                            </div>
                                        @endif
                                    @endif
                                @endforeach
                            </div>
                        @endforeach
                    </div>
                </div>

                <!-- Lista de médicos -->
                <div class="lg:sticky lg:top-6 self-start">
                    <div class="bg-white shadow-sm sm:rounded-lg p-4">
                        <h3 class="text-sm font-semibold text-gray-700 mb-1">Médicos</h3>
                        <p class="text-xs text-gray-400 mb-3">Arraste para um plantão, ou use o “+”.</p>
                        <div class="space-y-1.5 max-h-[70vh] overflow-y-auto">
                            @forelse ($doctors as $doctor)
                                <div draggable="true"
                                     @dragstart="dragging = {{ $doctor->id }}"
                                     @dragend="dragging = null"
                                     class="flex items-center gap-2 rounded-lg border border-gray-100 bg-gray-50 px-2 py-1.5 cursor-grab active:cursor-grabbing hover:border-teal-300 hover:bg-teal-50 transition">
                                    <span class="inline-flex h-7 w-7 shrink-0 items-center justify-center rounded-full bg-teal-100 text-teal-700 text-xs font-bold">
                                        {{ \Illuminate\Support\Str::of($doctor->name)->explode(' ')->take(2)->map(fn ($p) => \Illuminate\Support\Str::substr($p, 0, 1))->implode('') }}
                                    </span>
                                    <span class="text-sm text-gray-700 truncate">{{ $doctor->name }}</span>
                                </div>
                            @empty
                                <p class="text-sm text-gray-400">Nenhum médico neste hospital ainda. Convide médicos primeiro.</p>
                            @endforelse
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
