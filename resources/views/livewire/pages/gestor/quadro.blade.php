<?php

use App\Enums\Role;
use App\Models\ShiftBoard;
use App\Models\ShiftTemplate;
use App\Services\ShiftTemplateService;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Locked;
use Livewire\Volt\Component;

new #[Layout('layouts.app')] class extends Component
{
    #[Locked]
    public int $boardId;

    public string $tab = 'estrutura';

    // ---- form de template ----
    public bool $showTemplateForm = false;

    public ?int $editingTemplateId = null;

    public int $weekday = 1;

    public string $start_time = '07:00';

    public string $end_time = '19:00';

    public int $slots = 1;

    public string $amount = '';

    public string $label = '';

    /** @var list<int> dias extras pra replicar o template (só na criação) */
    public array $extraWeekdays = [];

    // ---- grade automática ----
    public bool $showGridForm = false;

    public int $gridDuration = 12;

    public string $gridStart = '07:00';

    public int $gridSlots = 1;

    public string $gridAmount = '';

    // ---- participantes ----
    /** @var list<int> */
    public array $selectedDoctors = [];

    public function mount(ShiftBoard $board): void
    {
        $this->authorize('update', $board->hospital);
        $this->boardId = $board->id;
        $this->selectedDoctors = $board->doctors()->pluck('users.id')->all();
    }

    public function board(): ShiftBoard
    {
        $board = ShiftBoard::with('hospital')->findOrFail($this->boardId);
        $this->authorize('update', $board->hospital);

        return $board;
    }

    /**
     * @return array<string, mixed>
     */
    public function with(): array
    {
        $board = $this->board();

        return [
            'board' => $board,
            'templatesByWeekday' => $board->templates()
                ->orderBy('weekday')->orderBy('start_time')
                ->get()
                ->groupBy('weekday'),
            'doctors' => $board->hospital->memberships()
                ->where('role', Role::Medico)
                ->where('active', true)
                ->with('user')
                ->get()
                ->pluck('user')
                ->sortBy('name')
                ->values(),
        ];
    }

    // ================= Estrutura =================

    public function createTemplate(): void
    {
        $this->reset(['editingTemplateId', 'weekday', 'start_time', 'end_time', 'slots', 'amount', 'label', 'extraWeekdays']);
        $this->resetValidation();
        $this->showTemplateForm = true;
        $this->showGridForm = false;
    }

    public function editTemplate(int $templateId): void
    {
        $template = $this->board()->templates()->whereKey($templateId)->firstOrFail();

        $this->editingTemplateId = $template->id;
        $this->weekday = $template->weekday;
        $this->start_time = substr($template->start_time, 0, 5);
        $this->end_time = substr($template->end_time, 0, 5);
        $this->slots = $template->slots;
        $this->amount = $template->amount !== null ? number_format((float) $template->amount, 2, ',', '') : '';
        $this->label = $template->label ?? '';
        $this->extraWeekdays = [];
        $this->resetValidation();
        $this->showTemplateForm = true;
        $this->showGridForm = false;
    }

    public function saveTemplate(ShiftTemplateService $service): void
    {
        $this->validate([
            'weekday' => 'required|integer|between:0,6',
            'start_time' => ['required', 'date_format:H:i'],
            'end_time' => ['required', 'date_format:H:i'],
            'slots' => 'required|integer|between:1,20',
            'amount' => ['nullable', 'regex:/^\d{1,7}([.,]\d{1,2})?$/'],
            'label' => 'nullable|string|max:50',
            'extraWeekdays' => 'array',
            'extraWeekdays.*' => 'integer|between:0,6',
        ]);

        $board = $this->board();
        $crosses = $this->end_time <= $this->start_time;
        $amount = $this->amount !== '' ? str_replace(',', '.', $this->amount) : null;

        $weekdays = $this->editingTemplateId !== null
            ? [$this->weekday]
            : array_values(array_unique([$this->weekday, ...array_map('intval', $this->extraWeekdays)]));

        foreach ($weekdays as $weekday) {
            if ($service->overlaps($board, $weekday, $this->start_time, $this->end_time, $crosses, $this->editingTemplateId)) {
                $this->addError('start_time', 'Este horário sobrepõe outro turno de '.ShiftTemplate::WEEKDAYS[$weekday].'.');

                return;
            }
        }

        $data = [
            'start_time' => $this->start_time,
            'end_time' => $this->end_time,
            'crosses_midnight' => $crosses,
            'slots' => $this->slots,
            'amount' => $amount,
            'label' => $this->label !== '' ? $this->label : null,
        ];

        if ($this->editingTemplateId !== null) {
            $board->templates()->whereKey($this->editingTemplateId)->firstOrFail()
                ->update([...$data, 'weekday' => $this->weekday]);
        } else {
            foreach ($weekdays as $weekday) {
                $board->templates()->create([...$data, 'weekday' => $weekday]);
            }
        }

        $this->reset(['showTemplateForm', 'editingTemplateId', 'extraWeekdays']);
    }

    public function deleteTemplate(int $templateId): void
    {
        $this->board()->templates()->whereKey($templateId)->firstOrFail()->delete();
    }

    public function cancelTemplate(): void
    {
        $this->reset(['showTemplateForm', 'editingTemplateId', 'extraWeekdays']);
        $this->resetValidation();
    }

    // ================= Grade automática =================

    public function openGridForm(): void
    {
        $this->reset(['gridDuration', 'gridStart', 'gridSlots', 'gridAmount']);
        $this->resetValidation();
        $this->showGridForm = true;
        $this->showTemplateForm = false;
    }

    public function applyGrid(ShiftTemplateService $service): void
    {
        $this->validate([
            'gridDuration' => 'required|integer|in:6,12,24',
            'gridStart' => ['required', 'date_format:H:i'],
            'gridSlots' => 'required|integer|between:1,20',
            'gridAmount' => ['nullable', 'regex:/^\d{1,7}([.,]\d{1,2})?$/'],
        ]);

        $amount = $this->gridAmount !== '' ? str_replace(',', '.', $this->gridAmount) : null;

        $result = $service->applyGrid($this->board(), $this->gridDuration, $this->gridStart, $this->gridSlots, $amount);

        $this->reset(['showGridForm']);
        session()->flash('grid-result', "Grade aplicada: {$result['created']} turnos criados"
            .($result['skipped'] > 0 ? ", {$result['skipped']} pulados por sobreposição." : '.'));
    }

    public function cancelGrid(): void
    {
        $this->reset(['showGridForm']);
        $this->resetValidation();
    }

    // ================= Participantes =================

    public function saveDoctors(): void
    {
        $board = $this->board();

        $validIds = $board->hospital->memberships()
            ->where('role', Role::Medico)
            ->where('active', true)
            ->pluck('user_id')
            ->all();

        $selected = array_values(array_intersect(array_map('intval', $this->selectedDoctors), $validIds));

        $board->doctors()->sync($selected);
        $this->selectedDoctors = $selected;

        session()->flash('doctors-saved', 'Participantes atualizados.');
    }
}; ?>

