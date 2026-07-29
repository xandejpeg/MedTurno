<?php

use App\Livewire\Actions\Logout;
use Livewire\Volt\Component;

new class extends Component
{
    /**
     * Seleciona o hospital de trabalho atual (persiste em session).
     */
    public function selectHospital(int $hospitalId): void
    {
        $hospital = auth()->user()->managedHospitals()->whereKey($hospitalId)->first();

        if ($hospital !== null) {
            session(['current_hospital_id' => $hospital->id]);
            $this->redirect(request()->header('Referer') ?? route('dashboard'), navigate: true);
        }
    }

    /**
     * Log the current user out of the application.
     */
    public function logout(Logout $logout): void
    {
        $logout();

        $this->redirect('/', navigate: true);
    }
}; ?>

<nav x-data="{ open: false }" class="glass-nav sticky top-0 z-50 border-b border-teal-900/10 shadow-sm shadow-teal-900/5">
    <!-- Primary Navigation Menu -->
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
        <div class="flex justify-between h-16">
            <div class="flex min-w-0">
                <!-- Logo -->
                <div class="shrink-0 flex items-center">
                    <a href="{{ route('dashboard') }}" wire:navigate class="flex items-center gap-2">
                        <x-application-logo class="block h-10 w-auto" />
                        <span class="hidden xl:inline text-lg font-bold tracking-tight"><span class="text-gray-800">Doctor</span><span class="text-lime-600">Turn</span></span>
                    </a>
                </div>

                <!-- Navigation Links -->
                <div class="hidden space-x-3 lg:space-x-5 sm:-my-px sm:ms-4 lg:ms-6 sm:flex overflow-x-auto no-scrollbar">
                    <x-nav-link :href="route('dashboard')" :active="request()->routeIs('dashboard')" wire:navigate>
                        {{ __('Dashboard') }}
                    </x-nav-link>

                    @if (auth()->user()->isMedico())
                        <x-nav-link :href="route('medico.painel')" :active="request()->routeIs('medico.painel')" wire:navigate>
                            Meus plantões
                        </x-nav-link>

                        <x-nav-link :href="route('medico.escala')" :active="request()->routeIs('medico.escala') || request()->routeIs('medico.plantao')" wire:navigate>
                            Minha escala
                        </x-nav-link>
                    @endif

                    @if (auth()->user()->isGestor())
                        <x-nav-link :href="route('gestor.hospitais')" :active="request()->routeIs('gestor.hospitais')" wire:navigate>
                            Hospitais
                        </x-nav-link>

                        <x-nav-link :href="route('gestor.equipe')" :active="request()->routeIs('gestor.equipe')" wire:navigate>
                            Equipe
                        </x-nav-link>

                        <x-nav-link :href="route('gestor.convites')" :active="request()->routeIs('gestor.convites')" wire:navigate>
                            Convites
                        </x-nav-link>

                        <x-nav-link :href="route('gestor.escalas')" :active="request()->routeIs('gestor.escalas') || request()->routeIs('gestor.escalas.nova') || request()->routeIs('gestor.escala')" wire:navigate>
                            Escalas
                        </x-nav-link>

                        <x-nav-link :href="route('gestor.patch-notes')" :active="request()->routeIs('gestor.patch-notes')" wire:navigate>
                            Patch Notes
                        </x-nav-link>
                    @endif
                </div>
            </div>

            <!-- Settings Dropdown -->
            <div class="hidden sm:flex sm:items-center sm:ms-3 shrink-0">
                @php($unreadNotifications = auth()->user()->notifications()->whereNull('read_at')->count())

                <a href="{{ route('notificacoes') }}" wire:navigate wire:poll.120s class="relative me-3 p-2 text-gray-500 hover:text-gray-700" title="Notificações">
                    <svg class="h-5 w-5" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M15 17h5l-1.405-1.405A2.032 2.032 0 0118 14.158V11a6.002 6.002 0 00-4-5.659V5a2 2 0 10-4 0v.341C7.67 6.165 6 8.388 6 11v3.159c0 .538-.214 1.055-.595 1.436L4 17h5m6 0v1a3 3 0 11-6 0v-1m6 0H9"/></svg>
                    @if ($unreadNotifications > 0)
                        <span class="absolute top-0 right-0 inline-flex items-center justify-center min-w-4 h-4 px-1 text-[10px] font-bold text-white bg-red-500 rounded-full">{{ $unreadNotifications }}</span>
                    @endif
                </a>

                @php($managed = auth()->user()->managedHospitals()->orderBy('name')->get())
                @php($current = currentHospital())

                @if ($managed->count() > 1)
                    <x-dropdown align="right" width="60">
                        <x-slot name="trigger">
                            <button class="inline-flex items-center px-3 py-2 border border-gray-300 text-sm leading-4 font-medium rounded-md text-gray-700 bg-white hover:bg-gray-50 focus:outline-none transition ease-in-out duration-150 me-3">
                                <svg class="h-4 w-4 me-1 text-gray-500" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M3 21h18M5 21V5a2 2 0 012-2h10a2 2 0 012 2v16M9 8h1m4 0h1M9 12h1m4 0h1M9 16h1m4 0h1"/></svg>
                                {{ $current?->name ?? 'Selecionar hospital' }}
                                <svg class="fill-current h-4 w-4 ms-1" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20"><path fill-rule="evenodd" d="M5.293 7.293a1 1 0 011.414 0L10 10.586l3.293-3.293a1 1 0 111.414 1.414l-4 4a1 1 0 01-1.414 0l-4-4a1 1 0 010-1.414z" clip-rule="evenodd" /></svg>
                            </button>
                        </x-slot>

                        <x-slot name="content">
                            @foreach ($managed as $hospital)
                                <button type="button" wire:click="selectHospital({{ $hospital->id }})" class="block w-full px-4 py-2 text-start text-sm leading-5 {{ $current?->id === $hospital->id ? 'text-teal-700 font-semibold bg-teal-50' : 'text-gray-700' }} hover:bg-gray-100 focus:outline-none focus:bg-gray-100 transition duration-150 ease-in-out">
                                    @if ($current?->id === $hospital->id) ✓ @endif
                                    {{ $hospital->name }}
                                </button>
                            @endforeach
                        </x-slot>
                    </x-dropdown>
                @elseif ($current !== null)
                    <span class="text-sm text-gray-500 me-3">{{ $current->name }}</span>
                @endif

                <x-dropdown align="right" width="48">
                    <x-slot name="trigger">
                        <button class="inline-flex items-center px-3 py-2 border border-transparent text-sm leading-4 font-medium rounded-md text-gray-500 bg-white hover:text-gray-700 focus:outline-none transition ease-in-out duration-150">
                            <div x-data="{{ json_encode(['name' => auth()->user()->name]) }}" x-text="name" x-on:profile-updated.window="name = $event.detail.name"></div>

                            <div class="ms-1">
                                <svg class="fill-current h-4 w-4" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20">
                                    <path fill-rule="evenodd" d="M5.293 7.293a1 1 0 011.414 0L10 10.586l3.293-3.293a1 1 0 111.414 1.414l-4 4a1 1 0 01-1.414 0l-4-4a1 1 0 010-1.414z" clip-rule="evenodd" />
                                </svg>
                            </div>
                        </button>
                    </x-slot>

                    <x-slot name="content">
                        <x-dropdown-link :href="route('profile')" wire:navigate>
                            {{ __('Profile') }}
                        </x-dropdown-link>

                        <!-- Authentication -->
                        <button wire:click="logout" class="w-full text-start">
                            <x-dropdown-link>
                                {{ __('Log Out') }}
                            </x-dropdown-link>
                        </button>
                    </x-slot>
                </x-dropdown>
            </div>

            <!-- Hamburger -->
            <div class="-me-2 flex items-center sm:hidden">
                <button @click="open = ! open" class="inline-flex items-center justify-center p-2 rounded-md text-gray-400 hover:text-gray-500 hover:bg-gray-100 focus:outline-none focus:bg-gray-100 focus:text-gray-500 transition duration-150 ease-in-out">
                    <svg class="h-6 w-6" stroke="currentColor" fill="none" viewBox="0 0 24 24">
                        <path :class="{'hidden': open, 'inline-flex': ! open }" class="inline-flex" stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 12h16M4 18h16" />
                        <path :class="{'hidden': ! open, 'inline-flex': open }" class="hidden" stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
                    </svg>
                </button>
            </div>
        </div>
    </div>

    <!-- Responsive Navigation Menu -->
    <div :class="{'block': open, 'hidden': ! open}" class="hidden sm:hidden">
        <div class="pt-2 pb-3 space-y-1">
            <x-responsive-nav-link :href="route('dashboard')" :active="request()->routeIs('dashboard')" wire:navigate>
                {{ __('Dashboard') }}
            </x-responsive-nav-link>

            @if (auth()->user()->isMedico())
                <x-responsive-nav-link :href="route('medico.painel')" :active="request()->routeIs('medico.painel')" wire:navigate>
                    Meus plantões
                </x-responsive-nav-link>

                <x-responsive-nav-link :href="route('medico.escala')" :active="request()->routeIs('medico.escala')" wire:navigate>
                    Minha escala
                </x-responsive-nav-link>
            @endif

            <x-responsive-nav-link :href="route('notificacoes')" :active="request()->routeIs('notificacoes')" wire:navigate>
                Notificações
            </x-responsive-nav-link>

            @if (auth()->user()->isGestor())
                <x-responsive-nav-link :href="route('gestor.hospitais')" :active="request()->routeIs('gestor.hospitais')" wire:navigate>
                    Hospitais
                </x-responsive-nav-link>

                <x-responsive-nav-link :href="route('gestor.equipe')" :active="request()->routeIs('gestor.equipe')" wire:navigate>
                    Equipe
                </x-responsive-nav-link>

                <x-responsive-nav-link :href="route('gestor.convites')" :active="request()->routeIs('gestor.convites')" wire:navigate>
                    Convites
                </x-responsive-nav-link>

                <x-responsive-nav-link :href="route('gestor.escalas')" :active="request()->routeIs('gestor.escalas') || request()->routeIs('gestor.escalas.nova')" wire:navigate>
                    Escalas
                </x-responsive-nav-link>

                <x-responsive-nav-link :href="route('gestor.patch-notes')" :active="request()->routeIs('gestor.patch-notes')" wire:navigate>
                    Patch Notes
                </x-responsive-nav-link>
            @endif
        </div>

        <!-- Responsive Settings Options -->
        <div class="pt-4 pb-1 border-t border-gray-200">
            <div class="px-4">
                <div class="font-medium text-base text-gray-800" x-data="{{ json_encode(['name' => auth()->user()->name]) }}" x-text="name" x-on:profile-updated.window="name = $event.detail.name"></div>
                <div class="font-medium text-sm text-gray-500">{{ auth()->user()->email }}</div>
            </div>

            <div class="mt-3 space-y-1">
                <x-responsive-nav-link :href="route('profile')" wire:navigate>
                    {{ __('Profile') }}
                </x-responsive-nav-link>

                <!-- Authentication -->
                <button wire:click="logout" class="w-full text-start">
                    <x-responsive-nav-link>
                        {{ __('Log Out') }}
                    </x-responsive-nav-link>
                </button>
            </div>
        </div>
    </div>
</nav>
