<?php

use App\Services\ScheduleService;
use Livewire\Attributes\Layout;
use Livewire\Volt\Component;

new #[Layout('layouts.app')] class extends Component
{
    public string $boardId = '';

    public string $month = '';

    public function mount(): void
    {
        $this->month = now()->addMonth()->format('Y-m');
    }

    /**
     * @return array<string, mixed>
     */
    public function with(): array
    {
        $hospital = currentHospital();

        return [
            'hospital' => $hospital,
            'boards' => $hospital
                ? $hospital->shiftBoards()->where('active', true)->withCount('templates')->orderBy('name')->get()
                : collect(),
        ];
    }

    public function save(ScheduleService $service): void
    {
        $this->validate([
            'boardId' => 'required|integer',
            'month' => ['required', 'date_format:Y-m'],
        ]);

        $hospital = currentHospital();
        abort_unless($hospital !== null, 404);
        $this->authorize('update', $hospital);

        $board = $hospital->shiftBoards()->where('active', true)->whereKey((int) $this->boardId)->firstOrFail();

        [$year, $month] = array_map('intval', explode('-', $this->month));

        try {
            $schedule = $service->createDraft($board, $year, $month, auth()->user());
        } catch (\InvalidArgumentException $e) {
            $this->addError('month', $e->getMessage());

            return;
        }

        session()->flash('escala-criada', "Escala {$board->name} — {$schedule->monthLabel()} criada com {$schedule->shifts()->count()} plantões.");

        $this->redirectRoute('gestor.escalas', navigate: true);
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
                <h3 class="text-lg font-medium text-gray-900 mb-1">Gerar escala mensal</h3>
                <p class="text-sm text-gray-500 mb-4">
                    A escala nasce em rascunho, com plantões dos turnos do quadro e médicos das recorrências ativas já pré-preenchidos.
                </p>

                <form wire:submit="save" class="space-y-4">
                    <div>
                        <x-input-label for="boardId" value="Quadro *" />
                        <select wire:model="boardId" id="boardId" class="block mt-1 w-full border-gray-300 focus:border-teal-500 focus:ring-teal-500 rounded-md shadow-sm">
                            <option value="">Selecione…</option>
                            @foreach ($boards as $board)
                                <option value="{{ $board->id }}">{{ $board->name }} ({{ $board->templates_count }} turnos)</option>
                            @endforeach
                        </select>
                        <x-input-error :messages="$errors->get('boardId')" class="mt-2" />
                    </div>

                    <div>
                        <x-input-label for="month" value="Mês *" />
                        <x-text-input wire:model="month" id="month" class="block mt-1 w-full" type="month" required />
                        <x-input-error :messages="$errors->get('month')" class="mt-2" />
                    </div>

                    <div class="flex gap-3">
                        <x-primary-button>Gerar escala</x-primary-button>
                        <a href="{{ route('gestor.escalas') }}" wire:navigate>
                            <x-secondary-button type="button">Cancelar</x-secondary-button>
                        </a>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>
