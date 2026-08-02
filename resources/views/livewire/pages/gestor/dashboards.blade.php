<?php

use Livewire\Attributes\Layout;
use Livewire\Volt\Component;

new #[Layout('layouts.app')] class extends Component
{
    public function mount(): void
    {
        abort_unless(auth()->user()->isGestor(), 403);
    }

    /**
     * @return array<string, mixed>
     */
    public function with(): array
    {
        $hospital = currentHospital();

        return [
            'hospital' => $hospital,
            'metabaseUrl' => config('services.metabase.url'),
            'metabaseDashboard' => config('services.metabase.dashboard'),
        ];
    }
}; ?>

<div>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 leading-tight">Dashboards</h2>
        <p class="text-sm text-gray-500">{{ $hospital?->name }}</p>
    </x-slot>

    <div class="py-6">
        <div class="max-w-6xl mx-auto sm:px-6 lg:px-8 space-y-6">
            @if (config('services.metabase.url') && config('services.metabase.dashboard'))
                <div class="bg-white shadow-sm sm:rounded-lg overflow-hidden">
                    <iframe
                        src="{{ config('services.metabase.url') }}/public/dashboard/{{ config('services.metabase.dashboard') }}"
                        frameborder="0"
                        width="100%"
                        height="800"
                        allowtransparency
                        class="block w-full"
                    ></iframe>
                </div>
            @else
                <div class="bg-white shadow-sm sm:rounded-lg p-8 text-center">
                    <p class="text-sm font-medium text-gray-700">Metabase não configurado</p>
                    <p class="mt-1 text-xs text-gray-500">Configure <code class="rounded bg-gray-100 px-1">METABASE_URL</code> e <code class="rounded bg-gray-100 px-1">METABASE_DASHBOARD</code> no ambiente para embutir os dashboards personalizados aqui.</p>
                </div>
            @endif
        </div>
    </div>
</div>
