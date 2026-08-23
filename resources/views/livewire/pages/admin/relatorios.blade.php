<?php

use Livewire\Attributes\Layout;
use Livewire\Volt\Component;

new #[Layout('layouts.admin')] class extends Component
{
    /**
     * @return array<string, mixed>
     */
    public function with(): array
    {
        return [
            'reports' => [
                [
                    'title' => 'Roadmap completo',
                    'desc' => 'Tudo que já temos e tudo que precisamos para as licitações, com cronograma.',
                    'type' => 'roadmap',
                    'icon' => 'map',
                ],
                [
                    'title' => 'Parte financeira',
                    'desc' => 'O que construir e o que contratar na vida real para a parte financeira/fiscal.',
                    'type' => 'financeiro',
                    'icon' => 'cash',
                ],
            ],
        ];
    }
}; ?>

<div class="px-4 py-6 sm:px-6 lg:px-10 lg:py-9">
    <header class="mb-6">
        <p class="text-xs font-semibold uppercase text-teal-700">Administração da plataforma</p>
        <h1 class="mt-1 text-2xl font-semibold text-gray-950">Relatórios</h1>
        <p class="mt-1 text-sm text-gray-500">Gere e baixe relatórios do DoctorTurn em PDF e PowerPoint.</p>
    </header>

    <div class="grid gap-4 sm:grid-cols-2">
        @foreach ($reports as $report)
            <div class="rounded-xl border border-gray-200 bg-white p-5 shadow-sm">
                <div class="flex items-start gap-3">
                    <span class="inline-flex h-10 w-10 shrink-0 items-center justify-center rounded-lg bg-teal-600 text-white">
                        @if ($report['icon'] === 'map')
                            <svg class="h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 20l-5.447-2.724A1 1 0 013 16.382V5.618a1 1 0 011.447-.894L9 7m0 13l6-3m-6 3V7m6 10l4.553 2.276A1 1 0 0021 18.382V7.618a1 1 0 00-.553-.894L15 4m0 13V4m0 0L9 7"/></svg>
                        @else
                            <svg class="h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 9V7a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2m2 4h10a2 2 0 002-2v-6a2 2 0 00-2-2H9a2 2 0 00-2 2v6a2 2 0 002 2zm7-5a2 2 0 11-4 0 2 2 0 014 0z"/></svg>
                        @endif
                    </span>
                    <div class="min-w-0">
                        <p class="font-semibold text-gray-900">{{ $report['title'] }}</p>
                        <p class="mt-0.5 text-xs text-gray-500">{{ $report['desc'] }}</p>
                    </div>
                </div>
                <div class="mt-4 flex gap-2">
                    <a href="{{ route('admin.relatorios.download', ['type' => $report['type'], 'format' => 'pdf']) }}"
                       class="inline-flex items-center gap-1 rounded-md bg-red-600 px-3 py-1.5 text-xs font-medium text-white hover:bg-red-700">
                        <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16v2a2 2 0 002 2h12a2 2 0 002-2v-2M7 10l5 5 5-5M12 15V3"/></svg>
                        PDF
                    </a>
                    <a href="{{ route('admin.relatorios.download', ['type' => $report['type'], 'format' => 'pptx']) }}"
                       class="inline-flex items-center gap-1 rounded-md bg-orange-600 px-3 py-1.5 text-xs font-medium text-white hover:bg-orange-700">
                        <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16v2a2 2 0 002 2h12a2 2 0 002-2v-2M7 10l5 5 5-5M12 15V3"/></svg>
                        PowerPoint
                    </a>
                </div>
            </div>
        @endforeach
    </div>
</div>
