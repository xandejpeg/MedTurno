<?php

use App\Models\Schedule;
use App\Models\User;
use App\Enums\Role;
use Illuminate\Support\Carbon;
use Livewire\Attributes\Layout;
use Livewire\Volt\Component;

new #[Layout('layouts.admin')] class extends Component
{
    public Schedule $schedule;

    public User $manager;

    public function mount(User $manager, Schedule $schedule): void
    {
        $isManager = $manager->isGestor()
            || $manager->hospitalMemberships()->where('role', Role::Gestor->value)->exists();

        abort_unless($isManager, 404);
        abort_unless($manager->managedHospitalsHistory()->whereKey($schedule->hospital_id)->exists(), 404);

        $this->manager = $manager;
        $this->schedule = $schedule;
    }

    /**
     * @return array<string, mixed>
     */
    public function with(): array
    {
        $start = Carbon::create($this->schedule->year, $this->schedule->month, 1);
        $end = $start->copy()->endOfMonth();
        $shifts = $this->schedule->shifts()
            ->with('doctor')
            ->orderBy('starts_at')
            ->get()
            ->groupBy(fn ($shift) => $shift->date->toDateString());

        $days = [];
        for ($cursor = $start->copy(); $cursor->lte($end); $cursor->addDay()) {
            $days[] = [
                'date' => $cursor->copy(),
                'shifts' => $shifts->get($cursor->toDateString(), collect()),
            ];
        }

        $total = $this->schedule->shifts()->count();
        $assigned = $this->schedule->shifts()->whereNotNull('user_id')->count();

        return [
            'days' => $days,
            'leadingBlanks' => (int) $start->dayOfWeek,
            'monthLabel' => ucfirst($start->translatedFormat('F \d\e Y')),
            'total' => $total,
            'assigned' => $assigned,
        ];
    }
}; ?>

<div class="px-4 py-6 sm:px-6 lg:px-10 lg:py-9">
    <a href="{{ route('admin.managers.show', $manager) }}" wire:navigate class="text-sm font-medium text-teal-700 hover:text-teal-900">← Voltar ao gestor</a>

    <header class="mt-5 flex flex-col gap-3 border-b border-gray-200 pb-6 sm:flex-row sm:items-end sm:justify-between">
        <div>
            <p class="text-xs font-semibold uppercase text-teal-700">Escala somente leitura</p>
            <h1 class="mt-1 text-2xl font-semibold text-gray-950">{{ $monthLabel }}</h1>
            <p class="mt-1 text-sm text-gray-500">{{ $schedule->hospital->name }} · {{ $schedule->status->label() }}</p>
        </div>
        <p class="text-sm font-medium text-gray-700">{{ $assigned }}/{{ $total }} plantões preenchidos</p>
    </header>

    <section class="mt-6 overflow-x-auto rounded-lg border border-gray-200 bg-white p-3 sm:p-5">
        <div class="grid min-w-[840px] grid-cols-7 gap-2">
            @foreach (['Dom', 'Seg', 'Ter', 'Qua', 'Qui', 'Sex', 'Sáb'] as $weekday)
                <div class="pb-1 text-center text-xs font-semibold uppercase text-gray-400">{{ $weekday }}</div>
            @endforeach

            @for ($i = 0; $i < $leadingBlanks; $i++)
                <div></div>
            @endfor

            @foreach ($days as $day)
                <article class="min-h-36 rounded-md border border-gray-200 bg-gray-50/70 p-2">
                    <p class="mb-2 text-right text-xs font-semibold text-gray-500">{{ $day['date']->day }}</p>
                    <div class="space-y-1.5">
                        @forelse ($day['shifts'] as $shift)
                            <div class="rounded border border-gray-200 bg-white px-2 py-1.5 shadow-sm">
                                <div class="flex items-center justify-between gap-1 text-[10px] text-gray-400">
                                    <span>{{ $shift->starts_at->format('H:i') }}–{{ $shift->ends_at->format('H:i') }}</span>
                                    <span>{{ $shift->status->label() }}</span>
                                </div>
                                <p class="mt-1 truncate text-xs font-medium {{ $shift->doctor ? 'text-gray-800' : 'text-red-600' }}" title="{{ $shift->doctor?->name }}">
                                    {{ $shift->doctor?->name ?: 'Sem médico' }}
                                </p>
                            </div>
                        @empty
                            <p class="text-[11px] text-gray-400">Sem plantões</p>
                        @endforelse
                    </div>
                </article>
            @endforeach
        </div>
    </section>
</div>