<?php

use App\Enums\Role;
use App\Enums\ScheduleStatus;
use App\Enums\ShiftStatus;
use App\Models\Schedule;
use App\Services\ScheduleService;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Livewire\Attributes\Layout;
use Livewire\Volt\Component;

new #[Layout('layouts.app')] class extends Component
{
    public Schedule $schedule;

    public bool $showSmartFill = false;

    public string $smartDoctorId = '';

    /** @var list<int|string> */
    public array $smartWeekdays = [];

    /** @var list<string> */
    public array $smartPeriods = [];

    public function mount(Schedule $schedule): void
    {
        abort_unless(auth()->user()->isGestorOf($schedule->hospital), 403);

        $this->schedule = $schedule;
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

        $doctor = \App\Models\User::findOrFail($userId);

        // Resolve o valor do plantão: médico > template > padrão do hospital.
        $membership = $this->schedule->hospital->memberships()->where('user_id', $userId)->first();
        $amount = $membership?->shift_amount
            ?? $shift->template?->default_amount
            ?? $shift->template?->amount
            ?? $shift->amount;

        if ($doctor->isAbsentOn($shift->date, $this->schedule->hospital_id)) {
            $this->addError('assign', "{$doctor->name} está de ausência nesta data.");

            return;
        }

        $violations = app(\App\Services\ComplianceService::class)->check($this->schedule->hospital, $doctor, $shift);

        foreach ($violations as $v) {
            if ($v['blocking']) {
                $this->addError('assign', $v['message']);

                return;
            }
        }

        foreach ($violations as $v) {
            session()->flash('status', 'Alerta de conformidade: '.$v['message']);
        }

        $limit = \App\Models\HourLimit::forDoctorOn($userId, $this->schedule->hospital_id, $shift->date);

        if ($limit !== null) {
            $consumed = $limit->consumedHours($shift->date);
            $shiftHours = round($shift->starts_at->diffInMinutes($shift->ends_at) / 60, 1);

            if ($consumed + $shiftHours > $limit->hours) {
                if ($limit->on_swap === 'block') {
                    $this->addError('assign', "{$doctor->name} excederia o limite de {$limit->hours}h (já tem {$consumed}h).");

                    return;
                }

                session()->flash('status', "Atenção: {$doctor->name} está próximo/acima do limite de {$limit->hours}h ({$consumed}h + {$shiftHours}h).");
            }
        }

        $shift->update([
            'user_id' => $userId,
            'status' => ShiftStatus::Confirmado,
            'confirmed_at' => now(),
            'amount' => $amount,
        ]);
    }

    public function substitute(int $shiftId, int $userId, \App\Services\SubstitutionService $service): void
    {
        $shift = $this->schedule->shifts()->whereKey($shiftId)->firstOrFail();
        $doctor = \App\Models\User::findOrFail($userId);

        $isDoctor = $this->schedule->hospital->memberships()
            ->where('user_id', $userId)
            ->where('role', Role::Medico->value)
            ->where('active', true)
            ->exists();

        if (! $isDoctor) {
            $this->addError('assign', 'Este usuário não é médico deste hospital.');

            return;
        }

        if ($doctor->isAbsentOn($shift->date, $this->schedule->hospital_id)) {
            $this->addError('assign', "{$doctor->name} está de ausência nesta data.");

            return;
        }

        $service->substitute($shift, $doctor, auth()->user());
        session()->flash('status', "Plantão substituído por {$doctor->name}.");
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

    public function toggleSwapApproval(): void
    {
        $this->schedule->update(['swap_requires_approval' => ! $this->schedule->swap_requires_approval]);
        $this->schedule->refresh();
    }

    public function openSmartFill(): void
    {
        $this->reset(['smartDoctorId', 'smartWeekdays', 'smartPeriods']);
        $this->resetValidation();
        $this->showSmartFill = true;
    }

    public function closeSmartFill(): void
    {
        $this->reset(['showSmartFill', 'smartDoctorId', 'smartWeekdays', 'smartPeriods']);
        $this->resetValidation();
    }

    public function applySmartFill(): void
    {
        if ($this->schedule->status !== ScheduleStatus::Rascunho) {
            $this->addError('smartDoctorId', 'Apenas escalas em rascunho podem ser preenchidas.');

            return;
        }

        $this->validate([
            'smartDoctorId' => ['required', 'integer'],
            'smartWeekdays' => ['required', 'array', 'min:1'],
            'smartWeekdays.*' => ['integer', 'between:0,6', 'distinct'],
            'smartPeriods' => ['required', 'array', 'min:1'],
            'smartPeriods.*' => ['string', 'in:dia,noite', 'distinct'],
        ], attributes: [
            'smartDoctorId' => 'médico',
            'smartWeekdays' => 'dias da semana',
            'smartPeriods' => 'turnos',
        ]);

        $doctor = $this->schedule->hospital->memberships()
            ->where('user_id', (int) $this->smartDoctorId)
            ->where('role', Role::Medico->value)
            ->where('active', true)
            ->with('user')
            ->firstOrFail()
            ->user;

        $weekdays = array_map('intval', $this->smartWeekdays);
        $periods = array_values($this->smartPeriods);

        $shifts = $this->schedule->shifts()
            ->whereIn('period', $periods)
            ->get()
            ->filter(fn ($shift) => in_array($shift->date->dayOfWeek, $weekdays, true));

        $skipped = 0;

        DB::transaction(function () use ($shifts, $doctor, &$skipped): void {
            foreach ($shifts as $shift) {
                if ($doctor->isAbsentOn($shift->date, $this->schedule->hospital_id)) {
                    $skipped++;

                    continue;
                }

                $shift->update([
                    'user_id' => $doctor->id,
                    'status' => ShiftStatus::Confirmado,
                    'confirmed_at' => now(),
                ]);
            }
        });

        $count = $shifts->count() - $skipped;
        $this->closeSmartFill();
        $msg = "Preenchimento inteligente aplicado: {$count} plantões atribuídos a {$doctor->name}.";
        if ($skipped > 0) {
            $msg .= " ({$skipped} ignorado(s) por ausência)";
        }
        session()->flash('status', $msg);
    }

    public function restorePlanned(int $shiftId, \App\Services\CheckinTreatmentService $service): void
    {
        $shift = $this->schedule->shifts()->whereKey($shiftId)->firstOrFail();
        $service->restorePlanned($shift);
        session()->flash('status', 'Horários restaurados para o planejado.');
    }

    public function consolidateShift(int $shiftId, \App\Services\CheckinTreatmentService $service): void
    {
        $shift = $this->schedule->shifts()->whereKey($shiftId)->firstOrFail();
        $service->consolidate($shift);
        session()->flash('status', 'Plantão consolidado (oficializado).');
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

        $presencas = $this->schedule->shifts()
            ->with(['doctor', 'checkins.user'])
            ->whereNotNull('user_id')
            ->orderBy('date')
            ->orderBy('starts_at')
            ->get()
            ->map(fn ($shift) => [
                'shift' => $shift,
                'in' => $shift->checkins->firstWhere('type', 'in'),
                'out' => $shift->checkins->firstWhere('type', 'out'),
            ]);

        return [
            'days' => $days,
            'doctors' => $doctors,
            'leadingBlanks' => (int) $start->dayOfWeek,
            'monthLabel' => ucfirst($start->translatedFormat('F \d\e Y')),
            'isPublished' => $this->schedule->status === ScheduleStatus::Publicada,
            'total' => $total,
            'preenchidos' => $preenchidos,
            'presencas' => $presencas,
        ];
    }
}; ?>

<div x-data="{ dragging: null, view: 'calendar' }">
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
            <div>
                @if ($isPublished)
                    <span class="inline-flex items-center gap-1 rounded-full bg-teal-100 text-teal-700 px-3 py-1 text-sm font-medium">Publicada</span>
                @endif
            </div>
        </div>
    </x-slot>

    <div class="py-6">
        <div class="max-w-[1400px] mx-auto sm:px-6 lg:px-8">
            @if (session('status'))
                <div class="mb-4 rounded-lg bg-teal-50 text-teal-800 px-4 py-3 text-sm">{{ session('status') }}</div>
            @endif

            <div class="mb-4 flex flex-wrap items-center justify-between gap-3">
                <button wire:click="toggleSwapApproval" type="button"
                    class="inline-flex items-center gap-3 rounded-lg border px-4 py-2.5 text-left transition
                        {{ $schedule->swap_requires_approval ? 'border-amber-200 bg-amber-50' : 'border-teal-200 bg-teal-50' }}">
                    <span class="relative inline-flex h-6 w-11 shrink-0 items-center rounded-full transition {{ $schedule->swap_requires_approval ? 'bg-amber-400' : 'bg-teal-500' }}">
                        <span class="inline-block h-4 w-4 transform rounded-full bg-white shadow transition {{ $schedule->swap_requires_approval ? 'translate-x-6' : 'translate-x-1' }}"></span>
                    </span>
                    <span>
                        <span class="block text-sm font-semibold {{ $schedule->swap_requires_approval ? 'text-amber-800' : 'text-teal-800' }}">
                            {{ $schedule->swap_requires_approval ? 'Troca com autorização do gestor' : 'Troca livre entre médicos' }}
                        </span>
                        <span class="block text-xs {{ $schedule->swap_requires_approval ? 'text-amber-600' : 'text-teal-600' }}">
                            {{ $schedule->swap_requires_approval ? 'Os médicos só podem trocar de plantão com a sua aprovação.' : 'Os médicos podem trocar de plantão livremente.' }}
                        </span>
                    </span>
                </button>

                <div class="flex flex-wrap gap-2">
                    @unless ($isPublished)
                        <x-secondary-button wire:click="openSmartFill" :disabled="$doctors->isEmpty()">
                            Preenchimento inteligente
                        </x-secondary-button>
                    @endunless
                    <x-primary-button wire:click="publish" wire:confirm="Publicar a escala e avisar os médicos com plantão?">
                        {{ $isPublished ? 'Republicar' : 'Publicar escala' }}
                    </x-primary-button>
                </div>
            </div>

            <div class="mb-4 flex flex-wrap items-center justify-between gap-3">
                <div class="flex items-center gap-3 flex-1 min-w-[200px]">
                    <div class="flex-1 h-2 rounded-full bg-gray-100 overflow-hidden">
                        <div class="h-full bg-teal-500 transition-all" style="width: {{ $total > 0 ? round($preenchidos / $total * 100) : 0 }}%"></div>
                    </div>
                    <span class="text-sm text-gray-500 whitespace-nowrap">{{ $preenchidos }}/{{ $total }} preenchidos</span>
                </div>
                <div class="inline-flex rounded-lg border border-gray-200 bg-white p-1 text-sm">
                    <button type="button" @click="view = 'calendar'" :class="view === 'calendar' ? 'bg-teal-600 text-white' : 'text-gray-600'" class="rounded-md px-3 py-1 font-medium transition">Calendário</button>
                    <button type="button" @click="view = 'presencas'" :class="view === 'presencas' ? 'bg-teal-600 text-white' : 'text-gray-600'" class="rounded-md px-3 py-1 font-medium transition">Presenças</button>
                </div>
            </div>

            {{-- Painel de presenças --}}
            <div x-show="view === 'presencas'" x-cloak class="bg-white shadow-sm sm:rounded-lg overflow-hidden">
                <div class="overflow-x-auto">
                    <table class="w-full text-sm">
                        <thead>
                            <tr class="text-left text-xs uppercase text-gray-400 border-b border-gray-100">
                                <th class="px-4 py-2">Data</th>
                                <th class="px-4 py-2">Turno</th>
                                <th class="px-4 py-2">Médico</th>
                                <th class="px-4 py-2">Check-in</th>
                                <th class="px-4 py-2">Check-out</th>
                                <th class="px-4 py-2">Status</th>
                                <th class="px-4 py-2">Ações</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse ($presencas as $p)
                                <tr class="border-t border-gray-50">
                                    <td class="px-4 py-2.5">{{ $p['shift']->date->format('d/m') }}</td>
                                    <td class="px-4 py-2.5">{{ $p['shift']->starts_at->format('H:i') }}–{{ $p['shift']->ends_at->format('H:i') }}</td>
                                    <td class="px-4 py-2.5">{{ $p['shift']->doctor?->name }}</td>
                                    <td class="px-4 py-2.5">
                                        @if ($p['in'])
                                            <span class="text-green-700">{{ $p['in']->checked_at->format('H:i') }}</span>
                                            <span class="text-xs text-gray-400">({{ $p['in']->methodLabel() }})</span>
                                        @else
                                            <span class="text-gray-300">—</span>
                                        @endif
                                    </td>
                                    <td class="px-4 py-2.5">
                                        @if ($p['out'])
                                            <span class="text-blue-700">{{ $p['out']->checked_at->format('H:i') }}</span>
                                            <span class="text-xs text-gray-400">({{ $p['out']->methodLabel() }})</span>
                                        @else
                                            <span class="text-gray-300">—</span>
                                        @endif
                                    </td>
                                    <td class="px-4 py-2.5">
                                        @if ($p['shift']->consolidated_at)
                                            <span class="inline-flex rounded-full bg-teal-100 text-teal-700 px-2 py-0.5 text-xs font-medium">Consolidado</span>
                                        @elseif ($p['in'] && $p['out'])
                                            <span class="inline-flex rounded-full bg-green-100 text-green-700 px-2 py-0.5 text-xs font-medium">Completo</span>
                                        @elseif ($p['in'])
                                            <span class="inline-flex rounded-full bg-amber-100 text-amber-700 px-2 py-0.5 text-xs font-medium">Em andamento</span>
                                        @else
                                            <span class="inline-flex rounded-full bg-gray-100 text-gray-500 px-2 py-0.5 text-xs font-medium">Sem registro</span>
                                        @endif
                                    </td>
                                    <td class="px-4 py-2.5">
                                        @unless ($p['shift']->consolidated_at)
                                            <div class="flex gap-1">
                                                <button wire:click="restorePlanned({{ $p['shift']->id }})" type="button" title="Restaurar horário planejado" class="rounded bg-gray-100 px-2 py-1 text-xs text-gray-600 hover:bg-gray-200">Restaurar</button>
                                                @if ($p['in'] && $p['out'])
                                                    <button wire:click="consolidateShift({{ $p['shift']->id }})" type="button" title="Consolidar horários" class="rounded bg-teal-600 px-2 py-1 text-xs text-white hover:bg-teal-700">Consolidar</button>
                                                @endif
                                            </div>
                                        @endunless
                                    </td>
                                </tr>
                            @empty
                                <tr><td colspan="7" class="px-4 py-8 text-center text-sm text-gray-400">Nenhum plantão preenchido nesta escala.</td></tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>

            <div x-show="view === 'calendar'" class="grid grid-cols-1 lg:grid-cols-[1fr_260px] gap-6">
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

            @if ($showSmartFill && ! $isPublished)
                <div class="fixed inset-0 z-50 flex items-center justify-center p-4">
                    <div class="fixed inset-0 bg-gray-900/50" wire:click="closeSmartFill"></div>
                    <div class="relative max-h-[calc(100vh-2rem)] w-full max-w-lg overflow-y-auto rounded-lg bg-white p-6 shadow-xl">
                        <h3 class="text-lg font-semibold text-gray-900">Preenchimento inteligente</h3>
                        <p class="mt-1 text-sm text-gray-500">Escolha uma pessoa, os dias e os turnos que ela fará durante este mês.</p>

                        <div class="mt-5 space-y-5">
                            <div>
                                <x-input-label for="smartDoctorId" value="Médico *" />
                                <select wire:model="smartDoctorId" id="smartDoctorId" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-teal-500 focus:ring-teal-500">
                                    <option value="">Selecione o médico</option>
                                    @foreach ($doctors as $doctor)
                                        <option value="{{ $doctor->id }}">{{ $doctor->name }}</option>
                                    @endforeach
                                </select>
                                <x-input-error :messages="$errors->get('smartDoctorId')" class="mt-2" />
                            </div>

                            <fieldset>
                                <legend class="text-sm font-medium text-gray-700">Dias da semana *</legend>
                                <div class="mt-2 grid grid-cols-2 gap-2 sm:grid-cols-4">
                                    @foreach (['Domingo', 'Segunda', 'Terça', 'Quarta', 'Quinta', 'Sexta', 'Sábado'] as $weekday => $label)
                                        <label class="flex items-center gap-2 rounded-md border border-gray-200 px-3 py-2 text-sm text-gray-700 hover:bg-teal-50">
                                            <input type="checkbox" wire:model="smartWeekdays" value="{{ $weekday }}" class="rounded border-gray-300 text-teal-600 focus:ring-teal-500">
                                            {{ $label }}
                                        </label>
                                    @endforeach
                                </div>
                                <x-input-error :messages="$errors->get('smartWeekdays')" class="mt-2" />
                            </fieldset>

                            <fieldset>
                                <legend class="text-sm font-medium text-gray-700">Turnos *</legend>
                                <div class="mt-2 grid grid-cols-1 gap-2 sm:grid-cols-2">
                                    <label class="flex items-center gap-2 rounded-md border border-gray-200 px-3 py-3 text-sm text-gray-700 hover:bg-teal-50">
                                        <input type="checkbox" wire:model="smartPeriods" value="dia" class="rounded border-gray-300 text-teal-600 focus:ring-teal-500">
                                        Diurno · 07–19h
                                    </label>
                                    <label class="flex items-center gap-2 rounded-md border border-gray-200 px-3 py-3 text-sm text-gray-700 hover:bg-teal-50">
                                        <input type="checkbox" wire:model="smartPeriods" value="noite" class="rounded border-gray-300 text-teal-600 focus:ring-teal-500">
                                        Noturno · 19–07h
                                    </label>
                                </div>
                                <x-input-error :messages="$errors->get('smartPeriods')" class="mt-2" />
                            </fieldset>

                            <p class="rounded-md bg-amber-50 px-3 py-2 text-xs text-amber-800">
                                Plantões já preenchidos dentro desta seleção serão substituídos pelo médico escolhido.
                            </p>
                        </div>

                        <div class="mt-6 flex justify-end gap-2">
                            <x-secondary-button wire:click="closeSmartFill">Cancelar</x-secondary-button>
                            <x-primary-button wire:click="applySmartFill">Aplicar preenchimento</x-primary-button>
                        </div>
                    </div>
                </div>
            @endif
        </div>
    </div>
</div>
