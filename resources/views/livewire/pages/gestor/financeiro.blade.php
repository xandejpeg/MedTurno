<?php

use App\Enums\Role;
use App\Models\Schedule;
use App\Services\FinancialReportService;
use Illuminate\Support\Carbon;
use Livewire\Attributes\Layout;
use Livewire\Volt\Component;

new #[Layout('layouts.app')] class extends Component
{
    public string $from = '';

    public string $to = '';

    public string $view = 'medico'; // medico, equipe, turno

    public string $scheduleId = '';

    public string $doctorId = '';

    public string $tag = '';

    public bool $includeBonus = true;

    public function mount(): void
    {
        abort_unless(auth()->user()->isGestor(), 403);
        $this->from = now()->startOfMonth()->toDateString();
        $this->to = now()->endOfMonth()->toDateString();
    }

    /**
     * @return array<string, mixed>
     */
    public function with(FinancialReportService $service): array
    {
        $hospital = currentHospital();
        $from = Carbon::parse($this->from);
        $to = Carbon::parse($this->to);

        $filters = array_filter([
            'schedule_id' => $this->scheduleId !== '' ? (int) $this->scheduleId : null,
            'user_id' => $this->doctorId !== '' ? (int) $this->doctorId : null,
            'tag' => $this->tag !== '' ? $this->tag : null,
            'include_bonus' => $this->includeBonus,
        ]);

        $doctors = $hospital?->memberships()
            ->where('role', Role::Medico->value)->where('active', true)
            ->with('user')->get()->pluck('user')->filter()->sortBy('name')->values() ?? collect();

        $schedules = $hospital?->schedules()->orderByDesc('year')->orderByDesc('month')->get() ?? collect();

        return [
            'hospital' => $hospital,
            'doctors' => $doctors,
            'schedules' => $schedules,
            'totals' => $service->totals($hospital, $from, $to, $filters),
            'byDoctor' => $this->view === 'medico' ? $service->consolidatedByDoctor($hospital, $from, $to, $filters) : collect(),
            'byTeam' => $this->view === 'equipe' ? $service->consolidatedByTeam($hospital, $from, $to, $filters) : collect(),
            'byShift' => $this->view === 'turno' ? $service->analyticByShift($hospital, $from, $to, $filters) : collect(),
        ];
    }
}; ?>

