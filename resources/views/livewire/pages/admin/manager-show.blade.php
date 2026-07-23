<?php

use App\Enums\Role;
use App\Models\HospitalMembership;
use App\Models\Invitation;
use App\Models\Schedule;
use App\Models\User;
use Livewire\Attributes\Layout;
use Livewire\Volt\Component;

new #[Layout('layouts.admin')] class extends Component
{
    public User $manager;

    public function mount(User $manager): void
    {
        $isManager = $manager->isGestor()
            || $manager->hospitalMemberships()->where('role', Role::Gestor->value)->exists();

        abort_unless($isManager, 404);

        $this->manager = $manager;
    }

    /**
     * @return array<string, mixed>
     */
    public function with(): array
    {
        $hospitals = $this->manager->managedHospitalsHistory()
            ->withCount([
                'memberships as active_doctors_count' => fn ($query) => $query
                    ->where('role', Role::Medico->value)
                    ->where('active', true),
                'schedules',
            ])
            ->orderBy('name')
            ->get();

        $hospitalIds = $hospitals->pluck('id');

        $invitations = Invitation::query()
            ->where('created_by', $this->manager->id)
            ->with(['hospital', 'shiftBoard'])
            ->withCount('memberships')
            ->latest()
            ->get();

        $registrations = HospitalMembership::query()
            ->whereNotNull('invitation_id')
            ->whereHas('invitation', fn ($query) => $query->where('created_by', $this->manager->id))
            ->with(['user', 'hospital', 'invitation'])
            ->latest()
            ->get();

        $schedules = Schedule::query()
            ->whereIn('hospital_id', $hospitalIds)
            ->with(['hospital', 'board'])
            ->withCount([
                'shifts',
                'shifts as assigned_shifts_count' => fn ($query) => $query->whereNotNull('user_id'),
            ])
            ->orderByDesc('year')
            ->orderByDesc('month')
            ->get();

        return [
            'hospitals' => $hospitals,
            'invitations' => $invitations,
            'registrations' => $registrations,
            'schedules' => $schedules,
            'uniqueRegistrations' => $registrations->pluck('user_id')->unique()->count(),
        ];
    }
}; ?>

