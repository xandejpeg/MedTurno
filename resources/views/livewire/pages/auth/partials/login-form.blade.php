<div>
    <x-auth-session-status class="mb-4" :status="session('status')" />

    <form wire:submit="login">
        <div>
            <x-input-label for="email" :value="__('Email')" />
            <x-text-input wire:model="form.email" id="email" class="block mt-1 w-full" type="email" name="email" required autofocus autocomplete="username" />
            <x-input-error :messages="$errors->get('form.email')" class="mt-2" />
        </div>

        <div class="mt-4">
            <x-input-label for="password" :value="__('Password')" />
            <div x-data="{ showPassword: false }" class="relative mt-1">
                <x-text-input wire:model="form.password" id="password" class="block w-full pr-20" x-bind:type="showPassword ? 'text' : 'password'" name="password" required autocomplete="current-password" />
                <button type="button" x-on:click="showPassword = ! showPassword" x-bind:aria-label="showPassword ? 'Ocultar senha' : 'Mostrar senha'" class="absolute inset-y-0 right-0 px-3 text-xs font-semibold text-teal-700 hover:text-teal-900 focus:outline-none focus:ring-2 focus:ring-inset focus:ring-teal-500">
                    <span x-text="showPassword ? 'Ocultar' : 'Mostrar'">Mostrar</span>
                </button>
            </div>
            <x-input-error :messages="$errors->get('form.password')" class="mt-2" />
        </div>

        <div class="block mt-4">
            <label for="remember" class="inline-flex items-center">
                <input wire:model="form.remember" id="remember" type="checkbox" class="rounded border-gray-300 text-teal-600 shadow-sm focus:ring-teal-500" name="remember">
                <span class="ms-2 text-sm text-gray-600">{{ __('Remember me') }}</span>
            </label>
        </div>

        <div class="flex flex-col items-center justify-center gap-3 mt-5">
            <x-primary-button>
                {{ __('Log in') }}
            </x-primary-button>

            @if (Route::has('password.request'))
                <a class="underline text-sm text-gray-600 hover:text-gray-900 rounded-md focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-teal-500" href="{{ route('password.request') }}" wire:navigate>
                    {{ __('Forgot your password?') }}
                </a>
            @endif
        </div>
    </form>

    <p class="mt-6 text-center text-sm text-gray-600">
        @if ($adminLogin)
            {{ __('Não é admin?') }}
            <a href="{{ route('login') }}" wire:navigate class="font-semibold text-teal-600 hover:text-teal-700 underline">
                {{ __('Entrar como usuário/gestor') }}
            </a>
        @elseif (Route::has('register'))
            {{ __('Ainda não tem conta?') }}
            <a href="{{ route('register') }}" wire:navigate class="font-semibold text-teal-600 hover:text-teal-700 underline">
                {{ __('Cadastre-se') }}
            </a>
        @endif
    </p>
</div>