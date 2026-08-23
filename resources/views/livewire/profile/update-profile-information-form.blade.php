<?php

use App\Models\User;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Session;
use Illuminate\Validation\Rule;
use Livewire\Volt\Component;

new class extends Component
{
    public string $name = '';
    public string $email = '';
    public string $nickname = '';
    public string $cbo = '';
    public string $council_type = '';
    public string $internal_id = '';
    public string $hired_at = '';

    /**
     * Mount the component.
     */
    public function mount(): void
    {
        $user = Auth::user();
        $this->name = $user->name;
        $this->email = $user->email;
        $this->nickname = $user->nickname ?? '';
        $this->cbo = $user->cbo ?? '';
        $this->council_type = $user->council_type ?? '';
        $this->internal_id = $user->internal_id ?? '';
        $this->hired_at = $user->hired_at?->toDateString() ?? '';
    }

    /**
     * Update the profile information for the currently authenticated user.
     */
    public function updateProfileInformation(): void
    {
        $user = Auth::user();

        $validated = $this->validate([
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'string', 'lowercase', 'email', 'max:255', Rule::unique(User::class)->ignore($user->id)],
            'nickname' => ['nullable', 'string', 'max:100'],
            'cbo' => ['nullable', 'string', 'max:20'],
            'council_type' => ['nullable', 'string', 'max:20'],
            'internal_id' => ['nullable', 'string', 'max:50'],
            'hired_at' => ['nullable', 'date'],
        ]);

        $user->fill($validated);

        if ($user->isDirty('email')) {
            $user->email_verified_at = null;
        }

        $user->save();

        $this->dispatch('profile-updated', name: $user->name);
    }

    /**
     * Send an email verification notification to the current user.
     */
    public function sendVerification(): void
    {
        $user = Auth::user();

        if ($user->hasVerifiedEmail()) {
            $this->redirectIntended(default: route('dashboard', absolute: false));

            return;
        }

        $user->sendEmailVerificationNotification();

        Session::flash('status', 'verification-link-sent');
    }
}; ?>

<section>
    <header>
        <h2 class="text-lg font-medium text-gray-900">
            {{ __('Profile Information') }}
        </h2>

        <p class="mt-1 text-sm text-gray-600">
            {{ __("Update your account's profile information and email address.") }}
        </p>
    </header>

    <form wire:submit="updateProfileInformation" class="mt-6 space-y-6">
        <div>
            <x-input-label for="name" :value="__('Name')" />
            <x-text-input wire:model="name" id="name" name="name" type="text" class="mt-1 block w-full" required autofocus autocomplete="name" />
            <x-input-error class="mt-2" :messages="$errors->get('name')" />
        </div>

        <div>
            <x-input-label for="email" :value="__('Email')" />
            <x-text-input wire:model="email" id="email" name="email" type="email" class="mt-1 block w-full" required autocomplete="username" />
            <x-input-error class="mt-2" :messages="$errors->get('email')" />

            @if (auth()->user() instanceof \Illuminate\Contracts\Auth\MustVerifyEmail && ! auth()->user()->hasVerifiedEmail())
                <div>
                    <p class="text-sm mt-2 text-gray-800">
                        {{ __('Your email address is unverified.') }}

                        <button wire:click.prevent="sendVerification" class="underline text-sm text-gray-600 hover:text-gray-900 rounded-md focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-teal-500">
                            {{ __('Click here to re-send the verification email.') }}
                        </button>
                    </p>

                    @if (session('status') === 'verification-link-sent')
                        <p class="mt-2 font-medium text-sm text-green-600">
                            {{ __('A new verification link has been sent to your email address.') }}
                        </p>
                    @endif
                </div>
            @endif
        </div>

        <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
            <div>
                <x-input-label for="nickname" value="Apelido" />
                <x-text-input wire:model="nickname" id="nickname" type="text" class="mt-1 block w-full" placeholder="Como prefere ser chamado" />
                <x-input-error class="mt-2" :messages="$errors->get('nickname')" />
            </div>
            <div>
                <x-input-label for="cbo" value="Ocupação (CBO)" />
                <x-text-input wire:model="cbo" id="cbo" type="text" class="mt-1 block w-full" placeholder="Ex.: 2251-25" />
                <x-input-error class="mt-2" :messages="$errors->get('cbo')" />
            </div>
        </div>

        <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
            <div>
                <x-input-label for="council_type" value="Tipo de conselho" />
                <select wire:model="council_type" id="council_type" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-teal-500 focus:ring-teal-500">
                    <option value="">Selecione</option>
                    <option value="CRM">CRM</option>
                    <option value="COREN">COREN</option>
                    <option value="CRO">CRO</option>
                    <option value="CRF">CRF</option>
                    <option value="Outro">Outro</option>
                </select>
                <x-input-error class="mt-2" :messages="$errors->get('council_type')" />
            </div>
            <div>
                <x-input-label for="internal_id" value="Identificação interna (matrícula)" />
                <x-text-input wire:model="internal_id" id="internal_id" type="text" class="mt-1 block w-full" placeholder="Ex.: 12345" />
                <x-input-error class="mt-2" :messages="$errors->get('internal_id')" />
            </div>
        </div>

        <div>
            <x-input-label for="hired_at" value="Data de ingresso" />
            <x-text-input wire:model="hired_at" id="hired_at" type="date" class="mt-1 block w-full" />
            <x-input-error class="mt-2" :messages="$errors->get('hired_at')" />
        </div>

        <div class="flex items-center gap-4">
            <x-primary-button>{{ __('Save') }}</x-primary-button>

            <x-action-message class="me-3" on="profile-updated">
                {{ __('Saved.') }}
            </x-action-message>
        </div>
    </form>
</section>
