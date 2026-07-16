<?php

use App\Enums\ScheduleStatus;
use App\Enums\ShiftStatus;
use App\Models\Shift;
use Livewire\Attributes\Layout;
use Livewire\Volt\Component;

new #[Layout('layouts.app')] class extends Component
{
    /**
     * @return array<string, mixed>
     */
    public function with(): array
    {
        $user = auth()->user();

        $published = fn ($q) => $q->where('status', ScheduleStatus::Publicada);

        $next = Shift::query()
            ->where('user_id', $user->id)
            ->whereHas('schedule', $published)
            ->whereIn('status', [ShiftStatus::Pendente, ShiftStatus::Confirmado])
            ->where('starts_at', '>', now())
            ->orderBy('starts_at')
            ->with(['hospital', 'template'])
            ->first();

        $pending = Shift::query()
            ->where('user_id', $user->id)
            ->whereHas('schedule', $published)
            ->where('status', ShiftStatus::Pendente)
            ->where('starts_at', '>', now())
            ->orderBy('starts_at')
            ->with('hospital')
            ->limit(10)
            ->get();

        $monthTotal = Shift::query()
            ->where('user_id', $user->id)
            ->whereHas('schedule', $published)
            ->whereIn('status', [ShiftStatus::Pendente, ShiftStatus::Confirmado, ShiftStatus::Concluido])
            ->whereYear('date', now()->year)
            ->whereMonth('date', now()->month)
            ->sum('amount');

        return [
            'next' => $next,
            'pending' => $pending,
            'monthTotal' => (float) $monthTotal,
        ];
    }
}; ?>

<div>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 leading-tight">Meus plantões</h2>
    </x-slot>

    <div class="py-12 pb-24 sm:pb-12">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8 space-y-6">

            <div class="grid grid-cols-1 sm:grid-cols-2 gap-6">
                <div class="bg-white shadow-sm sm:rounded-lg p-6">
                    <p class="text-sm text-gray-500 mb-1">Próximo plantão</p>
                    @if ($next)
                        <p class="text-2xl font-semibold text-gray-900">
                            {{ $next->starts_at->format('d/m') }} · {{ $next->starts_at->format('H:i') }}–{{ $next->ends_at->format('H:i') }}
                        </p>
                        <p class="text-sm text-gray-500 mt-1">
                            {{ $next->hospital->name }}
                            · {{ $next->status->label() }}
                            · {{ $next->starts_at->diffForHumans() }}
                        </p>
                        <a href="{{ route('medico.plantao', $next) }}" wire:navigate class="inline-block mt-3 text-sm text-teal-600 hover:underline">Ver detalhe →</a>
                    @else
                        <p class="text-gray-400">Nenhum plantão futuro.</p>
                    @endif
                </div>

                <div class="bg-white shadow-sm sm:rounded-lg p-6">
                    <p class="text-sm text-gray-500 mb-1">A receber em {{ now()->format('m/Y') }}</p>
                    <p class="text-2xl font-semibold text-gray-900">R$ {{ number_format($monthTotal, 2, ',', '.') }}</p>
                    <p class="text-sm text-gray-400 mt-1">Plantões pendentes, confirmados e concluídos do mês.</p>
                </div>
            </div>

            <div class="bg-white shadow-sm sm:rounded-lg overflow-hidden">
                <div class="p-6 border-b border-gray-100">
                    <h3 class="font-medium text-gray-900">Pendentes de confirmação</h3>
                </div>
                @forelse ($pending as $shift)
                    <div class="flex items-center justify-between p-6 {{ ! $loop->last ? 'border-b border-gray-100' : '' }}">
                        <div>
                            <p class="font-medium text-gray-900">
                                {{ $shift->starts_at->format('d/m/Y') }} · {{ $shift->starts_at->format('H:i') }}–{{ $shift->ends_at->format('H:i') }}
                            </p>
                            <p class="text-sm text-gray-500">{{ $shift->hospital->name }}</p>
                        </div>
                        <a href="{{ route('medico.plantao', $shift) }}" wire:navigate>
                            <x-primary-button type="button">Ver</x-primary-button>
                        </a>
                    </div>
                @empty
                    <p class="p-6 text-gray-500">Nada pendente. 🎉</p>
                @endforelse
            </div>
        </div>
    </div>
</div>
