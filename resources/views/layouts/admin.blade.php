<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1">
        <meta name="csrf-token" content="{{ csrf_token() }}">

        <title>Admin · {{ config('app.name', 'DoctorTurn') }}</title>
        <link rel="icon" type="image/png" href="{{ asset('images/logo.png') }}">
        <link rel="preconnect" href="https://fonts.bunny.net">
        <link href="https://fonts.bunny.net/css?family=figtree:400,500,600,700&display=swap" rel="stylesheet" />
        @vite(['resources/css/app.css', 'resources/js/app.js'])
    </head>
    <body class="min-h-screen bg-[#f4f7f6] font-sans text-gray-900 antialiased">
        <div class="min-h-screen lg:grid lg:grid-cols-[240px_1fr]">
            <aside class="border-b border-teal-950/10 bg-[#073f3d] text-white lg:min-h-screen lg:border-b-0 lg:border-r">
                <div class="flex h-16 items-center justify-between px-5 lg:h-20">
                    <a href="{{ route('admin.dashboard') }}" wire:navigate class="font-semibold tracking-wide">DoctorTurn <span class="text-teal-200">Admin</span></a>
                    <livewire:layout.admin-navigation />
                </div>
                <nav class="border-t border-white/10 px-3 py-3 lg:py-5">
                    <a href="{{ route('admin.dashboard') }}" wire:navigate
                              class="block rounded-md px-3 py-2 text-sm font-medium {{ request()->routeIs('admin.dashboard', 'admin.managers.*', 'admin.schedules.*') ? 'bg-white/15 text-white' : 'text-teal-50/75 hover:bg-white/10 hover:text-white' }}">
                        Dashboard
                    </a>
                    <a href="{{ route('admin.patch-notes') }}" wire:navigate
                              class="mt-1 block rounded-md px-3 py-2 text-sm font-medium {{ request()->routeIs('admin.patch-notes') ? 'bg-white/15 text-white' : 'text-teal-50/75 hover:bg-white/10 hover:text-white' }}">
                        Patch Notes
                    </a>
                    <a href="{{ route('admin.central') }}" wire:navigate
                              class="mt-1 block rounded-md px-3 py-2 text-sm font-medium {{ request()->routeIs('admin.central') ? 'bg-white/15 text-white' : 'text-teal-50/75 hover:bg-white/10 hover:text-white' }}">
                        Central de Controle
                    </a>
                </nav>
            </aside>

            <main class="min-w-0">
                {{ $slot }}
            </main>
        </div>
    </body>
</html>