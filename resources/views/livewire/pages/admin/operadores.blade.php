<?php

use App\Enums\Role;
use App\Models\Hospital;
use App\Models\HospitalMembership;
use App\Models\Schedule;
use App\Models\Shift;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Url;
use Livewire\Volt\Component;

new #[Layout('layouts.admin')] class extends Component
{
    #[Url]
    public string $tab = 'gestores';

    #[Url]
    public string $busca = '';

    #[Url]
    public ?int $perfilId = null;

    public function updatedTab(): void
    {
        $this->perfilId = null;
        $this->busca = '';
    }

    public function verPerfil(int $id): void
    {
        $this->perfilId = $id;
    }

    public function fecharPerfil(): void
    {
        $this->perfilId = null;
    }

    /**
     * Gestores + os hospitais sob sua gestão.
     *
     * @return \Illuminate\Support\Collection<int, User>
     */
    private function gestores(): \Illuminate\Support\Collection
    {
        return User::query()
            ->where('is_admin', false)
            ->where(function ($query) {
                $query->where('role', Role::Gestor->value)
                    ->orWhereHas('hospitalMemberships', fn ($m) => $m->where('role', Role::Gestor->value));
            })
            ->when($this->busca !== '', function ($query) {
                $termo = '%'.$this->busca.'%';
                $query->where(function ($q) use ($termo) {
                    $q->where('name', 'like', $termo)->orWhere('email', 'like', $termo);
                });
            })
            ->with(['managedHospitalsHistory' => fn ($q) => $q->orderBy('name')])
            ->withCount('createdInvitations')
            ->orderBy('name')
            ->get();
    }

    /**
     * Todos os usuários não-admin, com o vínculo de hospitais.
     *
     * @return \Illuminate\Support\Collection<int, User>
     */
    private function usuarios(): \Illuminate\Support\Collection
    {
        return User::query()
            ->where('is_admin', false)
            ->when($this->busca !== '', function ($query) {
                $termo = '%'.$this->busca.'%';
                $query->where(function ($q) use ($termo) {
                    $q->where('name', 'like', $termo)
                        ->orWhere('email', 'like', $termo)
                        ->orWhere('crm', 'like', $termo);
                });
            })
            ->with(['hospitalMemberships.hospital'])
            ->orderBy('name')
            ->get();
    }

    /**
     * Retrato completo de um usuário: vínculos, plantões, agenda e o que ele vê.
     *
     * @return array<string, mixed>|null
     */
    private function perfil(): ?array
    {
        if ($this->perfilId === null) {
            return null;
        }

        $user = User::query()
            ->where('is_admin', false)
            ->with(['hospitalMemberships.hospital'])
            ->find($this->perfilId);

        if ($user === null) {
            return null;
        }

        $hospitalIdsGestor = $user->hospitalMemberships
            ->where('role', Role::Gestor)
            ->pluck('hospital_id');

        return [
            'user' => $user,
            'vinculos' => $user->hospitalMemberships->sortBy(fn ($m) => $m->hospital?->name ?? ''),
            'plantoes' => Shift::query()
                ->where('user_id', $user->id)
                ->with(['schedule.hospital'])
                ->orderByDesc('date')
                ->limit(15)
                ->get(),
            'totalPlantoes' => Shift::where('user_id', $user->id)->count(),
            'porStatus' => Shift::query()
                ->where('user_id', $user->id)
                ->select('status', DB::raw('count(*) as total'))
                ->groupBy('status')
                ->pluck('total', 'status'),
            'notificacoes' => DB::table('notifications')->where('user_id', $user->id)->count(),
            'escalasGeridas' => $hospitalIdsGestor->isEmpty()
                ? collect()
                : Schedule::whereIn('hospital_id', $hospitalIdsGestor)
                    ->with('hospital')
                    ->orderByDesc('year')->orderByDesc('month')
                    ->get(),
            'convitesCriados' => $user->createdInvitations()->count(),
            'menu' => $this->menuVisivel($user),
        ];
    }

    /**
     * Reproduz o gate da navegação real para mostrar ao admin
     * exatamente quais itens de menu esse usuário enxerga.
     *
     * @return array<int, string>
     */
    private function menuVisivel(User $user): array
    {
        $itens = ['Dashboard'];

        if ($user->isMedico()) {
            $itens = array_merge($itens, ['Meus plantões', 'Minha escala', 'Trocas', 'Mural']);
        }

        if ($user->isGestor()) {
            $itens = array_merge($itens, [
                'Hospitais', 'Equipe', 'Convites', 'Escalas', 'Escala do dia',
                'Financeiro', 'NFS', 'Mural', 'Ausências', 'Personalizar',
            ]);
        }

        if ($user->isFinanceiro()) {
            $itens[] = 'Financeiro';
        }

        if ($user->isGestorMunicipal()) {
            $itens[] = 'Escala semanal';
        }

        $itens[] = 'Notificações';

        return array_values(array_unique($itens));
    }

    /**
     * @return array<string, mixed>
     */
    public function with(): array
    {
        return [
            'gestores' => $this->tab === 'gestores' ? $this->gestores() : collect(),
            'usuarios' => $this->tab === 'usuarios' ? $this->usuarios() : collect(),
            'perfil' => $this->perfil(),
            'totais' => [
                'gestores' => User::where('is_admin', false)
                    ->where(fn ($q) => $q->where('role', Role::Gestor->value)
                        ->orWhereHas('hospitalMemberships', fn ($m) => $m->where('role', Role::Gestor->value)))
                    ->count(),
                'usuarios' => User::where('is_admin', false)->count(),
                'hospitais' => Hospital::count(),
                'medicos' => HospitalMembership::where('role', Role::Medico->value)
                    ->where('active', true)->distinct()->count('user_id'),
            ],
        ];
    }
}; ?>

