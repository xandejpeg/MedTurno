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
    <body class="font-sans text-gray-900 antialiased">
        <div class="auth-bg relative min-h-screen flex flex-col sm:justify-center items-center pt-10 sm:pt-0 overflow-hidden">
            {{-- Blobs decorativos --}}
            <div class="anim-blob pointer-events-none absolute -top-32 -left-32 w-96 h-96 rounded-full bg-teal-400/20 blur-3xl"></div>
            <div class="anim-blob-2 pointer-events-none absolute top-1/3 -right-24 w-80 h-80 rounded-full bg-lime-400/15 blur-3xl"></div>
            <div class="anim-blob-3 pointer-events-none absolute -bottom-24 left-1/4 w-72 h-72 rounded-full bg-cyan-400/15 blur-3xl"></div>

            <div class="anim-logo relative z-10">
                <a href="/" wire:navigate>
                    <img src="{{ asset($loginImage ?? 'images/logo-name.png') }}" alt="{{ $loginImageAlt ?? 'DoctorTurn — Conectando quem cuida' }}" class="h-24 sm:h-28 drop-shadow-lg">
                </a>
            </div>

            <div class="anim-card auth-card relative z-10 w-full sm:max-w-lg mt-8 px-7 py-7 sm:px-10 sm:py-9 bg-white/10 backdrop-blur-2xl border border-white/20 shadow-2xl shadow-black/30 overflow-hidden sm:rounded-2xl">
                {{ $slot }}
            </div>

            <p class="anim-footer relative z-10 mt-6 mb-8 text-xs text-teal-100/60">DoctorTurn — conectando quem cuida.</p>
        </div>
    </body>
</html>
