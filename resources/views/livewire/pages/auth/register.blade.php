<?php

use App\Enums\Role;
use App\Models\User;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\Rules\Password;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Validate;
use Livewire\Volt\Component;

new #[Layout('layouts.guest')] class extends Component
{
    public string $role = '';

    public string $name = '';

    public string $email = '';

    public string $phone = '';

    public string $password = '';

    public string $password_confirmation = '';

    public function register(): void
    {
        $validated = $this->validate([
            'role' => ['required', 'in:gestor,medico'],
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'string', 'email', 'max:255', 'unique:'.User::class],
            'phone' => ['required', 'string', 'max:30', 'regex:/^(?=(?:\D*\d){10,11}\D*$)[\d\s()+-]+$/'],
            'password' => ['required', 'confirmed', Password::defaults()],
        ]);

        $user = User::create([
            'name' => $validated['name'],
            'email' => $validated['email'],
            'phone' => $validated['phone'],
            'role' => Role::from($validated['role']),
            'password' => $validated['password'],
        ]);

        $user->forceFill(['email_verified_at' => now()])->save();

        Auth::login($user);

        $this->redirect(route('dashboard', absolute: false), navigate: true);
    }
}; ?>

<div>
    <form wire:submit="register">
        <div>
            <x-input-label :value="__('Como você vai usar o DoctorTurn?')" />
            <div class="mt-2 grid grid-cols-2 gap-2 rounded-lg border border-white/15 bg-teal-950/20 p-1.5 shadow-inner backdrop-blur-xl">
                <label class="relative cursor-pointer overflow-hidden rounded-md border px-3 py-3 transition duration-200 focus-within:ring-2 focus-within:ring-teal-200 focus-within:ring-offset-2 focus-within:ring-offset-teal-900
                    {{ $role === 'gestor' ? 'border-teal-200/70 bg-teal-300/20 text-white shadow-[inset_0_1px_0_rgba(255,255,255,0.22),0_8px_24px_rgba(13,148,136,0.18)]' : 'border-white/10 bg-white/[0.06] text-teal-50 hover:border-white/25 hover:bg-white/[0.1]' }}">
                    <input type="radio" wire:model.live="role" value="gestor" class="sr-only">
                    <span class="block text-[11px] font-semibold uppercase text-teal-200">Gestão</span>
                    <span class="mt-0.5 block text-sm font-semibold">Sou gestor</span>
                    <span class="mt-1 block text-xs leading-4 text-teal-50/65">Crio e organizo escalas</span>
                </label>
                <label class="relative cursor-pointer overflow-hidden rounded-md border px-3 py-3 transition duration-200 focus-within:ring-2 focus-within:ring-teal-200 focus-within:ring-offset-2 focus-within:ring-offset-teal-900
                    {{ $role === 'medico' ? 'border-teal-200/70 bg-teal-300/20 text-white shadow-[inset_0_1px_0_rgba(255,255,255,0.22),0_8px_24px_rgba(13,148,136,0.18)]' : 'border-white/10 bg-white/[0.06] text-teal-50 hover:border-white/25 hover:bg-white/[0.1]' }}">
                    <input type="radio" wire:model.live="role" value="medico" class="sr-only">
                    <span class="block text-[11px] font-semibold uppercase text-teal-200">Assistência</span>
                    <span class="mt-0.5 block text-sm font-semibold">Sou médico</span>
                    <span class="mt-1 block text-xs leading-4 text-teal-50/65">Recebo e acompanho plantões</span>
                </label>
            </div>
            <x-input-error :messages="$errors->get('role')" class="mt-2" />
        </div>

        <!-- Nome -->
        <div class="mt-4">
            <x-input-label for="name" :value="__('Nome completo')" />
            <x-text-input wire:model="name" id="name" class="block mt-1 w-full" type="text" name="name" required autofocus autocomplete="name" />
            <x-input-error :messages="$errors->get('name')" class="mt-2" />
        </div>

        <!-- Email -->
        <div class="mt-4">
            <x-input-label for="email" :value="__('Email')" />
            <x-text-input wire:model="email" id="email" class="block mt-1 w-full" type="email" name="email" required autocomplete="username" />
            <x-input-error :messages="$errors->get('email')" class="mt-2" />
        </div>

        <div class="mt-4">
            <x-input-label for="phone" :value="__('Celular com WhatsApp')" />
            <x-text-input wire:model="phone" id="phone" class="block mt-1 w-full" type="tel" name="phone" required autocomplete="tel" inputmode="tel" placeholder="(11) 96123-4567" />
            <x-input-error :messages="$errors->get('phone')" class="mt-2" />
        </div>

        <!-- Senha -->
        <div class="mt-4">
            <x-input-label for="password" :value="__('Senha')" />
            <x-text-input wire:model="password" id="password" class="block mt-1 w-full" type="password" name="password" required autocomplete="new-password" />
            <x-input-error :messages="$errors->get('password')" class="mt-2" />
        </div>

        <!-- Confirmar senha -->
        <div class="mt-4">
            <x-input-label for="password_confirmation" :value="__('Confirmar senha')" />
            <x-text-input wire:model="password_confirmation" id="password_confirmation" class="block mt-1 w-full" type="password" name="password_confirmation" required autocomplete="new-password" />
            <x-input-error :messages="$errors->get('password_confirmation')" class="mt-2" />
        </div>

        <div class="flex items-center justify-between mt-6">
            <a class="underline text-sm text-gray-600 hover:text-gray-900 rounded-md focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-teal-500" href="{{ route('login') }}" wire:navigate>
                {{ __('Já tenho conta') }}
            </a>

            <x-primary-button>
                {{ __('Criar conta') }}
            </x-primary-button>
        </div>
    </form>
</div>
