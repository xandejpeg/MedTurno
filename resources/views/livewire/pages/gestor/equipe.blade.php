<?php

use App\Enums\InvitationStatus;
use App\Enums\Role;
use App\Services\InvitationService;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Validate;
use Livewire\Volt\Component;

new #[Layout('layouts.app')] class extends Component
{
    public bool $showForm = false;

    #[Validate('required|string|max:255')]
    public string $name = '';

    #[Validate('required|email|max:255')]
    public string $email = '';

    #[Validate('nullable|string|max:20')]
    public string $phone = '';

    /**
     * @return array<string, mixed>
     */
    public function with(): array
    {
        $hospital = currentHospital();

        if ($hospital === null) {
            return ['medicos' => collect(), 'convites' => collect(), 'hospital' => null];
        }

        return [
            'hospital' => $hospital,
            'medicos' => $hospital->memberships()
                ->with('user')
                ->where('role', Role::Medico->value)
                ->get()
                ->sortBy(fn ($m) => $m->user->name)
                ->values(),
            'convites' => $hospital->invitations()
                ->where('status', InvitationStatus::Pendente)
                ->latest()
                ->get(),
        ];
    }

    public function invite(InvitationService $service): void
    {
        $this->validate();

        $hospital = currentHospital();
        abort_if($hospital === null, 404);
        $this->authorize('update', $hospital);

        $service->invite($hospital, auth()->user(), $this->name, $this->email, $this->phone !== '' ? $this->phone : null);

        $this->reset(['name', 'email', 'phone', 'showForm']);
        $this->dispatch('convite-enviado');
    }

    public function resend(int $invitationId, InvitationService $service): void
    {
        $hospital = currentHospital();
        abort_if($hospital === null, 404);
        $this->authorize('update', $hospital);

        $invitation = App\Models\Invitation::query()
            ->where('hospital_id', $hospital->id)
            ->findOrFail($invitationId);

        $service->invite($hospital, auth()->user(), $invitation->name, $invitation->email, $invitation->phone);
    }

    public function toggleActive(int $membershipId): void
    {
        $hospital = currentHospital();
        abort_if($hospital === null, 404);
        $this->authorize('update', $hospital);

        $membership = $hospital->memberships()
            ->where('role', Role::Medico->value)
            ->findOrFail($membershipId);

        $membership->update(['active' => ! $membership->active]);
    }
}; ?>

<div>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 leading-tight">
            Equipe — {{ $hospital?->name ?? 'Sem hospital' }}
        </h2>
    </x-slot>

    <div class="py-12">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8 space-y-6">

            <div class="flex justify-end">
                <x-primary-button wire:click="$set('showForm', true)">Convidar médico</x-primary-button>
            </div>

            <div x-data="{ show: false }" x-on:convite-enviado.window="show = true; setTimeout(() => show = false, 4000)">
                <div x-show="show" x-transition class="bg-green-50 border border-green-200 text-green-800 rounded-lg p-4 text-sm" style="display: none;">
                    Convite enviado com sucesso!
                </div>
            </div>

            @if ($showForm)
                <div class="bg-white shadow-sm sm:rounded-lg p-6">
                    <h3 class="text-lg font-medium text-gray-900 mb-4">Convidar médico</h3>

                    <form wire:submit="invite" class="space-y-4">
                        <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                            <div>
                                <x-input-label for="name" value="Nome *" />
                                <x-text-input wire:model="name" id="name" class="block mt-1 w-full" type="text" required autofocus />
                                <x-input-error :messages="$errors->get('name')" class="mt-2" />
                            </div>
                            <div>
                                <x-input-label for="email" value="E-mail *" />
                                <x-text-input wire:model="email" id="email" class="block mt-1 w-full" type="email" required />
                                <x-input-error :messages="$errors->get('email')" class="mt-2" />
                            </div>
                        </div>

                        <div>
                            <x-input-label for="phone" value="Telefone" />
                            <x-text-input wire:model="phone" id="phone" class="block mt-1 w-full" type="text" placeholder="+5581999999999" />
                            <x-input-error :messages="$errors->get('phone')" class="mt-2" />
                        </div>

                        <div class="flex gap-3">
                            <x-primary-button>Enviar convite</x-primary-button>
                            <x-secondary-button wire:click="$set('showForm', false)" type="button">Cancelar</x-secondary-button>
                        </div>
                    </form>
                </div>
            @endif

            @if ($convites->isNotEmpty())
                <div class="bg-white shadow-sm sm:rounded-lg overflow-hidden">
                    <div class="px-6 py-4 border-b border-gray-100">
                        <h3 class="font-medium text-gray-900">Convites pendentes</h3>
                    </div>
                    @foreach ($convites as $convite)
                        <div class="flex items-center justify-between px-6 py-4 {{ ! $loop->last ? 'border-b border-gray-100' : '' }}">
                            <div>
                                <p class="font-medium text-gray-900">{{ $convite->name }}</p>
                                <p class="text-sm text-gray-500">{{ $convite->email }} · expira {{ $convite->expires_at->format('d/m/Y') }}</p>
                            </div>
                            <x-secondary-button wire:click="resend({{ $convite->id }})">Reenviar</x-secondary-button>
                        </div>
                    @endforeach
                </div>
            @endif

            <div class="bg-white shadow-sm sm:rounded-lg overflow-hidden">
                <div class="px-6 py-4 border-b border-gray-100">
                    <h3 class="font-medium text-gray-900">Médicos ({{ $medicos->count() }})</h3>
                </div>
                @forelse ($medicos as $membership)
                    <div class="flex items-center justify-between px-6 py-4 {{ ! $loop->last ? 'border-b border-gray-100' : '' }}">
                        <div>
                            <p class="font-medium text-gray-900 {{ ! $membership->active ? 'line-through text-gray-400' : '' }}">
                                {{ $membership->user->name }}
                            </p>
                            <p class="text-sm text-gray-500">
                                {{ $membership->user->email }}
                                @if (! $membership->active) · <span class="text-red-500">desativado</span> @endif
                            </p>
                        </div>
                        <x-secondary-button wire:click="toggleActive({{ $membership->id }})">
                            {{ $membership->active ? 'Desativar' : 'Reativar' }}
                        </x-secondary-button>
                    </div>
                @empty
                    <p class="p-6 text-gray-500">Nenhum médico na equipe ainda. Convide o primeiro!</p>
                @endforelse
            </div>
        </div>
    </div>
</div>
