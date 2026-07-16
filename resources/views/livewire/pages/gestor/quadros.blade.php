<?php

use App\Models\ShiftBoard;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Validate;
use Livewire\Volt\Component;

new #[Layout('layouts.app')] class extends Component
{
    public ?int $editingId = null;

    #[Validate('required|string|max:255')]
    public string $name = '';

    #[Validate('nullable|string|max:255')]
    public string $description = '';

    public bool $showForm = false;

    /**
     * @return array<string, mixed>
     */
    public function with(): array
    {
        $hospital = currentHospital();

        return [
            'hospital' => $hospital,
            'boards' => $hospital
                ? $hospital->shiftBoards()->withCount('templates', 'doctors')->orderBy('name')->get()
                : collect(),
        ];
    }

    public function create(): void
    {
        $this->reset(['editingId', 'name', 'description']);
        $this->resetValidation();
        $this->showForm = true;
    }

    public function edit(int $boardId): void
    {
        $board = $this->findBoard($boardId);

        $this->editingId = $board->id;
        $this->name = $board->name;
        $this->description = $board->description ?? '';
        $this->resetValidation();
        $this->showForm = true;
    }

    public function save(): void
    {
        $this->validate();

        $hospital = currentHospital();
        abort_unless($hospital !== null, 404);
        $this->authorize('update', $hospital);

        $data = [
            'name' => $this->name,
            'description' => $this->description !== '' ? $this->description : null,
        ];

        if ($this->editingId !== null) {
            $this->findBoard($this->editingId)->update($data);
        } else {
            $hospital->shiftBoards()->create($data);
        }

        $this->reset(['editingId', 'name', 'description', 'showForm']);
    }

    public function toggleActive(int $boardId): void
    {
        $board = $this->findBoard($boardId);
        $board->update(['active' => ! $board->active]);
    }

    public function cancel(): void
    {
        $this->reset(['editingId', 'name', 'description', 'showForm']);
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
            Quadros de plantão
            @if (currentHospital())
                <span class="text-gray-400 font-normal">— {{ currentHospital()->name }}</span>
            @endif
        </h2>
    </x-slot>

    <div class="py-12">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8 space-y-6">

            <div class="flex justify-end">
                <x-primary-button wire:click="create">Novo quadro</x-primary-button>
            </div>

            @if ($showForm)
                <div class="bg-white shadow-sm sm:rounded-lg p-6">
                    <h3 class="text-lg font-medium text-gray-900 mb-4">
                        {{ $editingId ? 'Editar quadro' : 'Novo quadro' }}
                    </h3>

                    <form wire:submit="save" class="space-y-4">
                        <div>
                            <x-input-label for="name" value="Nome *" />
                            <x-text-input wire:model="name" id="name" class="block mt-1 w-full" type="text" required autofocus placeholder="Ex.: Diurno UTI" />
                            <x-input-error :messages="$errors->get('name')" class="mt-2" />
                        </div>

                        <div>
                            <x-input-label for="description" value="Descrição" />
                            <x-text-input wire:model="description" id="description" class="block mt-1 w-full" type="text" />
                            <x-input-error :messages="$errors->get('description')" class="mt-2" />
                        </div>

                        <div class="flex gap-3">
                            <x-primary-button>Salvar</x-primary-button>
                            <x-secondary-button wire:click="cancel" type="button">Cancelar</x-secondary-button>
                        </div>
                    </form>
                </div>
            @endif

            <div class="bg-white shadow-sm sm:rounded-lg overflow-hidden">
                @forelse ($boards as $board)
                    <div class="flex items-center justify-between p-6 {{ ! $loop->last ? 'border-b border-gray-100' : '' }}">
                        <div>
                            <p class="font-medium text-gray-900">
                                {{ $board->name }}
                                @unless ($board->active)
                                    <span class="ml-2 text-xs text-red-600 bg-red-50 rounded px-2 py-0.5">Inativo</span>
                                @endunless
                            </p>
                            <p class="text-sm text-gray-500">
                                {{ $board->templates_count }} {{ $board->templates_count === 1 ? 'turno' : 'turnos' }}
                                · {{ $board->doctors_count }} {{ $board->doctors_count === 1 ? 'médico' : 'médicos' }}
                                @if ($board->description) · {{ $board->description }} @endif
                            </p>
                        </div>
                        <div class="flex gap-2">
                            <a href="{{ route('gestor.quadro', $board) }}" wire:navigate>
                                <x-primary-button type="button">Abrir</x-primary-button>
                            </a>
                            <x-secondary-button wire:click="edit({{ $board->id }})">Editar</x-secondary-button>
                            <x-secondary-button wire:click="toggleActive({{ $board->id }})">
                                {{ $board->active ? 'Desativar' : 'Reativar' }}
                            </x-secondary-button>
                        </div>
                    </div>
                @empty
                    <p class="p-6 text-gray-500">Nenhum quadro criado ainda. Crie o primeiro — ex.: "Diurno UTI".</p>
                @endforelse
            </div>
        </div>
    </div>
</div>
