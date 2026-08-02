<?php

use App\Enums\Role;
use App\Models\Absence;
use App\Models\HourLimit;
use App\Models\User;
use Livewire\Attributes\Layout;
use Livewire\Volt\Component;

new #[Layout('layouts.app')] class extends Component
{
    public string $doctorId = '';

    public string $startsOn = '';

    public string $endsOn = '';

    public string $reason = '';

    public string $scope = 'hospital';

    // Limite de horas
    public string $limitDoctorId = '';

    public string $limitHours = '40';

    public string $limitPeriod = 'monthly';

    public string $limitStartsOn = '';

    public string $limitEndsOn = '';

    public string $limitOnSwap = 'alert';

    // Conformidade
    public string $maxShiftHours = '';

    public string $minRestHours = '';

    public string $minRestHoursNight = '';

    public string $conflictMode = 'alert';

    public function mount(): void
    {
        abort_unless(auth()->user()->isGestor(), 403);

        $hospital = currentHospital();
        if ($hospital !== null) {
            $this->maxShiftHours = $hospital->max_shift_hours !== null ? (string) $hospital->max_shift_hours : '';
            $this->minRestHours = $hospital->min_rest_hours !== null ? (string) $hospital->min_rest_hours : '';
            $this->minRestHoursNight = $hospital->min_rest_hours_night !== null ? (string) $hospital->min_rest_hours_night : '';
            $this->conflictMode = $hospital->conflict_mode ?? 'alert';
        }
    }

    public function saveCompliance(): void
    {
        $hospital = currentHospital();
        abort_unless($hospital !== null, 403);

        $data = $this->validate([
            'maxShiftHours' => ['nullable', 'integer', 'min:1', 'max:48'],
            'minRestHours' => ['nullable', 'integer', 'min:0', 'max:48'],
            'minRestHoursNight' => ['nullable', 'integer', 'min:0', 'max:48'],
            'conflictMode' => ['required', 'in:alert,block,off'],
        ]);

        $hospital->update([
            'max_shift_hours' => $data['maxShiftHours'] !== '' ? (int) $data['maxShiftHours'] : null,
            'min_rest_hours' => $data['minRestHours'] !== '' ? (int) $data['minRestHours'] : null,
            'min_rest_hours_night' => $data['minRestHoursNight'] !== '' ? (int) $data['minRestHoursNight'] : null,
            'conflict_mode' => $data['conflictMode'],
        ]);

        session()->flash('status', 'Regras de conformidade salvas.');
    }

    public function save(): void
    {
        $hospital = currentHospital();
        abort_unless($hospital !== null, 403);

        $data = $this->validate([
            'doctorId' => ['required', 'integer'],
            'startsOn' => ['required', 'date'],
            'endsOn' => ['required', 'date', 'after_or_equal:startsOn'],
            'reason' => ['nullable', 'string', 'max:255'],
            'scope' => ['required', 'in:hospital,all'],
        ], attributes: [
            'doctorId' => 'médico',
            'startsOn' => 'início',
            'endsOn' => 'fim',
            'reason' => 'motivo',
        ]);

        $isDoctor = $hospital->memberships()
            ->where('user_id', (int) $data['doctorId'])
            ->where('role', Role::Medico->value)
            ->where('active', true)
            ->exists();

        if (! $isDoctor) {
            $this->addError('doctorId', 'Este usuário não é médico deste hospital.');

            return;
        }

        $absence = Absence::create([
            'user_id' => (int) $data['doctorId'],
            'hospital_id' => $data['scope'] === 'all' ? null : $hospital->id,
            'starts_on' => $data['startsOn'],
            'ends_on' => $data['endsOn'],
            'reason' => $data['reason'] !== '' ? $data['reason'] : null,
            'scope' => $data['scope'],
        ]);

        $this->reset(['doctorId', 'startsOn', 'endsOn', 'reason', 'scope']);
        $this->scope = 'hospital';
        $this->treatingAbsenceId = $absence->id;
        session()->flash('status', 'Ausência registrada. Trate os plantões afetados abaixo.');
    }

    public function remove(int $id): void
    {
        Absence::whereKey($id)->delete();
        session()->flash('status', 'Ausência removida.');
    }

    public ?int $treatingAbsenceId = null;

    public function closeTreatment(): void
    {
        $this->treatingAbsenceId = null;
    }

    public function substituteShift(int $shiftId, \App\Services\AbsenceService $service): void
    {
        $shift = \App\Models\Shift::findOrFail($shiftId);
        $substitute = $service->suggestSubstitute($shift);

        if ($substitute === null) {
            $this->addError('treatment', 'Nenhum substituto elegível encontrado para este plantão.');

            return;
        }

        $service->substitute($shift, $substitute);
        session()->flash('status', "Plantão substituído por {$substitute->name}.");
    }

    public function announceShift(int $shiftId, \App\Services\AbsenceService $service): void
    {
        $shift = \App\Models\Shift::findOrFail($shiftId);
        $service->announceCoverage($shift);
        session()->flash('status', 'Plantão anunciado como cobertura de ausência.');
    }

    public function saveLimit(): void
    {
        $hospital = currentHospital();
        abort_unless($hospital !== null, 403);

        $data = $this->validate([
            'limitDoctorId' => ['required', 'integer'],
            'limitHours' => ['required', 'integer', 'min:1', 'max:400'],
            'limitPeriod' => ['required', 'in:monthly,weekly'],
            'limitStartsOn' => ['required', 'date'],
            'limitEndsOn' => ['nullable', 'date', 'after_or_equal:limitStartsOn'],
            'limitOnSwap' => ['required', 'in:block,alert'],
        ], attributes: [
            'limitDoctorId' => 'médico',
            'limitHours' => 'horas',
            'limitStartsOn' => 'início',
        ]);

        $isDoctor = $hospital->memberships()
            ->where('user_id', (int) $data['limitDoctorId'])
            ->where('role', Role::Medico->value)
            ->where('active', true)
            ->exists();

        if (! $isDoctor) {
            $this->addError('limitDoctorId', 'Este usuário não é médico deste hospital.');

            return;
        }

        HourLimit::create([
            'user_id' => (int) $data['limitDoctorId'],
            'hospital_id' => $hospital->id,
            'hours' => (int) $data['limitHours'],
            'period' => $data['limitPeriod'],
            'starts_on' => $data['limitStartsOn'],
            'ends_on' => $data['limitEndsOn'] !== '' ? $data['limitEndsOn'] : null,
            'on_swap' => $data['limitOnSwap'],
            'on_announce' => $data['limitOnSwap'],
        ]);

        $this->reset(['limitDoctorId', 'limitHours', 'limitStartsOn', 'limitEndsOn']);
        $this->limitHours = '40';
        session()->flash('status', 'Limite de horas definido.');
    }

    public function removeLimit(int $id): void
    {
        HourLimit::whereKey($id)->delete();
        session()->flash('status', 'Limite removido.');
    }

    /**
     * @return array<string, mixed>
     */
    public function with(): array
    {
        $hospital = currentHospital();

        $doctors = $hospital?->memberships()
            ->where('role', Role::Medico->value)
            ->where('active', true)
            ->with('user')
            ->get()
            ->pluck('user')
            ->filter()
            ->sortBy('name')
            ->values() ?? collect();

        $doctorIds = $doctors->pluck('id');

        $absences = Absence::with('user')
            ->whereIn('user_id', $doctorIds)
            ->where(function ($q) use ($hospital) {
                $q->where('scope', 'all')
                    ->orWhere('hospital_id', $hospital?->id);
            })
            ->orderByDesc('starts_on')
            ->get();

        $limits = HourLimit::with('user')
            ->where('hospital_id', $hospital?->id)
            ->orderBy('starts_on')
            ->get();

        $treatingAbsence = null;
        $affectedShifts = collect();

        if ($this->treatingAbsenceId !== null) {
            $treatingAbsence = Absence::with('user')->find($this->treatingAbsenceId);
            if ($treatingAbsence !== null) {
                $affectedShifts = app(\App\Services\AbsenceService::class)->affectedShifts($treatingAbsence);
            }
        }

        return [
            'hospital' => $hospital,
            'doctors' => $doctors,
            'absences' => $absences,
            'limits' => $limits,
            'treatingAbsence' => $treatingAbsence,
            'affectedShifts' => $affectedShifts,
        ];
    }
}; ?>

