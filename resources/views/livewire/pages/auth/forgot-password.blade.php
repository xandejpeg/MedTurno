<?php

use Illuminate\Support\Facades\Password;
use Livewire\Attributes\Layout;
use Livewire\Volt\Component;

new #[Layout('layouts.guest')] class extends Component
{
    public string $email = '';

    public function sendPasswordResetLink(): void
    {
        $this->validate([
            'email' => ['required', 'string', 'email'],
        ]);

        $status = Password::sendResetLink(
            $this->only('email')
        );

        if ($status === Password::INVALID_USER) {
            $this->reset('email');
            session()->flash('status', __(Password::RESET_LINK_SENT));

            return;
        }

        if ($status != Password::RESET_LINK_SENT) {
            $this->addError('email', __($status));

            return;
        }

        $this->reset('email');

        session()->flash('status', __($status));
    }
}; ?>

<div>
    <div class="mb-6 text-center">
        <h1 class="text-xl font-semibold text-gray-900">Recuperar senha</h1>
        <p class="mt-2 text-sm leading-6 text-gray-600">
            Informe o e-mail usado no DoctorTurn. Enviaremos um link seguro para você criar uma nova senha.
        </p>
    </div>

    <x-auth-session-status class="mb-4" :status="session('status')" />

    <form wire:submit="sendPasswordResetLink">
        <div>
            <x-input-label for="email" :value="__('Email')" />
            <x-text-input wire:model="email" id="email" class="block mt-1 w-full" type="email" name="email" required autofocus autocomplete="email" placeholder="seuemail@exemplo.com" />
            <x-input-error :messages="$errors->get('email')" class="mt-2" />
        </div>

        <div class="mt-6">
            <x-primary-button class="w-full justify-center" wire:loading.attr="disabled" wire:target="sendPasswordResetLink">
                <span wire:loading.remove wire:target="sendPasswordResetLink">Enviar link de recuperação</span>
                <span wire:loading wire:target="sendPasswordResetLink">Enviando...</span>
            </x-primary-button>
        </div>
    </form>

    <p class="mt-6 text-center text-sm text-gray-600">
        <a href="{{ route('login') }}" wire:navigate class="font-semibold underline">
            Voltar para o login
        </a>
    </p>

    <p class="mt-5 border-t border-white/15 pt-5 text-center text-xs leading-5 text-gray-600">
        O link expira em 60 minutos e só pode ser usado uma vez.
    </p>
</div>
