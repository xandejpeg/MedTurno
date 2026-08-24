<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1">
        <meta name="csrf-token" content="{{ csrf_token() }}">

        <title>{{ config('app.name', 'DoctorTurn') }}</title>
        <link rel="icon" type="image/png" href="{{ asset('images/logo.png') }}">

        {{-- PWA — instalável na tela inicial --}}
        <link rel="manifest" href="{{ asset('manifest.json') }}">
        <meta name="theme-color" content="#0f766e">
        <link rel="apple-touch-icon" href="{{ asset('images/apple-touch-icon.png') }}">
        <meta name="apple-mobile-web-app-capable" content="yes">
        <meta name="mobile-web-app-capable" content="yes">
        <meta name="apple-mobile-web-app-status-bar-style" content="default">
        <meta name="apple-mobile-web-app-title" content="DoctorTurn">
        <link rel="preconnect" href="https://fonts.bunny.net">
        <link href="https://fonts.bunny.net/css?family=figtree:400,500,600,700&display=swap" rel="stylesheet" />

        <!-- Scripts -->
        @vite(['resources/css/app.css', 'resources/js/app.js'])
    </head>
    <body class="font-sans antialiased">
        <x-impersonation-banner />

        <div class="min-h-screen bg-gradient-to-br from-teal-50 via-gray-50 to-lime-50/60">
            <livewire:layout.navigation />

            <!-- Page Heading -->
            @if (isset($header))
                <header class="bg-white/60 backdrop-blur-sm shadow-sm shadow-teal-900/5">
                    <div class="max-w-7xl mx-auto py-6 px-4 sm:px-6 lg:px-8">
                        {{ $header }}
                        <div class="mt-2 h-1 w-16 rounded-full bg-gradient-to-r from-teal-500 to-lime-500"></div>
                    </div>
                </header>
            @endif

            <!-- Page Content -->
            <main>
                {{ $slot }}
            </main>

            @auth
                @if (auth()->user()->doctorHospitals()->exists())
                    <!-- Bottom-nav mobile (médico) -->
                    <nav class="sm:hidden fixed bottom-0 inset-x-0 glass-bottom border-t border-teal-900/10 grid grid-cols-4 text-xs z-40">
                        <a href="{{ route('medico.painel') }}" wire:navigate class="flex flex-col items-center py-2 {{ request()->routeIs('medico.painel') ? 'text-teal-600' : 'text-gray-500' }}">
                            <svg class="h-5 w-5 mb-0.5" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M3 12l9-9 9 9M5 10v10a1 1 0 001 1h4v-6h4v6h4a1 1 0 001-1V10"/></svg>
                            Painel
                        </a>
                        <a href="{{ route('medico.escala') }}" wire:navigate class="flex flex-col items-center py-2 {{ request()->routeIs('medico.escala') || request()->routeIs('medico.plantao') ? 'text-teal-600' : 'text-gray-500' }}">
                            <svg class="h-5 w-5 mb-0.5" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"/></svg>
                            Escala
                        </a>
                        <a href="{{ route('medico.trocas') }}" wire:navigate class="flex flex-col items-center py-2 {{ request()->routeIs('medico.trocas') ? 'text-teal-600' : 'text-gray-500' }}">
                            <svg class="h-5 w-5 mb-0.5" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M8 7h12m0 0l-4-4m4 4l-4 4m0 6H4m0 0l4 4m-4-4l4-4"/></svg>
                            Trocas
                        </a>
                        <a href="{{ route('notificacoes') }}" wire:navigate class="flex flex-col items-center py-2 relative {{ request()->routeIs('notificacoes') ? 'text-teal-600' : 'text-gray-500' }}">
                            <svg class="h-5 w-5 mb-0.5" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M15 17h5l-1.405-1.405A2.032 2.032 0 0118 14.158V11a6.002 6.002 0 00-4-5.659V5a2 2 0 10-4 0v.341C7.67 6.165 6 8.388 6 11v3.159c0 .538-.214 1.055-.595 1.436L4 17h5m6 0v1a3 3 0 11-6 0v-1m6 0H9"/></svg>
                            Notificações
                            @php($unreadBottom = auth()->user()->notifications()->whereNull('read_at')->count())
                            @if ($unreadBottom > 0)
                                <span class="absolute top-1 right-1/2 translate-x-4 inline-flex items-center justify-center min-w-4 h-4 px-1 text-[10px] font-bold text-white bg-red-500 rounded-full">{{ $unreadBottom }}</span>
                            @endif
                        </a>
                    </nav>
                @endif
            @endauth
        </div>
    </body>
</html>