<div class="px-4 py-6 sm:px-6 lg:px-10 lg:py-9">
    <header class="mb-6 flex flex-col gap-2 sm:flex-row sm:items-end sm:justify-between">
        <div>
            <p class="text-xs font-semibold uppercase text-teal-700">Administração da plataforma</p>
            <h1 class="mt-1 text-2xl font-semibold text-gray-950">Gestão de Operadores</h1>
            <p class="mt-1 text-sm text-gray-500">
                Veja o sistema pela perspectiva de qualquer gestor ou usuário, com auditoria completa dos vínculos.
            </p>
        </div>
    </header>

    @if (session('status'))
        <div class="mb-4 rounded-md border border-green-200 bg-green-50 px-4 py-3 text-sm text-green-700">
            {{ session('status') }}
        </div>
    @endif

    {{-- Métricas --}}
    <div class="mb-6 grid grid-cols-2 gap-3 sm:grid-cols-4">
        @foreach ([
            'Gestores' => $totais['gestores'],
            'Usuários' => $totais['usuarios'],
            'Hospitais' => $totais['hospitais'],
            'Médicos ativos' => $totais['medicos'],
        ] as $rotulo => $valor)
            <div class="rounded-lg border border-gray-200 bg-white px-4 py-3 shadow-sm">
                <p class="text-xs font-medium uppercase tracking-wide text-gray-500">{{ $rotulo }}</p>
                <p class="mt-1 text-2xl font-semibold text-gray-900">{{ $valor }}</p>
            </div>
        @endforeach
    </div>

    {{-- Abas --}}
    <div class="mb-5 flex gap-2 border-b border-gray-200">
        <button wire:click="$set('tab', 'gestores')" type="button"
                class="border-b-2 px-4 py-2 text-sm font-medium {{ $tab === 'gestores' ? 'border-teal-600 text-teal-700' : 'border-transparent text-gray-500 hover:text-gray-700' }}">
            Gestores e hospitais
        </button>
        <button wire:click="$set('tab', 'usuarios')" type="button"
                class="border-b-2 px-4 py-2 text-sm font-medium {{ $tab === 'usuarios' ? 'border-teal-600 text-teal-700' : 'border-transparent text-gray-500 hover:text-gray-700' }}">
            Todos os usuários
        </button>
    </div>

    {{-- Busca --}}
    <div class="mb-5">
        <input type="search" wire:model.live.debounce.300ms="busca"
               placeholder="Buscar por nome, e-mail{{ $tab === 'usuarios' ? ' ou CRM' : '' }}…"
               class="w-full max-w-md rounded-md border-gray-300 text-sm shadow-sm focus:border-teal-500 focus:ring-teal-500">
    </div>

    {{-- ABA: Gestores --}}
    @if ($tab === 'gestores')
        <div class="space-y-4">
            @forelse ($gestores as $gestor)
                <div class="rounded-lg border border-gray-200 bg-white p-4 shadow-sm">
                    <div class="flex flex-wrap items-start justify-between gap-3">
                        <div class="min-w-0">
                            <div class="flex items-center gap-2">
                                <h2 class="truncate text-base font-semibold text-gray-900">{{ $gestor->name }}</h2>
                                @if (! $gestor->active)
                                    <span class="rounded-full bg-red-100 px-2 py-0.5 text-xs font-medium text-red-700">Inativo</span>
                                @endif
                            </div>
                            <p class="truncate text-sm text-gray-500">{{ $gestor->email }}</p>
                            <p class="mt-1 text-xs text-gray-400">
                                {{ $gestor->created_invitations_count }} convite(s) criado(s)
                                · cadastrado em {{ $gestor->created_at?->format('d/m/Y') }}
                            </p>
                        </div>

                        <div class="flex shrink-0 flex-wrap gap-2">
                            <button wire:click="verPerfil({{ $gestor->id }})" type="button"
                                    class="rounded-md bg-gray-100 px-3 py-1.5 text-xs font-medium text-gray-700 hover:bg-gray-200">
                                Ver perfil
                            </button>
                            <a href="{{ route('admin.managers.show', $gestor) }}" wire:navigate
                               class="rounded-md bg-teal-50 px-3 py-1.5 text-xs font-medium text-teal-700 hover:bg-teal-100">
                                Detalhes
                            </a>
                            <form method="POST" action="{{ route('admin.impersonate.start', $gestor) }}">
                                @csrf
                                <button type="submit"
                                        class="rounded-md bg-teal-700 px-3 py-1.5 text-xs font-semibold text-white hover:bg-teal-800">
                                    Acessar como gestor
                                </button>
                            </form>
                        </div>
                    </div>

                    @if ($gestor->managedHospitalsHistory->isNotEmpty())
                        <div class="mt-3 border-t border-gray-100 pt-3">
                            <p class="mb-1.5 text-xs font-semibold uppercase tracking-wide text-gray-400">
                                Hospitais sob sua gestão
                            </p>
                            <div class="flex flex-wrap gap-1.5">
                                @foreach ($gestor->managedHospitalsHistory as $hospital)
                                    <span class="inline-flex items-center gap-1 rounded-full px-2.5 py-1 text-xs font-medium
                                        {{ $hospital->pivot->active ? 'bg-teal-50 text-teal-800' : 'bg-gray-100 text-gray-500 line-through' }}">
                                        {{ $hospital->name }}
                                    </span>
                                @endforeach
                            </div>
                        </div>
                    @else
                        <p class="mt-3 border-t border-gray-100 pt-3 text-xs text-amber-700">
                            Nenhum hospital vinculado.
                        </p>
                    @endif
                </div>
            @empty
                <p class="rounded-lg border border-dashed border-gray-300 bg-white px-4 py-8 text-center text-sm text-gray-500">
                    Nenhum gestor encontrado.
                </p>
            @endforelse
        </div>
    @endif

    {{-- ABA: Todos os usuários --}}
    @if ($tab === 'usuarios')
        <div class="overflow-x-auto rounded-lg border border-gray-200 bg-white shadow-sm">
            <table class="w-full text-sm">
                <thead class="bg-gray-50 text-xs uppercase text-gray-500">
                    <tr>
                        <th class="px-4 py-3 text-left font-semibold">Nome</th>
                        <th class="px-4 py-3 text-left font-semibold">E-mail</th>
                        <th class="px-4 py-3 text-left font-semibold">Papel</th>
                        <th class="px-4 py-3 text-left font-semibold">CRM</th>
                        <th class="px-4 py-3 text-left font-semibold">Hospitais</th>
                        <th class="px-4 py-3 text-left font-semibold">Cadastro</th>
                        <th class="px-4 py-3 text-right font-semibold">Ações</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-100">
                    @forelse ($usuarios as $usuario)
                        <tr class="hover:bg-gray-50">
                            <td class="px-4 py-3 font-medium text-gray-900">
                                {{ $usuario->name }}
                                @if (! $usuario->active)
                                    <span class="ml-1 rounded bg-red-100 px-1.5 py-0.5 text-[10px] font-medium text-red-700">inativo</span>
                                @endif
                            </td>
                            <td class="px-4 py-3 text-gray-600">{{ $usuario->email }}</td>
                            <td class="px-4 py-3">
                                <span class="rounded-full bg-gray-100 px-2 py-0.5 text-xs font-medium text-gray-700">
                                    {{ $usuario->role?->label() ?? '—' }}
                                </span>
                            </td>
                            <td class="px-4 py-3 text-gray-500">
                                {{ $usuario->crm ? $usuario->crm.'/'.$usuario->crm_uf : '—' }}
                            </td>
                            <td class="px-4 py-3 text-gray-500">
                                @if ($usuario->hospitalMemberships->isEmpty())
                                    <span class="text-amber-600">nenhum</span>
                                @else
                                    <span class="text-xs">
                                        {{ $usuario->hospitalMemberships->count() }} vínculo(s)
                                    </span>
                                @endif
                            </td>
                            <td class="px-4 py-3">
                                @if ($usuario->cadastroCompleto())
                                    <span class="rounded-full bg-green-100 px-2 py-0.5 text-xs font-medium text-green-700">completo</span>
                                @else
                                    <span class="rounded-full bg-amber-100 px-2 py-0.5 text-xs font-medium text-amber-700">incompleto</span>
                                @endif
                            </td>
                            <td class="px-4 py-3">
                                <div class="flex justify-end gap-2">
                                    <button wire:click="verPerfil({{ $usuario->id }})" type="button"
                                            class="rounded-md bg-gray-100 px-2.5 py-1 text-xs font-medium text-gray-700 hover:bg-gray-200">
                                        Perfil
                                    </button>
                                    <form method="POST" action="{{ route('admin.impersonate.start', $usuario) }}">
                                        @csrf
                                        <button type="submit"
                                                class="rounded-md bg-teal-700 px-2.5 py-1 text-xs font-semibold text-white hover:bg-teal-800">
                                            Acessar como
                                        </button>
                                    </form>
                                </div>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="7" class="px-4 py-8 text-center text-sm text-gray-500">
                                Nenhum usuário encontrado.
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    @endif

    {{-- Painel lateral: perfil detalhado --}}
    @if ($perfil !== null)
        <div class="fixed inset-0 z-40 flex justify-end">
            <div class="absolute inset-0 bg-gray-900/40" wire:click="fecharPerfil"></div>

            <aside class="relative z-10 flex h-full w-full max-w-xl flex-col overflow-y-auto bg-white shadow-2xl">
                <header class="sticky top-0 flex items-start justify-between gap-3 border-b border-gray-200 bg-white px-5 py-4">
                    <div class="min-w-0">
                        <h2 class="truncate text-lg font-semibold text-gray-900">{{ $perfil['user']->name }}</h2>
                        <p class="truncate text-sm text-gray-500">{{ $perfil['user']->email }}</p>
                    </div>
                    <button wire:click="fecharPerfil" type="button"
                            class="shrink-0 rounded-md p-1.5 text-gray-400 hover:bg-gray-100 hover:text-gray-600">
                        <svg class="h-5 w-5" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12" />
                        </svg>
                    </button>
                </header>

                <div class="space-y-6 px-5 py-5">
                    {{-- Identificação --}}
                    <section>
                        <h3 class="mb-2 text-xs font-semibold uppercase tracking-wide text-gray-400">Identificação</h3>
                        <dl class="grid grid-cols-2 gap-x-4 gap-y-2 text-sm">
                            @foreach ([
                                'Papel' => $perfil['user']->role?->label() ?? '—',
                                'CRM' => $perfil['user']->crm ? $perfil['user']->crm.'/'.$perfil['user']->crm_uf : '—',
                                'Telefone' => $perfil['user']->phone ?: '—',
                                'CPF' => $perfil['user']->cpf ? 'preenchido' : '—',
                                'Especialidade' => $perfil['user']->specialty ?: '—',
                                'Situação' => $perfil['user']->active ? 'ativo' : 'inativo',
                                'Cadastro' => $perfil['user']->cadastroCompleto() ? 'completo' : 'incompleto',
                                'Criado em' => $perfil['user']->created_at?->format('d/m/Y H:i') ?? '—',
                            ] as $rotulo => $valor)
                                <div>
                                    <dt class="text-xs text-gray-400">{{ $rotulo }}</dt>
                                    <dd class="text-gray-800">{{ $valor }}</dd>
                                </div>
                            @endforeach
                        </dl>
                    </section>

                    {{-- Vínculos --}}
                    <section>
                        <h3 class="mb-2 text-xs font-semibold uppercase tracking-wide text-gray-400">
                            Hospitais vinculados ({{ $perfil['vinculos']->count() }})
                        </h3>
                        @forelse ($perfil['vinculos'] as $vinculo)
                            <div class="mb-1.5 flex items-center justify-between gap-2 rounded-md border border-gray-200 px-3 py-2 text-sm">
                                <span class="min-w-0 truncate text-gray-800">{{ $vinculo->hospital?->name ?? '—' }}</span>
                                <span class="shrink-0 rounded-full px-2 py-0.5 text-xs font-medium
                                    {{ $vinculo->active ? 'bg-teal-50 text-teal-800' : 'bg-gray-100 text-gray-500' }}">
                                    {{ $vinculo->role?->label() ?? '—' }}{{ $vinculo->active ? '' : ' (inativo)' }}
                                </span>
                            </div>
                        @empty
                            <p class="text-sm text-amber-700">Nenhum vínculo com hospital.</p>
                        @endforelse
                    </section>

                    {{-- O que este usuário vê --}}
                    <section>
                        <h3 class="mb-2 text-xs font-semibold uppercase tracking-wide text-gray-400">
                            Menu que este usuário vê
                        </h3>
                        <div class="flex flex-wrap gap-1.5">
                            @foreach ($perfil['menu'] as $item)
                                <span class="rounded-full bg-indigo-50 px-2.5 py-1 text-xs font-medium text-indigo-700">
                                    {{ $item }}
                                </span>
                            @endforeach
                        </div>
                    </section>

                    {{-- Atividade --}}
                    <section>
                        <h3 class="mb-2 text-xs font-semibold uppercase tracking-wide text-gray-400">Atividade</h3>
                        <div class="grid grid-cols-3 gap-2 text-center">
                            @foreach ([
                                'Plantões' => $perfil['totalPlantoes'],
                                'Notificações' => $perfil['notificacoes'],
                                'Convites' => $perfil['convitesCriados'],
                            ] as $rotulo => $valor)
                                <div class="rounded-md bg-gray-50 px-2 py-2">
                                    <p class="text-lg font-semibold text-gray-900">{{ $valor }}</p>
                                    <p class="text-xs text-gray-500">{{ $rotulo }}</p>
                                </div>
                            @endforeach
                        </div>

                        @if ($perfil['porStatus']->isNotEmpty())
                            <div class="mt-3 flex flex-wrap gap-1.5">
                                @foreach ($perfil['porStatus'] as $status => $total)
                                    <span class="rounded-full bg-gray-100 px-2 py-0.5 text-xs text-gray-700">
                                        {{ $status }}: {{ $total }}
                                    </span>
                                @endforeach
                            </div>
                        @endif
                    </section>

                    {{-- Escalas geridas --}}
                    @if ($perfil['escalasGeridas']->isNotEmpty())
                        <section>
                            <h3 class="mb-2 text-xs font-semibold uppercase tracking-wide text-gray-400">
                                Escalas dos hospitais que gerencia
                            </h3>
                            @foreach ($perfil['escalasGeridas'] as $escala)
                                <div class="mb-1.5 flex items-center justify-between gap-2 rounded-md border border-gray-200 px-3 py-2 text-sm">
                                    <span class="min-w-0 truncate text-gray-800">
                                        {{ str_pad((string) $escala->month, 2, '0', STR_PAD_LEFT) }}/{{ $escala->year }}
                                        · {{ $escala->hospital?->name ?? '—' }}
                                    </span>
                                    <span class="shrink-0 rounded-full bg-gray-100 px-2 py-0.5 text-xs text-gray-600">
                                        {{ $escala->status }}
                                    </span>
                                </div>
                            @endforeach
                        </section>
                    @endif

                    {{-- Últimos plantões --}}
                    @if ($perfil['plantoes']->isNotEmpty())
                        <section>
                            <h3 class="mb-2 text-xs font-semibold uppercase tracking-wide text-gray-400">
                                Últimos plantões ({{ $perfil['plantoes']->count() }} de {{ $perfil['totalPlantoes'] }})
                            </h3>
                            @foreach ($perfil['plantoes'] as $plantao)
                                <div class="mb-1.5 flex items-center justify-between gap-2 rounded-md border border-gray-200 px-3 py-2 text-sm">
                                    <span class="min-w-0 truncate text-gray-800">
                                        {{ $plantao->date?->format('d/m/Y') }}
                                        <span class="text-gray-400">·</span>
                                        {{ $plantao->schedule?->hospital?->name ?? '—' }}
                                    </span>
                                    <span class="shrink-0 rounded-full bg-gray-100 px-2 py-0.5 text-xs text-gray-600">
                                        {{ $plantao->status }}
                                    </span>
                                </div>
                            @endforeach
                        </section>
                    @endif
                </div>

                <footer class="sticky bottom-0 border-t border-gray-200 bg-white px-5 py-4">
                    <form method="POST" action="{{ route('admin.impersonate.start', $perfil['user']) }}">
                        @csrf
                        <button type="submit"
                                class="w-full rounded-md bg-teal-700 px-4 py-2.5 text-sm font-semibold text-white hover:bg-teal-800">
                            Acessar o app como {{ $perfil['user']->name }}
                        </button>
                    </form>
                    <p class="mt-2 text-center text-xs text-gray-400">
                        Você poderá voltar ao painel admin a qualquer momento.
                    </p>
                </footer>
            </aside>
        </div>
    @endif
</div>
