<?php

use App\Enums\Role;
use App\Models\Announcement;
use App\Models\Schedule;
use App\Services\NotificationService;
use Livewire\Attributes\Layout;
use Livewire\Volt\Component;

new #[Layout('layouts.app')] class extends Component
{
    public string $title = '';

    public string $body = '';

    public string $scope = 'hospital'; // hospital, schedule

    public string $scheduleId = '';

    public function mount(): void
    {
        abort_unless(auth()->user()->isGestor(), 403);
    }

    public function publish(NotificationService $notifications): void
    {
        $hospital = currentHospital();
        abort_unless($hospital !== null, 403);

        $data = $this->validate([
            'title' => ['required', 'string', 'max:120'],
            'body' => ['required', 'string', 'max:1000'],
            'scope' => ['required', 'in:hospital,schedule'],
            'scheduleId' => ['nullable', 'integer', 'required_if:scope,schedule'],
        ], attributes: ['title' => 'título', 'body' => 'recado', 'scheduleId' => 'escala']);

        $schedule = $data['scope'] === 'schedule'
            ? Schedule::where('hospital_id', $hospital->id)->findOrFail((int) $data['scheduleId'])
            : null;

        $announcement = Announcement::create([
            'hospital_id' => $hospital->id,
            'schedule_id' => $schedule?->id,
            'created_by' => auth()->id(),
            'title' => $data['title'],
            'body' => $data['body'],
        ]);

        // Notifica os médicos do hospital (ou da escala).
        $recipients = $schedule !== null
            ? \App\Models\User::whereIn('id', $schedule->shifts()->whereNotNull('user_id')->distinct()->pluck('user_id'))->get()
            : \App\Models\User::whereHas('hospitalMemberships', fn ($q) => $q->where('hospital_id', $hospital->id)->where('role', Role::Medico)->where('active', true))->get();

        foreach ($recipients as $doctor) {
            $notifications->send(
                $doctor,
                'mural',
                $data['title'],
                $data['body'],
                route('medico.painel', absolute: false),
                $hospital,
            );
        }

        $this->reset(['title', 'body', 'scope', 'scheduleId']);
        $this->scope = 'hospital';
        session()->flash('status', "Recado publicado para {$recipients->count()} médico(s).");
    }

    public function remove(int $id): void
    {
        Announcement::whereKey($id)->delete();
        session()->flash('status', 'Recado removido.');
    }

    /**
     * @return array<string, mixed>
     */
    public function with(): array
    {
        $hospital = currentHospital();

        $schedules = $hospital?->schedules()->orderByDesc('year')->orderByDesc('month')->get() ?? collect();

        $announcements = Announcement::with(['author', 'schedule'])
            ->where('hospital_id', $hospital?->id)
            ->latest('id')
            ->limit(50)
            ->get();

        return [
            'hospital' => $hospital,
            'schedules' => $schedules,
            'announcements' => $announcements,
        ];
    }
}; ?>

<div>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 leading-tight">Mural de recados</h2>
        <p class="text-sm text-gray-500">{{ $hospital?->name }}</p>
    </x-slot>

    <div class="py-6">
        <div class="max-w-4xl mx-auto sm:px-6 lg:px-8 space-y-6">
            @if (session('status'))
                <div class="rounded-lg bg-teal-50 text-teal-800 px-4 py-3 text-sm">{{ session('status') }}</div>
            @endif

            <div class="bg-white shadow-sm sm:rounded-lg p-6">
                <h3 class="text-sm font-semibold text-gray-800 mb-4">Publicar recado</h3>
                <form wire:submit="publish" class="space-y-4">
                    <div>
                        <x-input-label for="title" value="Título *" />
                        <x-text-input wire:model="title" id="title" type="text" class="mt-1 block w-full" maxlength="120" />
                        <x-input-error :messages="$errors->get('title')" class="mt-1" />
                    </div>
                    <div>
                        <x-input-label for="body" value="Recado *" />
                        <textarea wire:model="body" id="body" rows="3" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-teal-500 focus:ring-teal-500" maxlength="1000"></textarea>
                        <x-input-error :messages="$errors->get('body')" class="mt-1" />
                    </div>
                    <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                        <div>
                            <x-input-label for="scope" value="Enviar para *" />
                            <select wire:model.live="scope" id="scope" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-teal-500 focus:ring-teal-500">
                                <option value="hospital">Todos os médicos do hospital</option>
                                <option value="schedule">Médicos de uma escala</option>
                            </select>
                            <x-input-error :messages="$errors->get('scope')" class="mt-1" />
                        </div>
                        @if ($scope === 'schedule')
                            <div>
                                <x-input-label for="scheduleId" value="Escala *" />
                                <select wire:model="scheduleId" id="scheduleId" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-teal-500 focus:ring-teal-500">
                                    <option value="">Selecione</option>
                                    @foreach ($schedules as $schedule)
                                        <option value="{{ $schedule->id }}">{{ $schedule->monthLabel() }}</option>
                                    @endforeach
                                </select>
                                <x-input-error :messages="$errors->get('scheduleId')" class="mt-1" />
                            </div>
                        @endif
                    </div>
                    <div class="flex justify-end">
                        <x-primary-button type="submit">Publicar recado</x-primary-button>
                    </div>
                </form>
            </div>

            <div class="bg-white shadow-sm sm:rounded-lg">
                <p class="border-b border-gray-100 px-6 py-3 text-sm font-semibold text-gray-700">Recados publicados</p>
                <ul class="divide-y divide-gray-50">
                    @forelse ($announcements as $a)
                        <li class="px-6 py-4">
                            <div class="flex items-start justify-between gap-3">
                                <div class="min-w-0">
                                    <p class="text-sm font-semibold text-gray-900">{{ $a->title }}</p>
                                    <p class="mt-0.5 text-sm text-gray-600">{{ $a->body }}</p>
                                    <p class="mt-1 text-xs text-gray-400">
                                        por {{ $a->author?->name }} · {{ $a->created_at->format('d/m/Y H:i') }}
                                        @if ($a->schedule) · escala {{ $a->schedule->monthLabel() }} @else · todo o hospital @endif
                                    </p>
                                </div>
                                <button wire:click="remove({{ $a->id }})" wire:confirm="Remover este recado?" type="button" class="shrink-0 text-xs text-red-600 hover:underline">Remover</button>
                            </div>
                        </li>
                    @empty
                        <li class="px-6 py-8 text-center text-sm text-gray-400">Nenhum recado publicado ainda.</li>
                    @endforelse
                </ul>
            </div>
        </div>
    </div>
</div>
