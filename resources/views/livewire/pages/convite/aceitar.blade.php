<?php

use App\Models\Invitation;
use App\Models\User;
use App\Services\InvitationService;
use Illuminate\Support\Facades\Auth;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Locked;
use Livewire\Attributes\Validate;
use Livewire\Volt\Component;

new #[Layout('layouts.guest')] class extends Component
{
    #[Locked]
    public string $token = '';

    public bool $valid = false;

    public bool $existingUser = false;

    public string $hospitalName = '';

    public string $inviteeName = '';

    #[Validate('required|string|min:8|confirmed')]
    public string $password = '';

    public string $password_confirmation = '';

    public function mount(InvitationService $service): void
    {
        $this->token = (string) request()->query('token', '');

        $invitation = $service->findUsableByToken($this->token);

        if ($invitation !== null) {
            $this->valid = true;
            $this->hospitalName = $invitation->hospital->name;
            $this->inviteeName = $invitation->name;
            $this->existingUser = User::where('email', $invitation->email)->exists();
        }
    }

    public function accept(InvitationService $service): void
    {
        if ($this->existingUser) {
            $user = $service->accept($this->token);
        } else {
            $this->validate();
            $user = $service->accept($this->token, $this->password);
        }

        Auth::login($user);
        session()->regenerate();

        $this->redirect(route('dashboard'), navigate: true);
    }
}; ?>

<div>
    @if (! $valid)
        <div class="text-center space-y-4">
            <h1 class="text-xl font-semibold text-gray-900">Convite inválido</h1>
            <p class="text-gray-600">Este convite não existe, já foi usado ou expirou. Peça ao gestor para reenviar.</p>
            <a href="{{ route('login') }}" class="underline text-sm text-gray-600 hover:text-gray-900" wire:navigate>Ir para o login</a>
        </div>
    @else
        <div class="space-y-4">
            <div class="text-center">
                <h1 class="text-xl font-semibold text-gray-900">Olá, {{ $inviteeName }}!</h1>
                <p class="text-gray-600 mt-1">Você foi convidado(a) para a equipe do <strong>{{ $hospitalName }}</strong>.</p>
            </div>

            <form wire:submit="accept" class="space-y-4">
                @if (! $existingUser)
                    <div>
                        <x-input-label for="password" value="Defina sua senha" />
                        <x-text-input wire:model="password" id="password" class="block mt-1 w-full" type="password" required autofocus autocomplete="new-password" />
                        <x-input-error :messages="$errors->get('password')" class="mt-2" />
                    </div>

                    <div>
                        <x-input-label for="password_confirmation" value="Confirme a senha" />
                        <x-text-input wire:model="password_confirmation" id="password_confirmation" class="block mt-1 w-full" type="password" required autocomplete="new-password" />
                    </div>
                @else
                    <p class="text-sm text-gray-600 text-center">Você já tem uma conta no MedTurno — é só aceitar pra entrar nessa equipe também.</p>
                @endif

                <x-primary-button class="w-full justify-center">Aceitar convite</x-primary-button>
            </form>
        </div>
    @endif
</div>