<div>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 leading-tight">Ausências</h2>
        <p class="text-sm text-gray-500">{{ $hospital?->name }}</p>
    </x-slot>

    <div class="py-6">
        <div class="max-w-4xl mx-auto sm:px-6 lg:px-8 space-y-6">
            @if (session('status'))
                <div class="rounded-lg bg-teal-50 text-teal-800 px-4 py-3 text-sm">{{ session('status') }}</div>
            @endif

            {{-- Formulário --}}
            <div class="bg-white shadow-sm sm:rounded-lg p-6">
                <h3 class="text-sm font-semibold text-gray-800 mb-4">Registrar ausência</h3>
                <form wire:submit="save" class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                    <div class="sm:col-span-2">
                        <x-input-label for="doctorId" value="Médico *" />
                        <select wire:model="doctorId" id="doctorId" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-teal-500 focus:ring-teal-500">
                            <option value="">Selecione</option>
                            @foreach ($doctors as $doctor)
                                <option value="{{ $doctor->id }}">{{ $doctor->name }}</option>
                            @endforeach
                        </select>
                        <x-input-error :messages="$errors->get('doctorId')" class="mt-1" />
                    </div>
                    <div>
                        <x-input-label for="startsOn" value="Início *" />
                        <x-text-input wire:model="startsOn" id="startsOn" type="date" class="mt-1 block w-full" />
                        <x-input-error :messages="$errors->get('startsOn')" class="mt-1" />
                    </div>
                    <div>
                        <x-input-label for="endsOn" value="Fim *" />
                        <x-text-input wire:model="endsOn" id="endsOn" type="date" class="mt-1 block w-full" />
                        <x-input-error :messages="$errors->get('endsOn')" class="mt-1" />
                    </div>
                    <div>
                        <x-input-label for="scope" value="Escopo *" />
                        <select wire:model="scope" id="scope" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-teal-500 focus:ring-teal-500">
                            <option value="hospital">Neste hospital</option>
                            <option value="all">Todas as escalas</option>
                        </select>
                        <x-input-error :messages="$errors->get('scope')" class="mt-1" />
                    </div>
                    <div>
                        <x-input-label for="reason" value="Motivo" />
                        <x-text-input wire:model="reason" id="reason" type="text" class="mt-1 block w-full" placeholder="Atestado, férias, licença..." />
                        <x-input-error :messages="$errors->get('reason')" class="mt-1" />
                    </div>
                    <div class="sm:col-span-2 flex justify-end">
                        <x-primary-button type="submit">Registrar ausência</x-primary-button>
                    </div>
                </form>
            </div>

            {{-- Lista --}}
            <div class="bg-white shadow-sm sm:rounded-lg">
                <p class="border-b border-gray-100 px-6 py-3 text-sm font-semibold text-gray-700">Ausências registradas</p>
                <ul class="divide-y divide-gray-50">
                    @forelse ($absences as $absence)
                        <li class="flex items-center justify-between gap-3 px-6 py-3">
                            <div class="min-w-0">
                                <p class="text-sm font-medium text-gray-900">{{ $absence->user?->name }}</p>
                                <p class="text-xs text-gray-500">
                                    {{ $absence->starts_on->format('d/m/Y') }} a {{ $absence->ends_on->format('d/m/Y') }}
                                    · {{ $absence->scopeLabel() }}
                                    @if ($absence->reason) · {{ $absence->reason }} @endif
                                </p>
                            </div>
                            <button wire:click="remove({{ $absence->id }})" wire:confirm="Remover esta ausência?" type="button" class="text-xs text-red-600 hover:underline">Remover</button>
                        </li>
                    @empty
                        <li class="px-6 py-8 text-center text-sm text-gray-400">Nenhuma ausência registrada.</li>
                    @endforelse
                </ul>
            </div>

            {{-- Tratamento de ausência em turnos publicados --}}
            @if ($treatingAbsence)
                <div class="bg-amber-50 border border-amber-200 sm:rounded-lg p-6">
                    <div class="flex items-start justify-between gap-3">
                        <div>
                            <h3 class="text-sm font-semibold text-amber-900">Tratar plantões de {{ $treatingAbsence->user?->name }}</h3>
                            <p class="text-xs text-amber-700">Ausência de {{ $treatingAbsence->starts_on->format('d/m') }} a {{ $treatingAbsence->ends_on->format('d/m') }} — {{ $affectedShifts->count() }} plantão(ões) publicado(s) afetado(s).</p>
                        </div>
                        <button wire:click="closeTreatment" type="button" class="text-xs text-amber-700 hover:underline">Fechar</button>
                    </div>

                    <x-input-error :messages="$errors->get('treatment')" class="mt-2" />

                    @if ($affectedShifts->isNotEmpty())
                        <ul class="mt-4 space-y-2">
                            @foreach ($affectedShifts as $shift)
                                <li class="flex flex-wrap items-center justify-between gap-2 rounded-lg border border-amber-200 bg-white px-4 py-2.5 text-sm">
                                    <div>
                                        <span class="font-medium text-gray-900">{{ $shift->date->format('d/m') }} · {{ $shift->starts_at->format('H:i') }}–{{ $shift->ends_at->format('H:i') }}</span>
                                        <span class="text-xs text-gray-500"> · {{ $shift->schedule?->monthLabel() }}</span>
                                    </div>
                                    <div class="flex gap-2">
                                        <button wire:click="substituteShift({{ $shift->id }})" type="button" class="rounded-md bg-teal-600 px-3 py-1 text-xs font-medium text-white hover:bg-teal-700">Substituir</button>
                                        <button wire:click="announceShift({{ $shift->id }})" type="button" class="rounded-md bg-amber-600 px-3 py-1 text-xs font-medium text-white hover:bg-amber-700">Anunciar cobertura</button>
                                    </div>
                                </li>
                            @endforeach
                        </ul>
                    @else
                        <p class="mt-3 text-sm text-amber-700">Nenhum plantão publicado afetado por esta ausência.</p>
                    @endif
                </div>
            @endif

            {{-- Limite de horas --}}
            <div class="bg-white shadow-sm sm:rounded-lg p-6">
                <h3 class="text-sm font-semibold text-gray-800 mb-1">Limite de horas por médico</h3>
                <p class="text-xs text-gray-500 mb-4">Impede ou alerta quando um médico ultrapassa a carga horária no período.</p>
                <form wire:submit="saveLimit" class="grid grid-cols-1 sm:grid-cols-3 gap-4">
                    <div class="sm:col-span-3">
                        <x-input-label for="limitDoctorId" value="Médico *" />
                        <select wire:model="limitDoctorId" id="limitDoctorId" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-teal-500 focus:ring-teal-500">
                            <option value="">Selecione</option>
                            @foreach ($doctors as $doctor)
                                <option value="{{ $doctor->id }}">{{ $doctor->name }}</option>
                            @endforeach
                        </select>
                        <x-input-error :messages="$errors->get('limitDoctorId')" class="mt-1" />
                    </div>
                    <div>
                        <x-input-label for="limitHours" value="Horas *" />
                        <x-text-input wire:model="limitHours" id="limitHours" type="number" min="1" max="400" class="mt-1 block w-full" />
                        <x-input-error :messages="$errors->get('limitHours')" class="mt-1" />
                    </div>
                    <div>
                        <x-input-label for="limitPeriod" value="Período *" />
                        <select wire:model="limitPeriod" id="limitPeriod" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-teal-500 focus:ring-teal-500">
                            <option value="monthly">Mensal</option>
                            <option value="weekly">Semanal</option>
                        </select>
                        <x-input-error :messages="$errors->get('limitPeriod')" class="mt-1" />
                    </div>
                    <div>
                        <x-input-label for="limitOnSwap" value="Ao atingir *" />
                        <select wire:model="limitOnSwap" id="limitOnSwap" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-teal-500 focus:ring-teal-500">
                            <option value="alert">Apenas alertar</option>
                            <option value="block">Bloquear</option>
                        </select>
                        <x-input-error :messages="$errors->get('limitOnSwap')" class="mt-1" />
                    </div>
                    <div>
                        <x-input-label for="limitStartsOn" value="Vigência inicial *" />
                        <x-text-input wire:model="limitStartsOn" id="limitStartsOn" type="date" class="mt-1 block w-full" />
                        <x-input-error :messages="$errors->get('limitStartsOn')" class="mt-1" />
                    </div>
                    <div>
                        <x-input-label for="limitEndsOn" value="Vigência final" />
                        <x-text-input wire:model="limitEndsOn" id="limitEndsOn" type="date" class="mt-1 block w-full" />
                        <x-input-error :messages="$errors->get('limitEndsOn')" class="mt-1" />
                    </div>
                    <div class="flex items-end justify-end">
                        <x-primary-button type="submit">Definir limite</x-primary-button>
                    </div>
                </form>
            </div>

            {{-- Lista de limites --}}
            <div class="bg-white shadow-sm sm:rounded-lg">
                <p class="border-b border-gray-100 px-6 py-3 text-sm font-semibold text-gray-700">Limites definidos</p>
                <ul class="divide-y divide-gray-50">
                    @forelse ($limits as $limit)
                        <li class="flex items-center justify-between gap-3 px-6 py-3">
                            <div class="min-w-0">
                                <p class="text-sm font-medium text-gray-900">{{ $limit->user?->name }}</p>
                                <p class="text-xs text-gray-500">
                                    {{ $limit->hours }}h {{ $limit->periodLabel() }}
                                    · {{ $limit->starts_on->format('d/m/Y') }}{{ $limit->ends_on ? ' a '.$limit->ends_on->format('d/m/Y') : ' em diante' }}
                                    · {{ $limit->on_swap === 'block' ? 'Bloqueia' : 'Apenas alerta' }}
                                </p>
                            </div>
                            <button wire:click="removeLimit({{ $limit->id }})" wire:confirm="Remover este limite?" type="button" class="text-xs text-red-600 hover:underline">Remover</button>
                        </li>
                    @empty
                        <li class="px-6 py-8 text-center text-sm text-gray-400">Nenhum limite definido.</li>
                    @endforelse
                </ul>
            </div>

            {{-- Conformidade --}}
            <div class="bg-white shadow-sm sm:rounded-lg p-6">
                <h3 class="text-sm font-semibold text-gray-800 mb-1">Regras de conformidade</h3>
                <p class="text-xs text-gray-500 mb-4">Tempo máximo de turno, descanso entre plantões e conflito de agenda. Deixe em branco para não aplicar a regra.</p>
                <form wire:submit="saveCompliance" class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                    <div>
                        <x-input-label for="maxShiftHours" value="Tempo máximo de turno (h)" />
                        <x-text-input wire:model="maxShiftHours" id="maxShiftHours" type="number" min="1" max="48" class="mt-1 block w-full" placeholder="Ex.: 12" />
                        <x-input-error :messages="$errors->get('maxShiftHours')" class="mt-1" />
                    </div>
                    <div>
                        <x-input-label for="minRestHours" value="Descanso mínimo (h)" />
                        <x-text-input wire:model="minRestHours" id="minRestHours" type="number" min="0" max="48" class="mt-1 block w-full" placeholder="Ex.: 11" />
                        <x-input-error :messages="$errors->get('minRestHours')" class="mt-1" />
                    </div>
                    <div>
                        <x-input-label for="minRestHoursNight" value="Descanso noturno (h)" />
                        <x-text-input wire:model="minRestHoursNight" id="minRestHoursNight" type="number" min="0" max="48" class="mt-1 block w-full" placeholder="Ex.: 12" />
                        <x-input-error :messages="$errors->get('minRestHoursNight')" class="mt-1" />
                    </div>
                    <div>
                        <x-input-label for="conflictMode" value="Conflito de agenda *" />
                        <select wire:model="conflictMode" id="conflictMode" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-teal-500 focus:ring-teal-500">
                            <option value="alert">Apenas alertar</option>
                            <option value="block">Bloquear</option>
                            <option value="off">Desligado</option>
                        </select>
                        <x-input-error :messages="$errors->get('conflictMode')" class="mt-1" />
                    </div>
                    <div class="sm:col-span-2 flex justify-end">
                        <x-primary-button type="submit">Salvar regras</x-primary-button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>
