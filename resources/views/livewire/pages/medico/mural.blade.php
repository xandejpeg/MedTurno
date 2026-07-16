<?php

use App\Enums\InterestStatus;
use App\Enums\ScheduleStatus;
use App\Enums\ShiftStatus;
use App\Models\Shift;
use App\Services\TransferService;
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

        $shifts = Shift::query()
            ->where('status', ShiftStatus::Disponivel)
            ->where('user_id', '!=', $user->id)
            ->whereHas('schedule', fn ($q) => $q
                ->where('status', ScheduleStatus::Publicada)
                ->whereHas('board.doctors', fn ($b) => $b->whereKey($user->id)))
            ->where('date', '>=', today())
            ->with(['hospital', 'doctor', 'schedule.board'])
            ->orderBy('date')
            ->orderBy('starts_at')
            ->get();

        $myInterests = $user->shiftInterests()
            ->where('status', InterestStatus::Pendente)
            ->pluck('shift_id');

        return [
            'shifts' => $shifts,
            'myInterests' => $myInterests,
        ];
    }

    public function interest(int $shiftId, TransferService $service): void
    {
        try {
            $service->expressInterest(Shift::findOrFail($shiftId), auth()->user());
            session()->flash('status', 'Interesse registrado! O gestor vai decidir.');
        } catch (\InvalidArgumentException $e) {
            $this->addError('mural', $e->getMessage());
        }
    }

    public function withdraw(int $shiftId, TransferService $service): void
    {
        try {
            $service->withdrawInterest(Shift::findOrFail($shiftId), auth()->user());
            session()->flash('status', 'Interesse retirado.');
        } catch (\InvalidArgumentException $e) {
            $this->addError('mural', $e->getMessage());
        }
    }
}; ?>

<div>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 leading-tight">Mural de plantões</h2>
    </x-slot>

    <div class="py-12 pb-24 sm:pb-12">
        <div class="max-w-4xl mx-auto sm:px-6 lg:px-8 space-y-6">

            @if (session('status'))
                <div class="bg-green-50 border border-green-200 text-green-800 rounded-lg p-4 text-sm">
                    {{ session('status') }}
                </div>
            @endif

            @error('mural')
                <div class="bg-red-50 border border-red-200 text-red-800 rounded-lg p-4 text-sm">{{ $message }}</div>
            @enderror

            <div class="bg-white shadow-sm sm:rounded-lg overflow-hidden">
                <div class="p-6 border-b border-gray-100">
                    <h3 class="font-medium text-gray-900">Plantões disponíveis dos seus quadros</h3>
                </div>
                @forelse ($shifts as $shift)
                    <div class="p-6 {{ ! $loop->last ? 'border-b border-gray-100' : '' }}">
                        <div class="flex flex-wrap items-center justify-between gap-4">
                            <div>
                                <p class="font-medium text-gray-900">
                                    {{ $shift->date->format('d/m/Y') }} · {{ $shift->starts_at->format('H:i') }}–{{ $shift->ends_at->format('H:i') }}
                                </p>
                                <p class="text-sm text-gray-500">
                                    {{ $shift->hospital->name }} · {{ $shift->schedule->board->name }} · anunciado por {{ $shift->doctor?->name }}
                                </p>
                            </div>
                            <div>
                                @if ($myInterests->contains($shift->id))
                                    <x-secondary-button wire:click="withdraw({{ $shift->id }})" type="button">Retirar interesse</x-secondary-button>
                                @else
                                    <x-primary-button wire:click="interest({{ $shift->id }})" type="button">Tenho interesse</x-primary-button>
                                @endif
                            </div>
                        </div>
                    </div>
                @empty
                    <p class="p-6 text-gray-500">Nenhum plantão disponível no mural.</p>
                @endforelse
            </div>
        </div>
    </div>
</div>