<div class="px-4 py-6 sm:px-6 lg:px-10 lg:py-9">
    <a href="{{ route('admin.dashboard') }}" wire:navigate class="text-sm font-medium text-teal-700 hover:text-teal-900">← Voltar ao dashboard</a>

    <header class="mt-5 flex flex-col gap-4 border-b border-gray-200 pb-6 sm:flex-row sm:items-end sm:justify-between">
        <div class="min-w-0">
            <p class="text-xs font-semibold uppercase text-teal-700">Gestor</p>
            <h1 class="mt-1 truncate text-2xl font-semibold text-gray-950">{{ $manager->name }}</h1>
            <p class="mt-1 text-sm text-gray-500">{{ $manager->email }} @if($manager->phone) · {{ $manager->phone }} @endif</p>
        </div>
        <p class="text-xs text-gray-400">Conta criada em {{ $manager->created_at->format('d/m/Y H:i') }}</p>
    </header>

    <section class="mt-6 grid grid-cols-2 gap-px overflow-hidden rounded-lg border border-gray-200 bg-gray-200 sm:grid-cols-4">
        @foreach ([
            'Hospitais' => $hospitals->count(),
            'Convites gerados' => $invitations->count(),
            'Cadastros via convite' => $uniqueRegistrations,
            'Escalas' => $schedules->count(),
        ] as $label => $value)
            <div class="bg-white px-4 py-4">
                <p class="text-xs text-gray-500">{{ $label }}</p>
                <p class="mt-1 text-2xl font-semibold text-gray-950">{{ $value }}</p>
            </div>
        @endforeach
    </section>

    <section class="mt-8">
        <div class="mb-3">
            <h2 class="font-semibold text-gray-950">Hospitais administrados</h2>
            <p class="text-sm text-gray-500">Equipes e escalas vinculadas ao gestor.</p>
        </div>
        <div class="grid gap-3 md:grid-cols-2 xl:grid-cols-3">
            @forelse ($hospitals as $hospital)
                <article class="rounded-lg border border-gray-200 bg-white p-4">
                    <div class="flex items-start justify-between gap-3">
                        <h3 class="font-semibold text-gray-900">{{ $hospital->name }}</h3>
                        <span class="rounded-full px-2 py-0.5 text-xs {{ $hospital->pivot->active ? 'bg-teal-50 text-teal-700' : 'bg-gray-100 text-gray-500' }}">{{ $hospital->pivot->active ? 'Vínculo ativo' : 'Vínculo inativo' }}</span>
                    </div>
                    <dl class="mt-4 grid grid-cols-2 gap-3 text-sm">
                        <div><dt class="text-xs text-gray-400">Médicos ativos</dt><dd class="font-medium text-gray-800">{{ $hospital->active_doctors_count }}</dd></div>
                        <div><dt class="text-xs text-gray-400">Escalas</dt><dd class="font-medium text-gray-800">{{ $hospital->schedules_count }}</dd></div>
                    </dl>
                </article>
            @empty
                <p class="text-sm text-gray-500">Este gestor ainda não administra hospitais.</p>
            @endforelse
        </div>
    </section>

    <section class="mt-8 overflow-hidden rounded-lg border border-gray-200 bg-white">
        <div class="border-b border-gray-200 px-5 py-4">
            <h2 class="font-semibold text-gray-950">Contas criadas pelos convites</h2>
            <p class="mt-1 text-sm text-gray-500">Vínculos comprovados pelo convite usado no cadastro.</p>
        </div>
        <div class="overflow-x-auto">
            <table class="min-w-full divide-y divide-gray-200 text-sm">
                <thead class="bg-gray-50 text-left text-xs font-semibold uppercase text-gray-500">
                    <tr><th class="px-5 py-3">Usuário</th><th class="px-5 py-3">Hospital</th><th class="px-5 py-3">Contato</th><th class="px-5 py-3">Entrada</th></tr>
                </thead>
                <tbody class="divide-y divide-gray-100">
                    @forelse ($registrations as $membership)
                        <tr>
                            <td class="px-5 py-3"><p class="font-medium text-gray-900">{{ $membership->user->name }}</p><p class="text-xs text-gray-500">{{ $membership->user->email }}</p></td>
                            <td class="px-5 py-3 text-gray-700">{{ $membership->hospital->name }}</td>
                            <td class="px-5 py-3 text-gray-600">{{ $membership->user->phone ?: 'Não informado' }}</td>
                            <td class="px-5 py-3 text-gray-500">{{ $membership->created_at->format('d/m/Y H:i') }}</td>
                        </tr>
                    @empty
                        <tr><td colspan="4" class="px-5 py-8 text-center text-gray-500">Nenhuma conta criada por convite deste gestor.</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </section>

    <section class="mt-8 overflow-hidden rounded-lg border border-gray-200 bg-white">
        <div class="border-b border-gray-200 px-5 py-4">
            <h2 class="font-semibold text-gray-950">Convites gerados</h2>
            <p class="mt-1 text-sm text-gray-500">Histórico real de links individuais e de grupo.</p>
        </div>
        <div class="divide-y divide-gray-100">
            @forelse ($invitations as $invitation)
                <div class="grid gap-2 px-5 py-3 sm:grid-cols-[1fr_1fr_100px_100px] sm:items-center">
                    <div><p class="font-medium text-gray-900">{{ $invitation->hospital->name }}</p><p class="text-xs text-gray-500">{{ $invitation->type->label() }}{{ $invitation->shiftBoard ? ' · '.$invitation->shiftBoard->name : '' }}</p></div>
                    <p class="text-sm text-gray-600">{{ $invitation->email ?: 'Link de grupo' }}</p>
                    <div><p class="text-xs text-gray-400">Cadastros</p><p class="font-medium">{{ $invitation->memberships_count }}</p></div>
                    <span class="text-sm text-gray-600">{{ $invitation->status->label() }}</span>
                </div>
            @empty
                <p class="px-5 py-8 text-center text-sm text-gray-500">Nenhum convite gerado por este gestor.</p>
            @endforelse
        </div>
    </section>

    <section class="mt-8 overflow-hidden rounded-lg border border-gray-200 bg-white">
        <div class="border-b border-gray-200 px-5 py-4">
            <h2 class="font-semibold text-gray-950">Escalas dos hospitais</h2>
            <p class="mt-1 text-sm text-gray-500">Clique para abrir a distribuição completa de plantões.</p>
        </div>
        <div class="divide-y divide-gray-100">
            @forelse ($schedules as $schedule)
                <a href="{{ route('admin.schedules.show', ['manager' => $manager, 'schedule' => $schedule]) }}" wire:navigate class="grid gap-2 px-5 py-4 hover:bg-teal-50/50 sm:grid-cols-[1fr_160px_140px_20px] sm:items-center">
                    <div><p class="font-medium text-gray-900">{{ $schedule->hospital->name }}</p><p class="text-xs text-gray-500">{{ $schedule->board?->name ?: 'Escala mensal' }}</p></div>
                    <p class="text-sm text-gray-700">{{ $schedule->monthLabel() }}</p>
                    <div><p class="text-xs text-gray-400">Preenchidos</p><p class="text-sm font-medium">{{ $schedule->assigned_shifts_count }}/{{ $schedule->shifts_count }}</p></div>
                    <span class="text-right text-lg text-teal-700">›</span>
                </a>
            @empty
                <p class="px-5 py-8 text-center text-sm text-gray-500">Nenhuma escala criada nos hospitais deste gestor.</p>
            @endforelse
        </div>
    </section>
</div>