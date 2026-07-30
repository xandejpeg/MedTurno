<?php

use App\Services\ScheduleService;
use Livewire\Attributes\Layout;
use Livewire\Volt\Component;

new #[Layout('layouts.app')] class extends Component
{
    public string $month = '';

    public function mount(): void
    {
        $this->month = now()->addMonth()->format('Y-m');
    }

    public function save(ScheduleService $service): void
    {
        $this->validate([
            'month' => ['required', 'date_format:Y-m'],
        ]);

        $hospital = currentHospital();
        abort_unless($hospital !== null, 404);
        $this->authorize('update', $hospital);

        [$year, $month] = array_map('intval', explode('-', $this->month));

        try {
            $schedule = $service->createMonthly($hospital, $year, $month, auth()->user());
        } catch (\InvalidArgumentException $e) {
            $this->addError('month', $e->getMessage());

            return;
        }

        session()->flash('escala-criada', "Escala de {$schedule->monthLabel()} criada com {$schedule->shifts()->count()} plantões.");

        $this->redirectRoute('gestor.escala.montar', $schedule, navigate: true);
    }
}; ?>

<div>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 leading-tight">Nova escala</h2>
    </x-slot>

    <div class="py-12">
        <div class="max-w-2xl mx-auto sm:px-6 lg:px-8 space-y-6">

            <a href="{{ route('gestor.escalas') }}" wire:navigate class="text-sm text-gray-500 hover:text-gray-700">← Escalas</a>

            <div class="bg-white shadow-sm sm:rounded-lg p-6">
                <h3 class="text-lg font-medium text-gray-900 mb-1">Criar escala mensal</h3>
                <p class="text-sm text-gray-500 mb-4">
                    O sistema cria os plantões diurnos e noturnos do mês. Depois você escolhe os médicos e publica.
                </p>

                <form wire:submit="save" class="space-y-4">
                    <div>
                        <x-input-label for="month" value="Mês *" />
                        <x-text-input wire:model="month" id="month" class="block mt-1 w-full" type="month" required />
                        <x-input-error :messages="$errors->get('month')" class="mt-2" />
                    </div>

                    <div class="flex gap-3">
                        <x-primary-button>Criar escala e montar plantões</x-primary-button>
                        <a href="{{ route('gestor.escalas') }}" wire:navigate>
                            <x-secondary-button type="button">Cancelar</x-secondary-button>
                        </a>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>