<div>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 leading-tight">Financeiro</h2>
        <p class="text-sm text-gray-500">{{ $hospital?->name }}</p>
    </x-slot>

    <div class="py-6">
        <div class="max-w-6xl mx-auto sm:px-6 lg:px-8 space-y-6">

            {{-- Filtros --}}
            <div class="bg-white shadow-sm sm:rounded-lg p-4 grid grid-cols-2 sm:grid-cols-3 lg:grid-cols-6 gap-3">
                <div>
                    <x-input-label for="from" value="De" />
                    <x-text-input wire:model.live="from" id="from" type="date" class="mt-1 block w-full" />
                </div>
                <div>
                    <x-input-label for="to" value="Até" />
                    <x-text-input wire:model.live="to" id="to" type="date" class="mt-1 block w-full" />
                </div>
                <div>
                    <x-input-label for="scheduleId" value="Escala" />
                    <select wire:model.live="scheduleId" id="scheduleId" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm text-sm">
                        <option value="">Todas</option>
                        @foreach ($schedules as $s)
                            <option value="{{ $s->id }}">{{ $s->monthLabel() }}</option>
                        @endforeach
                    </select>
                </div>
                <div>
                    <x-input-label for="doctorId" value="Médico" />
                    <select wire:model.live="doctorId" id="doctorId" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm text-sm">
                        <option value="">Todos</option>
                        @foreach ($doctors as $d)
                            <option value="{{ $d->id }}">{{ $d->name }}</option>
                        @endforeach
                    </select>
                </div>
                <div>
                    <x-input-label for="tag" value="TAG" />
                    <x-text-input wire:model.live="tag" id="tag" type="text" class="mt-1 block w-full" placeholder="Ex.: UTI" />
                </div>
                <div class="flex items-end">
                    <label class="flex items-center gap-2 text-sm text-gray-700">
                        <input type="checkbox" wire:model.live="includeBonus" class="rounded border-gray-300 text-teal-600">
                        Incluir bônus
                    </label>
                </div>
            </div>

            {{-- Totais --}}
            <div class="grid grid-cols-2 sm:grid-cols-4 gap-4">
                <div class="bg-white shadow-sm rounded-lg p-4 text-center">
                    <p class="text-2xl font-bold text-gray-900">{{ $totals['plantoes'] }}</p>
                    <p class="text-xs text-gray-500">Plantões</p>
                </div>
                <div class="bg-white shadow-sm rounded-lg p-4 text-center">
                    <p class="text-2xl font-bold text-gray-900">{{ $totals['horas'] }}h</p>
                    <p class="text-xs text-gray-500">Horas</p>
                </div>
                <div class="bg-white shadow-sm rounded-lg p-4 text-center">
                    <p class="text-2xl font-bold text-teal-700">R$ {{ number_format($totals['valor'], 2, ',', '.') }}</p>
                    <p class="text-xs text-gray-500">Total</p>
                </div>
                <div class="bg-white shadow-sm rounded-lg p-4 text-center">
                    <p class="text-2xl font-bold text-gray-900">{{ $totals['medicos'] }}</p>
                    <p class="text-xs text-gray-500">Médicos</p>
                </div>
            </div>

            {{-- Abas + exportar --}}
            <div class="flex flex-wrap items-center justify-between gap-3 border-b border-gray-200">
                <div class="flex gap-2">
                    @foreach (['medico' => 'Por médico', 'equipe' => 'Por equipe', 'turno' => 'Por turno'] as $key => $label)
                        <button wire:click="$set('view', '{{ $key }}')" type="button"
                            class="px-4 py-2 text-sm font-medium border-b-2 -mb-px transition {{ $view === $key ? 'border-teal-600 text-teal-700' : 'border-transparent text-gray-500 hover:text-gray-700' }}">
                            {{ $label }}
                        </button>
                    @endforeach
                </div>
                <a href="{{ route('gestor.financeiro.exportar', ['view' => $view, 'from' => $from, 'to' => $to, 'schedule_id' => $scheduleId, 'user_id' => $doctorId, 'tag' => $tag, 'include_bonus' => $includeBonus]) }}"
                   class="mb-1 inline-flex items-center gap-1 rounded-md bg-teal-600 px-3 py-1.5 text-xs font-medium text-white hover:bg-teal-700">
                    <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16v2a2 2 0 002 2h12a2 2 0 002-2v-2M7 10l5 5 5-5M12 15V3"/></svg>
                    Exportar xlsx
                </a>
            </div>

            {{-- Por médico --}}
            @if ($view === 'medico')
                <div class="bg-white shadow-sm sm:rounded-lg overflow-hidden">
                    <table class="w-full text-sm">
                        <thead>
                            <tr class="text-left text-xs uppercase text-gray-400 border-b border-gray-100">
                                <th class="px-4 py-2">Médico</th>
                                <th class="px-4 py-2 text-right">Plantões</th>
                                <th class="px-4 py-2 text-right">Horas</th>
                                <th class="px-4 py-2 text-right">Valor</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse ($byDoctor as $row)
                                <tr class="border-t border-gray-50">
                                    <td class="px-4 py-2.5">{{ $row['doctor']?->name }}</td>
                                    <td class="px-4 py-2.5 text-right">{{ $row['plantoes'] }}</td>
                                    <td class="px-4 py-2.5 text-right">{{ $row['horas'] }}h</td>
                                    <td class="px-4 py-2.5 text-right font-medium text-teal-700">R$ {{ number_format($row['valor'], 2, ',', '.') }}</td>
                                </tr>
                            @empty
                                <tr><td colspan="4" class="px-4 py-8 text-center text-sm text-gray-400">Nenhum plantão no período.</td></tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            @endif

            {{-- Por equipe --}}
            @if ($view === 'equipe')
                <div class="bg-white shadow-sm sm:rounded-lg overflow-hidden">
                    <table class="w-full text-sm">
                        <thead>
                            <tr class="text-left text-xs uppercase text-gray-400 border-b border-gray-100">
                                <th class="px-4 py-2">Equipe</th>
                                <th class="px-4 py-2 text-right">Plantões</th>
                                <th class="px-4 py-2 text-right">Horas</th>
                                <th class="px-4 py-2 text-right">Valor</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse ($byTeam as $row)
                                <tr class="border-t border-gray-50">
                                    <td class="px-4 py-2.5">{{ $row['equipe'] }}</td>
                                    <td class="px-4 py-2.5 text-right">{{ $row['plantoes'] }}</td>
                                    <td class="px-4 py-2.5 text-right">{{ $row['horas'] }}h</td>
                                    <td class="px-4 py-2.5 text-right font-medium text-teal-700">R$ {{ number_format($row['valor'], 2, ',', '.') }}</td>
                                </tr>
                            @empty
                                <tr><td colspan="4" class="px-4 py-8 text-center text-sm text-gray-400">Nenhum plantão no período.</td></tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            @endif

            {{-- Por turno --}}
            @if ($view === 'turno')
                <div class="bg-white shadow-sm sm:rounded-lg overflow-hidden">
                    <table class="w-full text-sm">
                        <thead>
                            <tr class="text-left text-xs uppercase text-gray-400 border-b border-gray-100">
                                <th class="px-4 py-2">Data</th>
                                <th class="px-4 py-2">Horário</th>
                                <th class="px-4 py-2">Equipe</th>
                                <th class="px-4 py-2">Médico</th>
                                <th class="px-4 py-2 text-right">Valor</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse ($byShift as $shift)
                                <tr class="border-t border-gray-50">
                                    <td class="px-4 py-2.5">{{ $shift->date->format('d/m') }}</td>
                                    <td class="px-4 py-2.5">{{ $shift->starts_at->format('H:i') }}–{{ $shift->ends_at->format('H:i') }}</td>
                                    <td class="px-4 py-2.5">{{ $shift->schedule?->board?->name ?? 'Geral' }}</td>
                                    <td class="px-4 py-2.5">{{ $shift->doctor?->name ?? '—' }}</td>
                                    <td class="px-4 py-2.5 text-right font-medium text-teal-700">R$ {{ number_format((float) $shift->amount + (float) $shift->bonus_amount, 2, ',', '.') }}</td>
                                </tr>
                            @empty
                                <tr><td colspan="5" class="px-4 py-8 text-center text-sm text-gray-400">Nenhum plantão no período.</td></tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            @endif
        </div>
    </div>
</div>
