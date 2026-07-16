<?php

use App\Enums\RecurrenceType;
use App\Models\Recurrence;
use App\Models\ShiftBoard;
use App\Models\ShiftTemplate;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Validate;
use Livewire\Volt\Component;

new #[Layout('layouts.app')] class extends Component
{
    public bool $showForm = false;

    public string $boardId = '';

    public string $templateId = '';

    public string $userId = '';

    #[Validate('required|in:semanal,quinzenal')]
    public string $type = 'semanal';

    #[Validate('required|date')]
    public string $reference_date = '';

    /**
     * @return array<string, mixed>
     */
    public function with(): array
    {
        $hospital = currentHospital();

        $boards = $hospital
            ? $hospital->shiftBoards()->where('active', true)->orderBy('name')->get()
            : collect();

        $selectedBoard = $this->boardId !== ''
            ? $boards->firstWhere('id', (int) $this->boardId)
            : null;

        return [
            'hospital' => $hospital,
            'boards' => $boards,
            'templates' => $selectedBoard
                ? $selectedBoard->templates()->where('active', true)->orderBy('weekday')->orderBy('start_time')->get()
                : collect(),
            'boardDoctors' => $selectedBoard
                ? $selectedBoard->doctors()->orderBy('name')->get()
                : collect(),
            'recurrences' => $hospital
                ? Recurrence::query()
                    ->whereHas('template.board', fn ($q) => $q->where('hospital_id', $hospital->id))
                    ->with(['user', 'template.board'])
                    ->latest()
                    ->get()
                : collect(),
        ];
    }

    public function create(): void
    {
        $this->reset(['boardId', 'templateId', 'userId', 'type']);
        $this->reference_date = now()->toDateString();
        $this->resetValidation();
        $this->showForm = true;
    }

    public function save(): void
    {
        $this->validate([
            'boardId' => 'required|integer',
            'templateId' => 'required|integer',
            'userId' => 'required|integer',
            'type' => 'required|in:semanal,quinzenal',
            'reference_date' => 'required|date',
        ]);

        $board = $this->findBoard((int) $this->boardId);

        $template = $board->templates()->where('active', true)->whereKey((int) $this->templateId)->firstOrFail();

        abort_unless($board->doctors()->whereKey((int) $this->userId)->exists(), 422);

        if (\Illuminate\Support\Carbon::parse($this->reference_date)->dayOfWeek !== $template->weekday) {
            $this->addError('reference_date', 'A data de referência precisa cair em '.$template->weekdayLabel().'.');

            return;
        }

        $exists = Recurrence::query()
            ->where('shift_template_id', $template->id)
            ->where('user_id', (int) $this->userId)
            ->where('active', true)
            ->exists();

        if ($exists) {
            $this->addError('userId', 'Este médico já tem recorrência ativa neste turno.');

            return;
        }

        Recurrence::create([
            'user_id' => (int) $this->userId,
            'shift_template_id' => $template->id,
            'type' => $this->type,
            'reference_date' => $this->reference_date,
        ]);

        $this->reset(['showForm', 'boardId', 'templateId', 'userId']);
    }

    public function toggleActive(int $recurrenceId): void
    {
        $hospital = currentHospital();
        abort_unless($hospital !== null, 404);
        $this->authorize('update', $hospital);

        $recurrence = Recurrence::query()
            ->whereHas('template.board', fn ($q) => $q->where('hospital_id', $hospital->id))
            ->whereKey($recurrenceId)
            ->firstOrFail();

        $recurrence->update(['active' => ! $recurrence->active]);
    }

    public function cancel(): void
    {
        $this->reset(['showForm', 'boardId', 'templateId', 'userId']);
        $this->resetValidation();
    }

    private function findBoard(int $boardId): ShiftBoard
    {
        $hospital = currentHospital();
        abort_unless($hospital !== null, 404);
        $this->authorize('update', $hospital);

        return $hospital->shiftBoards()->whereKey($boardId)->firstOrFail();
    }
}; ?>

