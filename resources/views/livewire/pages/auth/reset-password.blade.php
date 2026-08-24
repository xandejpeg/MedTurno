<?php

use App\Models\User;
use Illuminate\Auth\Events\PasswordReset;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Password;
use Illuminate\Support\Facades\Session;
use Illuminate\Support\Str;
use Illuminate\Validation\Rules;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Locked;
use Livewire\Volt\Component;

new #[Layout('layouts.guest')] class extends Component
{
    #[Locked]
    public string $token = '';
    public string $email = '';
    public string $password = '';
    public string $password_confirmation = '';

    public function mount(string $token): void
    {
        $this->token = $token;

        $this->email = request()->string('email');
    }

    public function resetPassword(): void
    {
        $this->validate([
            'token' => ['required'],
            'email' => ['required', 'string', 'email'],
            'password' => ['required', 'string', 'confirmed', Rules\Password::defaults()],
        ]);

        $status = Password::reset(
            $this->only('email', 'password', 'password_confirmation', 'token'),
            function ($user) {
                $user->forceFill([
                    'password' => Hash::make($this->password),
                    'remember_token' => Str::random(60),
                ])->save();

                event(new PasswordReset($user));
            }
        );

        if ($status != Password::PASSWORD_RESET) {
            $this->addError('email', __($status));

            return;
        }

        Session::flash('status', __($status));

        $loginRoute = User::query()->where('email', $this->email)->value('is_admin')
            ? 'admin.login'
            : 'login';

        $this->redirectRoute($loginRoute, navigate: true);
    }
}; ?>

<div>
    <div class="mb-6 text-center">
        <h1 class="text-xl font-semibold text-gray-900">Crie uma nova senha</h1>
        <p class="mt-2 text-sm leading-6 text-gray-600">
            Digite e confirme sua nova senha para recuperar o acesso à conta.
        </p>
    </div>

    <form wire:submit="resetPassword">
        <div>
            <x-input-label for="email" :value="__('Email')" />
            <x-text-input wire:model="email" id="email" class="block mt-1 w-full opacity-80" type="email" name="email" required readonly autocomplete="username" />
            <x-input-error :messages="$errors->get('email')" class="mt-2" />
        </div>

        <div class="mt-4">
            <x-input-label for="password" value="Nova senha" />
            <div x-data="{ showPassword: false }" class="relative mt-1">
                <x-text-input wire:model="password" id="password" class="block w-full pr-20" x-bind:type="showPassword ? 'text' : 'password'" name="password" required autocomplete="new-password" />
                <button type="button" x-on:click="showPassword = ! showPassword" x-bind:aria-label="showPassword ? 'Ocultar senha' : 'Mostrar senha'" class="absolute inset-y-0 right-0 px-3 text-xs font-semibold text-teal-200 hover:text-white focus:outline-none focus:ring-2 focus:ring-inset focus:ring-teal-500">
                    <span x-text="showPassword ? 'Ocultar' : 'Mostrar'">Mostrar</span>
                </button>
            </div>
            <x-input-error :messages="$errors->get('password')" class="mt-2" />
        </div>

        <div class="mt-4">
            <x-input-label for="password_confirmation" value="Confirme a nova senha" />
            <div x-data="{ showPassword: false }" class="relative mt-1">
                <x-text-input wire:model="password_confirmation" id="password_confirmation" class="block w-full pr-20" x-bind:type="showPassword ? 'text' : 'password'" name="password_confirmation" required autocomplete="new-password" />
                <button type="button" x-on:click="showPassword = ! showPassword" x-bind:aria-label="showPassword ? 'Ocultar confirmação' : 'Mostrar confirmação'" class="absolute inset-y-0 right-0 px-3 text-xs font-semibold text-teal-200 hover:text-white focus:outline-none focus:ring-2 focus:ring-inset focus:ring-teal-500">
                    <span x-text="showPassword ? 'Ocultar' : 'Mostrar'">Mostrar</span>
                </button>
            </div>
            <x-input-error :messages="$errors->get('password_confirmation')" class="mt-2" />
        </div>

        <p class="mt-3 text-xs leading-5 text-gray-600">Use pelo menos 8 caracteres e evite senhas utilizadas em outros serviços.</p>

        <div class="mt-6">
            <x-primary-button class="w-full justify-center" wire:loading.attr="disabled" wire:target="resetPassword">
                <span wire:loading.remove wire:target="resetPassword">Salvar nova senha</span>
                <span wire:loading wire:target="resetPassword">Salvando...</span>
            </x-primary-button>
        </div>
    </form>

    <p class="mt-6 text-center text-sm text-gray-600">
        <a href="{{ route('login') }}" wire:navigate class="font-semibold underline">
            Voltar para o login
        </a>
    </p>
</div>
