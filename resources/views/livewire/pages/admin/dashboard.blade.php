<?php

use App\Enums\Role;
use App\Enums\ScheduleStatus;
use App\Models\Hospital;
use App\Models\HospitalMembership;
use App\Models\Invitation;
use App\Models\Schedule;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Livewire\Attributes\Layout;
use Livewire\Volt\Component;

new #[Layout('layouts.admin')] class extends Component
{
    /**
     * @return array<string, mixed>
     */
    public function with(): array
    {
        $managers = User::query()
            ->where(function ($query) {
                $query->where('role', Role::Gestor->value)
                    ->orWhereHas('hospitalMemberships', fn ($membership) => $membership->where('role', Role::Gestor->value));
            })
            ->with(['managedHospitalsHistory' => fn ($query) => $query->orderBy('name')])
            ->withCount('createdInvitations')
            ->orderBy('name')
            ->get();

        $signupCounts = DB::table('hospital_memberships')
            ->join('invitations', 'invitations.id', '=', 'hospital_memberships.invitation_id')
            ->whereIn('invitations.created_by', $managers->pluck('id'))
            ->selectRaw('invitations.created_by, count(distinct hospital_memberships.user_id) as total')
            ->groupBy('invitations.created_by')
            ->pluck('total', 'invitations.created_by');

        foreach ($managers as $manager) {
            $manager->setAttribute('invitation_signups_count', (int) ($signupCounts[$manager->id] ?? 0));
        }

        return [
            'managers' => $managers,
            'metrics' => [
                'gestores' => $managers->count(),
                'hospitais' => Hospital::query()->count(),
                'usuarios' => User::query()->where('is_admin', false)->count(),
                'medicos' => HospitalMembership::query()->where('role', Role::Medico->value)->where('active', true)->distinct()->count('user_id'),
                'convites' => Invitation::query()->count(),
                'escalas' => Schedule::query()->count(),
                'publicadas' => Schedule::query()->where('status', ScheduleStatus::Publicada->value)->count(),
            ],
        ];
    }
}; ?>

<div class="px-4 py-6 sm:px-6 lg:px-10 lg:py-9">
    <header class="mb-8 flex flex-col gap-2 sm:flex-row sm:items-end sm:justify-between">
        <div>
            <p class="text-xs font-semibold uppercase text-teal-700">Administração da plataforma</p>
            <h1 class="mt-1 text-2xl font-semibold text-gray-950">Dashboard</h1>
            <p class="mt-1 text-sm text-gray-500">Dados atuais do DoctorTurn, sem informações simuladas.</p>
        </div>
        <p class="text-xs text-gray-400">Atualizado em {{ now()->format('d/m/Y H:i') }}</p>
    </header>

    <section aria-label="Indicadores" class="grid grid-cols-2 gap-px overflow-hidden rounded-lg border border-gray-200 bg-gray-200 sm:grid-cols-4 xl:grid-cols-7">
        @foreach ([
            'Gestores' => $metrics['gestores'],
            'Hospitais' => $metrics['hospitais'],
            'Usuários' => $metrics['usuarios'],
            'Médicos ativos' => $metrics['medicos'],
            'Convites' => $metrics['convites'],
            'Escalas' => $metrics['escalas'],
            'Publicadas' => $metrics['publicadas'],
        ] as $label => $value)
            <div class="bg-white px-4 py-4">
                <p class="text-xs text-gray-500">{{ $label }}</p>
                <p class="mt-1 text-2xl font-semibold text-gray-950">{{ $value }}</p>
            </div>
        @endforeach
    </section>

    <section class="mt-8 overflow-hidden rounded-lg border border-gray-200 bg-white">
        <div class="border-b border-gray-200 px-5 py-4">
            <h2 class="font-semibold text-gray-950">Gestores cadastrados</h2>
            <p class="mt-1 text-sm text-gray-500">Abra um gestor para consultar hospitais, equipe, convites e escalas.</p>
        </div>

        <div class="divide-y divide-gray-100">
            @forelse ($managers as $manager)
                <a href="{{ route('admin.managers.show', $manager) }}" wire:navigate class="grid gap-3 px-5 py-4 hover:bg-teal-50/50 sm:grid-cols-[minmax(0,1.5fr)_minmax(0,1fr)_100px_120px_20px] sm:items-center">
                    <div class="min-w-0">
                        <p class="truncate font-semibold text-gray-900">{{ $manager->name }}</p>
                        <p class="truncate text-sm text-gray-500">{{ $manager->email }}</p>
                    </div>
                    <p class="truncate text-sm text-gray-600">{{ $manager->managedHospitalsHistory->pluck('name')->join(', ') ?: 'Sem hospital' }}</p>
                    <div>
                        <p class="text-xs text-gray-400">Convites</p>
                        <p class="font-medium text-gray-800">{{ $manager->created_invitations_count }}</p>
                    </div>
                    <div>
                        <p class="text-xs text-gray-400">Cadastros via link</p>
                        <p class="font-medium text-gray-800">{{ $manager->invitation_signups_count }}</p>
                    </div>
                    <span class="text-right text-lg text-teal-700" aria-hidden="true">›</span>
                </a>
            @empty
                <p class="px-5 py-10 text-center text-sm text-gray-500">Nenhum gestor cadastrado.</p>
            @endforelse
        </div>
    </section>
</div>