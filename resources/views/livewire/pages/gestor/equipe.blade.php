<?php

use App\Enums\InvitationStatus;
use App\Enums\InvitationType;
use App\Enums\Role;
use App\Services\InvitationService;
use App\Support\PhoneNumber;
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

    #[Validate('required|string|size:2')]
    public string $phoneCountry = 'BR';

    #[Validate('nullable|string|max:30')]
    public string $phoneNumber = '';

    public string $inviteLink = '';

    public string $inviteWhatsappUrl = '';

    public string $inviteMessage = '';

    public string $invitedName = '';

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
                ->where('type', InvitationType::Individual)
                ->where('status', InvitationStatus::Pendente)
                ->latest()
                ->get(),
        ];
    }

    public function invite(InvitationService $service): void
    {
        $this->validate();

        $phone = null;

        if (trim($this->phoneNumber) !== '') {
            $phone = PhoneNumber::toE164($this->phoneCountry, $this->phoneNumber);

            if ($phone === null) {
                $this->addError('phoneNumber', 'Digite um celular válido para o país selecionado.');

                return;
            }
        }

        $hospital = currentHospital();
        abort_if($hospital === null, 404);
        $this->authorize('update', $hospital);

        $invitation = $service->invite($hospital, auth()->user(), $this->name, $this->email, $phone);

        $this->reset(['name', 'email', 'phoneNumber', 'showForm']);
        $this->surfaceLink($invitation);
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

        $fresh = $service->invite($hospital, auth()->user(), $invitation->name, $invitation->email, $invitation->phone);

        $this->surfaceLink($fresh);
    }

    /**
     * Monta o link de convite copiável e a URL pré-preenchida do WhatsApp
     * pro gestor mandar pra pessoa. O token cru só existe logo após convidar.
     */
    private function surfaceLink(App\Models\Invitation $invitation): void
    {
        $hospital = currentHospital();
        $link = route('convite.aceitar', ['token' => $invitation->plainToken]);

        $message = "Olá {$invitation->name}! Você foi convidado(a) para a equipe do "
            .($hospital?->name ?? 'hospital')." no DoctorTurn. Crie sua conta neste link: {$link}";

        $phone = preg_replace('/\D/', '', (string) $invitation->phone);

        $this->invitedName = $invitation->name;
        $this->inviteLink = $link;
        $this->inviteMessage = $message;
        $this->inviteWhatsappUrl = 'https://wa.me/'.$phone.'?text='.rawurlencode($message);
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

            @if ($inviteLink !== '')
                <div x-data="{ copied: false }" class="bg-teal-50 border border-teal-200 rounded-lg p-4 space-y-3">
                    <div class="flex items-start justify-between gap-4">
                        <div>
                            <p class="font-medium text-teal-900">Link de convite — {{ $invitedName }}</p>
                            <p class="text-sm text-teal-700">Mande este link pelo WhatsApp. Ao abrir, a pessoa cria a conta e já entra como médico do hospital.</p>
                        </div>
                        <button type="button" wire:click="$set('inviteLink', '')" class="text-teal-400 hover:text-teal-600 text-xl leading-none" title="Fechar">&times;</button>
                    </div>
                    <div class="flex flex-wrap items-center gap-2">
                        <input type="text" readonly value="{{ $inviteLink }}" x-ref="link"
                               class="flex-1 min-w-0 text-sm border-teal-300 rounded-md bg-white text-gray-700 focus:border-teal-500 focus:ring-teal-500" />
                        <button type="button"
                                x-on:click="navigator.clipboard.writeText($refs.link.value); copied = true; setTimeout(() => copied = false, 2000)"
                                class="inline-flex items-center px-3 py-2 bg-teal-600 text-white text-sm rounded-md hover:bg-teal-700">
                            <span x-show="!copied">Copiar link</span>
                            <span x-show="copied" style="display:none">Copiado!</span>
                        </button>
                        <a href="{{ $inviteWhatsappUrl }}"
                           x-data="{ shareMsg: @js($inviteMessage) }"
                           x-on:click="if (navigator.share) { $event.preventDefault(); navigator.share({ text: shareMsg }).catch(() => {}); }"
                           target="_blank" rel="noopener"
                           class="inline-flex items-center px-3 py-2 bg-green-600 text-white text-sm rounded-md hover:bg-green-700">
                            Compartilhar
                        </a>
                    </div>
                </div>
            @endif

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

                        <x-phone-input country-model="phoneCountry" number-model="phoneNumber" id="invite-phone" label="Telefone" />

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
                                <p class="text-sm text-gray-500">{{ $convite->email }} · expira {{ $convite->expires_at?->format('d/m/Y') ?? 'sem prazo' }}</p>
                            </div>
                            <x-secondary-button wire:click="resend({{ $convite->id }})">Gerar link</x-secondary-button>
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