<div>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 leading-tight">Quadro</h2>
    </x-slot>

    <div class="py-12">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8 space-y-6">

            <div class="flex items-center justify-between">
                <div>
                    <a href="{{ route('gestor.quadros') }}" wire:navigate class="text-sm text-gray-500 hover:text-gray-700">← Quadros</a>
                    <h3 class="text-2xl font-semibold text-gray-900">{{ $board->name }}</h3>
                    <p class="text-sm text-gray-500">{{ $board->hospital->name }}</p>
                </div>
            </div>

            {{-- Abas --}}
            <div class="border-b border-gray-200">
                <nav class="-mb-px flex gap-6">
                    <button wire:click="$set('tab', 'estrutura')"
                        class="py-3 border-b-2 text-sm font-medium {{ $tab === 'estrutura' ? 'border-teal-500 text-teal-600' : 'border-transparent text-gray-500 hover:text-gray-700' }}">
                        Estrutura
                    </button>
                    <button wire:click="$set('tab', 'participantes')"
                        class="py-3 border-b-2 text-sm font-medium {{ $tab === 'participantes' ? 'border-teal-500 text-teal-600' : 'border-transparent text-gray-500 hover:text-gray-700' }}">
                        Participantes
                    </button>
                </nav>
            </div>

            @if ($tab === 'estrutura')
                <div class="flex justify-end gap-2">
                    <x-secondary-button wire:click="openGridForm">Aplicar grade automática</x-secondary-button>
                    <x-primary-button wire:click="createTemplate">Novo turno</x-primary-button>
                </div>

                @if (session('grid-result'))
                    <div class="bg-green-50 text-green-800 text-sm rounded-lg p-4">{{ session('grid-result') }}</div>
                @endif

                @if ($showGridForm)
                    <div class="bg-white shadow-sm sm:rounded-lg p-6">
                        <h4 class="text-lg font-medium text-gray-900 mb-4">Grade automática (todos os dias)</h4>
                        <form wire:submit="applyGrid" class="space-y-4">
                            <div class="grid grid-cols-2 sm:grid-cols-4 gap-4">
                                <div>
                                    <x-input-label for="gridDuration" value="Duração" />
                                    <select wire:model="gridDuration" id="gridDuration" class="block mt-1 w-full border-gray-300 focus:border-teal-500 focus:ring-teal-500 rounded-md shadow-sm">
                                        <option value="6">6h (4 turnos/dia)</option>
                                        <option value="12">12h (2 turnos/dia)</option>
                                        <option value="24">24h (1 turno/dia)</option>
                                    </select>
                                    <x-input-error :messages="$errors->get('gridDuration')" class="mt-2" />
                                </div>
                                <div>
                                    <x-input-label for="gridStart" value="Início" />
                                    <x-text-input wire:model="gridStart" id="gridStart" class="block mt-1 w-full" type="time" required />
                                    <x-input-error :messages="$errors->get('gridStart')" class="mt-2" />
                                </div>
                                <div>
                                    <x-input-label for="gridSlots" value="Vagas" />
                                    <x-text-input wire:model="gridSlots" id="gridSlots" class="block mt-1 w-full" type="number" min="1" max="20" required />
                                    <x-input-error :messages="$errors->get('gridSlots')" class="mt-2" />
                                </div>
                                <div>
                                    <x-input-label for="gridAmount" value="Valor (R$)" />
                                    <x-text-input wire:model="gridAmount" id="gridAmount" class="block mt-1 w-full" type="text" placeholder="1200,00" />
                                    <x-input-error :messages="$errors->get('gridAmount')" class="mt-2" />
                                </div>
                            </div>
                            <div class="flex gap-3">
                                <x-primary-button>Aplicar</x-primary-button>
                                <x-secondary-button wire:click="cancelGrid" type="button">Cancelar</x-secondary-button>
                            </div>
                        </form>
                    </div>
                @endif

                @if ($showTemplateForm)
                    <div class="bg-white shadow-sm sm:rounded-lg p-6">
                        <h4 class="text-lg font-medium text-gray-900 mb-4">
                            {{ $editingTemplateId ? 'Editar turno' : 'Novo turno' }}
                        </h4>
                        <form wire:submit="saveTemplate" class="space-y-4">
                            <div class="grid grid-cols-2 sm:grid-cols-3 gap-4">
                                <div>
                                    <x-input-label for="weekday" value="Dia da semana *" />
                                    <select wire:model="weekday" id="weekday" class="block mt-1 w-full border-gray-300 focus:border-teal-500 focus:ring-teal-500 rounded-md shadow-sm">
                                        @foreach (\App\Models\ShiftTemplate::WEEKDAYS as $value => $dayLabel)
                                            <option value="{{ $value }}">{{ $dayLabel }}</option>
                                        @endforeach
                                    </select>
                                    <x-input-error :messages="$errors->get('weekday')" class="mt-2" />
                                </div>
                                <div>
                                    <x-input-label for="start_time" value="Início *" />
                                    <x-text-input wire:model="start_time" id="start_time" class="block mt-1 w-full" type="time" required />
                                    <x-input-error :messages="$errors->get('start_time')" class="mt-2" />
                                </div>
                                <div>
                                    <x-input-label for="end_time" value="Fim *" />
                                    <x-text-input wire:model="end_time" id="end_time" class="block mt-1 w-full" type="time" required />
                                    <p class="mt-1 text-xs text-gray-400">Fim menor ou igual ao início = atravessa a meia-noite.</p>
                                    <x-input-error :messages="$errors->get('end_time')" class="mt-2" />
                                </div>
                                <div>
                                    <x-input-label for="slots" value="Vagas *" />
                                    <x-text-input wire:model="slots" id="slots" class="block mt-1 w-full" type="number" min="1" max="20" required />
                                    <x-input-error :messages="$errors->get('slots')" class="mt-2" />
                                </div>
                                <div>
                                    <x-input-label for="amount" value="Valor (R$)" />
                                    <x-text-input wire:model="amount" id="amount" class="block mt-1 w-full" type="text" placeholder="1200,00" />
                                    <x-input-error :messages="$errors->get('amount')" class="mt-2" />
                                </div>
                                <div>
                                    <x-input-label for="label" value="Rótulo" />
                                    <x-text-input wire:model="label" id="label" class="block mt-1 w-full" type="text" placeholder="Diurno, Noturno…" />
                                    <x-input-error :messages="$errors->get('label')" class="mt-2" />
                                </div>
                            </div>

                            @if (! $editingTemplateId)
                                <div>
                                    <x-input-label value="Repetir também em:" />
                                    <div class="mt-2 flex flex-wrap gap-4">
                                        @foreach (\App\Models\ShiftTemplate::WEEKDAYS as $value => $dayLabel)
                                            <label class="inline-flex items-center gap-1.5 text-sm text-gray-700">
                                                <input type="checkbox" wire:model="extraWeekdays" value="{{ $value }}"
                                                    class="rounded border-gray-300 text-teal-600 shadow-sm focus:ring-teal-500">
                                                {{ $dayLabel }}
                                            </label>
                                        @endforeach
                                    </div>
                                </div>
                            @endif

                            <div class="flex gap-3">
                                <x-primary-button>Salvar</x-primary-button>
                                <x-secondary-button wire:click="cancelTemplate" type="button">Cancelar</x-secondary-button>
                            </div>
                        </form>
                    </div>
                @endif

                <div class="bg-white shadow-sm sm:rounded-lg overflow-hidden">
                    @forelse (\App\Models\ShiftTemplate::WEEKDAYS as $value => $dayLabel)
                        @continue(! isset($templatesByWeekday[$value]))
                        <div class="p-6 {{ ! $loop->last ? 'border-b border-gray-100' : '' }}">
                            <p class="font-medium text-gray-900 mb-3">{{ $dayLabel }}</p>
                            <div class="space-y-2">
                                @foreach ($templatesByWeekday[$value] as $template)
                                    <div class="flex items-center justify-between bg-gray-50 rounded-lg px-4 py-3">
                                        <div class="text-sm text-gray-700">
                                            <span class="font-medium">{{ substr($template->start_time, 0, 5) }}–{{ substr($template->end_time, 0, 5) }}</span>
                                            @if ($template->crosses_midnight)
                                                <span class="text-xs text-amber-600 bg-amber-50 rounded px-1.5 py-0.5 ml-1">+1 dia</span>
                                            @endif
                                            · {{ $template->slots }} {{ $template->slots === 1 ? 'vaga' : 'vagas' }}
                                            @if ($template->amount) · R$ {{ number_format((float) $template->amount, 2, ',', '.') }} @endif
                                            @if ($template->label) · {{ $template->label }} @endif
                                        </div>
                                        <div class="flex gap-2">
                                            <x-secondary-button wire:click="editTemplate({{ $template->id }})">Editar</x-secondary-button>
                                            <x-danger-button wire:click="deleteTemplate({{ $template->id }})" wire:confirm="Remover este turno?">Remover</x-danger-button>
                                        </div>
                                    </div>
                                @endforeach
                            </div>
                        </div>
                    @empty
                    @endforelse

                    @if ($templatesByWeekday->isEmpty())
                        <p class="p-6 text-gray-500">Nenhum turno definido. Use "Novo turno" ou "Aplicar grade automática".</p>
                    @endif
                </div>
            @else
                {{-- Participantes --}}
                @if (session('doctors-saved'))
                    <div class="bg-green-50 text-green-800 text-sm rounded-lg p-4">{{ session('doctors-saved') }}</div>
                @endif

                <div class="bg-white shadow-sm sm:rounded-lg p-6">
                    <h4 class="text-lg font-medium text-gray-900 mb-1">Médicos do quadro</h4>
                    <p class="text-sm text-gray-500 mb-4">Só médicos vinculados veem e pegam plantões deste quadro.</p>

                    @forelse ($doctors as $doctor)
                        <label class="flex items-center gap-3 py-2 {{ ! $loop->last ? 'border-b border-gray-100' : '' }}">
                            <input type="checkbox" wire:model="selectedDoctors" value="{{ $doctor->id }}"
                                class="rounded border-gray-300 text-teal-600 shadow-sm focus:ring-teal-500">
                            <span class="text-sm text-gray-800">{{ $doctor->name }}</span>
                            <span class="text-xs text-gray-400">{{ $doctor->email }}</span>
                        </label>
                    @empty
                        <p class="text-gray-500 text-sm">
                            Nenhum médico ativo neste hospital ainda.
                            <a href="{{ route('gestor.equipe') }}" wire:navigate class="text-teal-600 hover:underline">Convidar médicos →</a>
                        </p>
                    @endforelse

                    @if ($doctors->isNotEmpty())
                        <div class="mt-4">
                            <x-primary-button wire:click="saveDoctors">Salvar participantes</x-primary-button>
                        </div>
                    @endif
                </div>
            @endif
        </div>
    </div>
</div>
