<?php

use App\Enums\Role;
use App\Models\Hospital;
use Illuminate\Support\Collection;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Validate;
use Livewire\Volt\Component;

new #[Layout('layouts.app')] class extends Component
{
    public ?int $editingId = null;

    #[Validate('required|string|max:255')]
    public string $name = '';

    #[Validate('nullable|string|max:20')]
    public string $cnpj = '';

    #[Validate('nullable|string|max:255')]
    public string $address = '';

    #[Validate('nullable|string|max:20')]
    public string $phone = '';

    #[Validate('nullable|numeric|min:0|max:99999999')]
    public string $defaultShiftAmount = '';

    public bool $showForm = false;

    /**
     * @return array<string, mixed>
     */
    public function with(): array
    {
        return [
            'hospitals' => auth()->user()->managedHospitals()->orderBy('name')->get(),
        ];
    }

    public function create(): void
    {
        $this->reset(['editingId', 'name', 'cnpj', 'address', 'phone', 'defaultShiftAmount']);
        $this->resetValidation();
        $this->showForm = true;
    }

    public function edit(int $hospitalId): void
    {
        $hospital = auth()->user()->managedHospitals()->whereKey($hospitalId)->firstOrFail();

        $this->editingId = $hospital->id;
        $this->name = $hospital->name;
        $this->cnpj = $hospital->cnpj ?? '';
        $this->address = $hospital->address ?? '';
        $this->phone = $hospital->phone ?? '';
        $this->defaultShiftAmount = $hospital->default_shift_amount !== null ? (string) $hospital->default_shift_amount : '';
        $this->resetValidation();
        $this->showForm = true;
    }

    public function save(): void
    {
        $this->validate();

        $data = [
            'name' => $this->name,
            'cnpj' => $this->cnpj !== '' ? $this->cnpj : null,
            'address' => $this->address !== '' ? $this->address : null,
            'phone' => $this->phone !== '' ? $this->phone : null,
            'default_shift_amount' => $this->defaultShiftAmount !== '' ? str_replace(',', '.', $this->defaultShiftAmount) : null,
        ];

        if ($this->editingId !== null) {
            $hospital = auth()->user()->managedHospitals()->whereKey($this->editingId)->firstOrFail();
            $this->authorize('update', $hospital);
            $hospital->update($data);
        } else {
            $hospital = Hospital::create($data);
            auth()->user()->hospitalMemberships()->create([
                'hospital_id' => $hospital->id,
                'role' => Role::Gestor,
            ]);
        }

        $this->reset(['editingId', 'name', 'cnpj', 'address', 'phone', 'defaultShiftAmount', 'showForm']);
    }

    public function cancel(): void
    {
        $this->reset(['editingId', 'name', 'cnpj', 'address', 'phone', 'defaultShiftAmount', 'showForm']);
        $this->resetValidation();
    }
}; ?>

<div>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 leading-tight">Hospitais</h2>
    </x-slot>

    <div class="py-12">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8 space-y-6">

            <div class="flex justify-end">
                <x-primary-button wire:click="create">Novo hospital</x-primary-button>
            </div>

            @if ($showForm)
                <div class="bg-white shadow-sm sm:rounded-lg p-6">
                    <h3 class="text-lg font-medium text-gray-900 mb-4">
                        {{ $editingId ? 'Editar hospital' : 'Novo hospital' }}
                    </h3>

                    <form wire:submit="save" class="space-y-4">
                        <div>
                            <x-input-label for="name" value="Nome *" />
                            <x-text-input wire:model="name" id="name" class="block mt-1 w-full" type="text" required autofocus />
                            <x-input-error :messages="$errors->get('name')" class="mt-2" />
                        </div>

                        <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                            <div>
                                <x-input-label for="cnpj" value="CNPJ" />
                                <x-text-input wire:model="cnpj" id="cnpj" class="block mt-1 w-full" type="text" />
                                <x-input-error :messages="$errors->get('cnpj')" class="mt-2" />
                            </div>
                            <div>
                                <x-input-label for="phone" value="Telefone" />
                                <x-text-input wire:model="phone" id="phone" class="block mt-1 w-full" type="text" />
                                <x-input-error :messages="$errors->get('phone')" class="mt-2" />
                            </div>
                        </div>

                        <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                            <div>
                                <x-input-label for="address" value="Endereço" />
                                <x-text-input wire:model="address" id="address" class="block mt-1 w-full" type="text" />
                                <x-input-error :messages="$errors->get('address')" class="mt-2" />
                            </div>
                            <div>
                                <x-input-label for="defaultShiftAmount" value="Valor padrão por plantão (R$)" />
                                <x-text-input wire:model="defaultShiftAmount" id="defaultShiftAmount" class="block mt-1 w-full" type="text" inputmode="decimal" placeholder="Ex.: 1200,00" />
                                <p class="text-xs text-gray-400 mt-1">Aplicado automaticamente a todo plantão deste hospital. Você ainda pode ajustar um plantão específico na escala.</p>
                                <x-input-error :messages="$errors->get('defaultShiftAmount')" class="mt-2" />
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
                @forelse ($hospitals as $hospital)
                    <div class="flex items-center justify-between p-6 {{ ! $loop->last ? 'border-b border-gray-100' : '' }}">
                        <a href="{{ route('gestor.hospital', $hospital) }}" wire:navigate class="flex-1 min-w-0 group">
                            <p class="font-medium text-gray-900 group-hover:text-teal-700">{{ $hospital->name }}</p>
                            <p class="text-sm text-gray-500">
                                {{ $hospital->address ?? 'Sem endereço' }}
                                @if ($hospital->phone) · {{ $hospital->phone }} @endif
                            </p>
                        </a>
                        <div class="flex items-center gap-2">
                            <a href="{{ route('gestor.hospital', $hospital) }}" wire:navigate class="inline-flex items-center rounded-md bg-teal-600 px-3 py-2 text-sm font-medium text-white hover:bg-teal-700">Abrir</a>
                            <x-secondary-button wire:click="edit({{ $hospital->id }})">Editar</x-secondary-button>
                        </div>
                    </div>
                @empty
                    <p class="p-6 text-gray-500">Nenhum hospital cadastrado ainda.</p>
                @endforelse
            </div>
        </div>
    </div>
</div>