<div>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 leading-tight">
            Recorrências
            @if (currentHospital())
                <span class="text-gray-400 font-normal">— {{ currentHospital()->name }}</span>
            @endif
        </h2>
    </x-slot>

    <div class="py-12">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8 space-y-6">

            <div class="flex justify-end">
                <x-primary-button wire:click="create">Nova recorrência</x-primary-button>
            </div>

            @if ($showForm)
                <div class="bg-white shadow-sm sm:rounded-lg p-6">
                    <h3 class="text-lg font-medium text-gray-900 mb-4">Nova recorrência</h3>

                    <form wire:submit="save" class="space-y-4">
                        <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                            <div>
                                <x-input-label for="boardId" value="Quadro *" />
                                <select wire:model.live="boardId" id="boardId" class="block mt-1 w-full border-gray-300 focus:border-teal-500 focus:ring-teal-500 rounded-md shadow-sm">
                                    <option value="">Selecione…</option>
                                    @foreach ($boards as $board)
                                        <option value="{{ $board->id }}">{{ $board->name }}</option>
                                    @endforeach
                                </select>
                                <x-input-error :messages="$errors->get('boardId')" class="mt-2" />
                            </div>
                            <div>
                                <x-input-label for="templateId" value="Turno *" />
                                <select wire:model="templateId" id="templateId" class="block mt-1 w-full border-gray-300 focus:border-teal-500 focus:ring-teal-500 rounded-md shadow-sm" @disabled($templates->isEmpty())>
                                    <option value="">Selecione…</option>
                                    @foreach ($templates as $template)
                                        <option value="{{ $template->id }}">
                                            {{ $template->weekdayLabel() }} {{ substr($template->start_time, 0, 5) }}–{{ substr($template->end_time, 0, 5) }}
                                            @if ($template->label) ({{ $template->label }}) @endif
                                        </option>
                                    @endforeach
                                </select>
                                <x-input-error :messages="$errors->get('templateId')" class="mt-2" />
                            </div>
                            <div>
                                <x-input-label for="userId" value="Médico *" />
                                <select wire:model="userId" id="userId" class="block mt-1 w-full border-gray-300 focus:border-teal-500 focus:ring-teal-500 rounded-md shadow-sm" @disabled($boardDoctors->isEmpty())>
                                    <option value="">Selecione…</option>
                                    @foreach ($boardDoctors as $doctor)
                                        <option value="{{ $doctor->id }}">{{ $doctor->name }}</option>
                                    @endforeach
                                </select>
                                @if ($boardId !== '' && $boardDoctors->isEmpty())
                                    <p class="mt-1 text-xs text-amber-600">Este quadro não tem médicos vinculados.</p>
                                @endif
                                <x-input-error :messages="$errors->get('userId')" class="mt-2" />
                            </div>
                            <div>
                                <x-input-label for="type" value="Tipo *" />
                                <select wire:model="type" id="type" class="block mt-1 w-full border-gray-300 focus:border-teal-500 focus:ring-teal-500 rounded-md shadow-sm">
                                    <option value="semanal">Semanal</option>
                                    <option value="quinzenal">Quinzenal</option>
                                </select>
                                <x-input-error :messages="$errors->get('type')" class="mt-2" />
                            </div>
                            <div>
                                <x-input-label for="reference_date" value="Primeira ocorrência *" />
                                <x-text-input wire:model="reference_date" id="reference_date" class="block mt-1 w-full" type="date" required />
                                <p class="mt-1 text-xs text-gray-400">Define a paridade da quinzena. Precisa cair no dia da semana do turno.</p>
                                <x-input-error :messages="$errors->get('reference_date')" class="mt-2" />
                            </div>
                        </div>

                        <div class="flex gap-3">
                            <x-primary-button>Salvar</x-primary-button>
                            <x-secondary-button wire:click="cancel" type="button">Cancelar</x-secondary-button>
                        </div>
                    </form>
                </div>
            @endif

            <div class="bg-white shadow-sm sm:rounded-lg overflow-hidden">
                @forelse ($recurrences as $recurrence)
                    <div class="flex items-center justify-between p-6 {{ ! $loop->last ? 'border-b border-gray-100' : '' }}">
                        <div>
                            <p class="font-medium text-gray-900">
                                {{ $recurrence->user->name }}
                                @unless ($recurrence->active)
                                    <span class="ml-2 text-xs text-red-600 bg-red-50 rounded px-2 py-0.5">Inativa</span>
                                @endunless
                            </p>
                            <p class="text-sm text-gray-500">
                                {{ $recurrence->template->board->name }}
                                · {{ $recurrence->template->weekdayLabel() }} {{ substr($recurrence->template->start_time, 0, 5) }}–{{ substr($recurrence->template->end_time, 0, 5) }}
                                · {{ $recurrence->type->label() }}
                                · desde {{ $recurrence->reference_date->format('d/m/Y') }}
                            </p>
                        </div>
                        <x-secondary-button wire:click="toggleActive({{ $recurrence->id }})">
                            {{ $recurrence->active ? 'Desativar' : 'Reativar' }}
                        </x-secondary-button>
                    </div>
                @empty
                    <p class="p-6 text-gray-500">Nenhuma recorrência. Crie uma pra pré-preencher as escalas do mês.</p>
                @endforelse
            </div>
        </div>
    </div>
</div>
