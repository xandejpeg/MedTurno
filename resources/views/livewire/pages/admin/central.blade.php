<?php

use App\Enums\Role;
use App\Enums\ScheduleStatus;
use App\Enums\TransferStatus;
use App\Models\CommunicationLog;
use App\Models\Schedule;
use App\Models\ShiftTransfer;
use App\Models\User;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Url;
use Livewire\Volt\Component;

new #[Layout('layouts.admin')] class extends Component
{
    #[Url]
    public string $tab = 'comunicacao';

    public ?int $selectedUserId = null;

    public ?int $selectedManagerId = null;

    public ?int $selectedScheduleId = null;

    public function selectUser(int $id): void
    {
        $this->selectedUserId = $this->selectedUserId === $id ? null : $id;
    }

    public function selectManager(int $id): void
    {
        $this->selectedManagerId = $id;
        $this->selectedScheduleId = null;
    }

    public function selectSchedule(int $id): void
    {
        $this->selectedScheduleId = $this->selectedScheduleId === $id ? null : $id;
    }

    /**
     * @return array<string, mixed>
     */
    public function with(): array
    {
        return [
            'tab' => $this->tab,
            'comunicacao' => $this->tab === 'comunicacao' ? $this->comunicacaoData() : null,
            'plantoes' => $this->tab === 'plantoes' ? $this->plantoesData() : null,
            'trocas' => $this->tab === 'trocas' ? $this->trocasData() : null,
        ];
    }

    /**
     * @return array<string, mixed>
     */
    private function comunicacaoData(): array
    {
        $users = User::query()
            ->where('is_admin', false)
            ->orderBy('name')
            ->get(['id', 'name', 'email', 'phone']);

        $logs = CommunicationLog::with('user')
            ->latest('id')
            ->limit(200)
            ->get();

        $selectedLogs = $this->selectedUserId !== null
            ? CommunicationLog::with('schedule')->where('user_id', $this->selectedUserId)->latest('id')->get()
            : collect();

        return [
            'emailVersion' => 'EscalaPublicada (template fixo do sistema)',
            'whatsappVersion' => config('services.whatsapp.schedule_published_template') ?: '—',
            'whatsappEnabled' => (bool) config('services.whatsapp.enabled'),
            'users' => $users,
            'logs' => $logs,
            'selectedUser' => $this->selectedUserId !== null ? $users->firstWhere('id', $this->selectedUserId) : null,
            'selectedLogs' => $selectedLogs,
        ];
    }

    /**
     * @return array<string, mixed>
     */
    private function plantoesData(): array
    {
        $managers = User::query()
            ->where(function ($q) {
                $q->where('role', Role::Gestor->value)
                    ->orWhereHas('hospitalMemberships', fn ($m) => $m->where('role', Role::Gestor->value));
            })
            ->orderBy('name')
            ->get(['id', 'name', 'email']);

        $schedules = collect();
        $selectedSchedule = null;

        if ($this->selectedManagerId !== null) {
            $schedules = Schedule::with('hospital')
                ->where('created_by', $this->selectedManagerId)
                ->where('status', ScheduleStatus::Publicada->value)
                ->orderByDesc('published_at')
                ->get()
                ->groupBy(fn ($s) => $s->hospital->name);
        }

        if ($this->selectedScheduleId !== null) {
            $selectedSchedule = Schedule::with(['hospital', 'board', 'shifts.doctor'])
                ->find($this->selectedScheduleId);
        }

        return [
            'managers' => $managers,
            'selectedManager' => $this->selectedManagerId !== null ? $managers->firstWhere('id', $this->selectedManagerId) : null,
            'schedulesByHospital' => $schedules,
            'selectedSchedule' => $selectedSchedule,
        ];
    }

    /**
     * @return array<string, mixed>
     */
    private function trocasData(): array
    {
        $transfers = ShiftTransfer::with(['shift.hospital', 'shift.schedule', 'fromDoctor', 'toDoctor', 'decider'])
            ->latest('id')
            ->limit(150)
            ->get();

        return [
            'transfers' => $transfers,
            'ativas' => $transfers->filter(fn ($t) => $t->status->isActive())->count(),
            'aprovadas' => $transfers->where('status', TransferStatus::Aprovada)->count(),
            'recusadas' => $transfers->where('status', TransferStatus::Recusada)->count(),
        ];
    }
}; ?>

<div class="px-4 py-6 sm:px-6 lg:px-10 lg:py-9">
    <header class="mb-6">
        <p class="text-xs font-semibold uppercase text-teal-700">Administração da plataforma</p>
        <h1 class="mt-1 text-2xl font-semibold text-gray-950">Central de Controle</h1>
        <p class="mt-1 text-sm text-gray-500">Comunicação com médicos, plantões publicados por gestor e central de trocas.</p>
    </header>

    <nav class="mb-6 flex flex-wrap gap-2 border-b border-gray-200">
        @foreach ([
            'comunicacao' => 'Comunicação com Médicos',
            'plantoes' => 'Plantões Publicados por Gestor',
            'trocas' => 'Central de Trocas',
        ] as $key => $label)
            <button wire:click="$set('tab', '{{ $key }}')" type="button"
                class="px-4 py-2 text-sm font-medium rounded-t-md border-b-2 -mb-px transition
                    {{ $tab === $key ? 'border-teal-600 text-teal-700 bg-white' : 'border-transparent text-gray-500 hover:text-gray-700' }}">
                {{ $label }}
            </button>
        @endforeach
    </nav>

    {{-- ===================== ABA: COMUNICAÇÃO ===================== --}}
    @if ($tab === 'comunicacao')
        <div class="space-y-6">
            <section class="grid gap-4 sm:grid-cols-2">
                <div class="rounded-lg border border-gray-200 bg-white p-5">
                    <p class="text-xs font-semibold uppercase text-gray-400">E-mail programado</p>
                    <p class="mt-1 text-lg font-semibold text-gray-900">{{ $comunicacao['emailVersion'] }}</p>
                    <p class="mt-1 text-xs text-gray-500">Enviado na publicação de cada escala aos médicos escalados.</p>
                </div>
                <div class="rounded-lg border border-gray-200 bg-white p-5">
                    <p class="text-xs font-semibold uppercase text-gray-400">WhatsApp programado</p>
                    <p class="mt-1 text-lg font-semibold text-gray-900">{{ $comunicacao['whatsappVersion'] }}</p>
                    <p class="mt-1 text-xs">
                        <span class="inline-flex items-center gap-1 rounded-full px-2 py-0.5 font-medium {{ $comunicacao['whatsappEnabled'] ? 'bg-green-100 text-green-700' : 'bg-gray-100 text-gray-500' }}">
                            {{ $comunicacao['whatsappEnabled'] ? 'Ativo' : 'Desativado' }}
                        </span>
                    </p>
                </div>
            </section>

            <div class="grid gap-6 lg:grid-cols-[280px_1fr]">
                {{-- Lista de usuários --}}
                <section class="rounded-lg border border-gray-200 bg-white">
                    <p class="border-b border-gray-100 px-4 py-3 text-sm font-semibold text-gray-700">Usuários</p>
                    <div class="max-h-[60vh] overflow-y-auto">
                        @foreach ($comunicacao['users'] as $u)
                            <button wire:click="selectUser({{ $u->id }})" type="button"
                                class="block w-full px-4 py-2.5 text-left text-sm transition {{ $selectedUserId === $u->id ? 'bg-teal-50 text-teal-800' : 'text-gray-700 hover:bg-gray-50' }}">
                                <span class="block truncate font-medium">{{ $u->name }}</span>
                                <span class="block truncate text-xs text-gray-400">{{ $u->email }}</span>
                            </button>
                        @endforeach
                    </div>
                </section>

                {{-- Fluxo / mensagens do usuário selecionado --}}
                <section class="rounded-lg border border-gray-200 bg-white p-5">
                    @if ($comunicacao['selectedUser'])
                        <p class="text-sm font-semibold text-gray-900">Mensagens enviadas para {{ $comunicacao['selectedUser']->name }}</p>
                        <p class="text-xs text-gray-400">{{ $comunicacao['selectedUser']->email }} · {{ $comunicacao['selectedUser']->phone ?? 'sem celular' }}</p>

                        <div class="mt-4 space-y-2">
                            @forelse ($comunicacao['selectedLogs'] as $log)
                                <div class="flex items-center justify-between gap-3 rounded-md border border-gray-100 bg-gray-50 px-4 py-2.5 text-sm">
                                    <div class="min-w-0">
                                        <span class="inline-flex rounded-full px-2 py-0.5 text-xs font-medium {{ $log->channel === 'whatsapp' ? 'bg-green-100 text-green-700' : 'bg-blue-100 text-blue-700' }}">{{ $log->channelLabel() }}</span>
                                        <span class="ml-2 text-gray-700">{{ $log->subject ?? $log->template ?? '—' }}</span>
                                        <span class="block text-xs text-gray-400">para {{ $log->recipient }}</span>
                                    </div>
                                    <span class="shrink-0 text-xs text-gray-400">{{ $log->created_at->format('d/m/Y H:i') }}</span>
                                </div>
                            @empty
                                <p class="text-sm text-gray-400">Nenhuma mensagem registrada para este usuário ainda.</p>
                            @endforelse
                        </div>
                    @else
                        <p class="text-sm font-semibold text-gray-700">Envios recentes (todos os médicos)</p>
                        <div class="mt-4 space-y-2">
                            @forelse ($comunicacao['logs'] as $log)
                                <div class="flex items-center justify-between gap-3 rounded-md border border-gray-100 bg-gray-50 px-4 py-2.5 text-sm">
                                    <div class="min-w-0">
                                        <span class="inline-flex rounded-full px-2 py-0.5 text-xs font-medium {{ $log->channel === 'whatsapp' ? 'bg-green-100 text-green-700' : 'bg-blue-100 text-blue-700' }}">{{ $log->channelLabel() }}</span>
                                        <span class="ml-2 font-medium text-gray-800">{{ $log->user?->name ?? '—' }}</span>
                                        <span class="block text-xs text-gray-400">{{ $log->subject ?? $log->template ?? '—' }} · para {{ $log->recipient }}</span>
                                    </div>
                                    <span class="shrink-0 text-xs text-gray-400">{{ $log->created_at->format('d/m/Y H:i') }}</span>
                                </div>
                            @empty
                                <p class="text-sm text-gray-400">Nenhum envio registrado ainda. Os próximos envios de e-mail e WhatsApp passam a ser listados aqui automaticamente.</p>
                            @endforelse
                        </div>
                    @endif
                </section>
            </div>
        </div>
    @endif

    {{-- ===================== ABA: PLANTÕES POR GESTOR ===================== --}}
    @if ($tab === 'plantoes')
        <div class="grid gap-6 lg:grid-cols-[280px_1fr]">
            <section class="rounded-lg border border-gray-200 bg-white">
                <p class="border-b border-gray-100 px-4 py-3 text-sm font-semibold text-gray-700">Gestores</p>
                <div class="max-h-[60vh] overflow-y-auto">
                    @foreach ($plantoes['managers'] as $m)
                        <button wire:click="selectManager({{ $m->id }})" type="button"
                            class="block w-full px-4 py-2.5 text-left text-sm transition {{ $selectedManagerId === $m->id ? 'bg-teal-50 text-teal-800' : 'text-gray-700 hover:bg-gray-50' }}">
                            <span class="block truncate font-medium">{{ $m->name }}</span>
                            <span class="block truncate text-xs text-gray-400">{{ $m->email }}</span>
                        </button>
                    @endforeach
                </div>
            </section>

            <section class="space-y-4">
                @if (! $plantoes['selectedManager'])
                    <div class="rounded-lg border border-dashed border-gray-300 bg-white p-8 text-center text-sm text-gray-400">Selecione um gestor para ver os plantões publicados por ele.</div>
                @else
                    <p class="text-sm font-semibold text-gray-900">Publicações de {{ $plantoes['selectedManager']->name }}</p>

                    @forelse ($plantoes['schedulesByHospital'] as $hospitalName => $schedules)
                        <div class="rounded-lg border border-gray-200 bg-white">
                            <p class="border-b border-gray-100 px-4 py-2.5 text-sm font-semibold text-gray-700">{{ $hospitalName }}</p>
                            @foreach ($schedules as $schedule)
                                <button wire:click="selectSchedule({{ $schedule->id }})" type="button"
                                    class="flex w-full items-center justify-between px-4 py-2.5 text-left text-sm transition {{ $selectedScheduleId === $schedule->id ? 'bg-teal-50' : 'hover:bg-gray-50' }}">
                                    <span class="text-gray-800">{{ $schedule->monthLabel() }} · {{ $schedule->board?->name ?? 'Escala geral' }}</span>
                                    <span class="text-xs text-gray-400">publicada em {{ $schedule->published_at?->format('d/m/Y H:i') }}</span>
                                </button>
                            @endforeach
                        </div>
                    @empty
                        <div class="rounded-lg border border-dashed border-gray-300 bg-white p-8 text-center text-sm text-gray-400">Este gestor ainda não publicou nenhuma escala.</div>
                    @endforelse

                    @if ($plantoes['selectedSchedule'])
                        @php $ss = $plantoes['selectedSchedule']; @endphp
                        <div class="rounded-lg border border-teal-200 bg-white p-5">
                            <p class="text-sm font-semibold text-gray-900">Detalhe — {{ $ss->board?->name ?? $ss->hospital->name }} · {{ $ss->monthLabel() }}</p>
                            <p class="text-xs text-gray-400">{{ $ss->hospital->name }} · {{ $ss->shifts->count() }} plantões · publicada em {{ $ss->published_at?->format('d/m/Y H:i') }}</p>
                            <div class="mt-3 overflow-x-auto">
                                <table class="w-full text-sm">
                                    <thead>
                                        <tr class="text-left text-xs uppercase text-gray-400">
                                            <th class="py-1 pr-3">Data</th>
                                            <th class="py-1 pr-3">Horário</th>
                                            <th class="py-1 pr-3">Médico</th>
                                            <th class="py-1">Status</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        @foreach ($ss->shifts->sortBy(['date', 'starts_at']) as $shift)
                                            <tr class="border-t border-gray-50">
                                                <td class="py-1.5 pr-3">{{ $shift->date->format('d/m') }}</td>
                                                <td class="py-1.5 pr-3">{{ $shift->starts_at->format('H:i') }}–{{ $shift->ends_at->format('H:i') }}</td>
                                                <td class="py-1.5 pr-3">{{ $shift->doctor?->name ?? '—' }}</td>
                                                <td class="py-1.5">{{ $shift->status->label() }}</td>
                                            </tr>
                                        @endforeach
                                    </tbody>
                                </table>
                            </div>
                        </div>
                    @endif
                @endif
            </section>
        </div>
    @endif

    {{-- ===================== ABA: CENTRAL DE TROCAS ===================== --}}
    @if ($tab === 'trocas')
        <div class="space-y-6">
            <section class="grid gap-4 sm:grid-cols-3">
                <div class="rounded-lg border border-gray-200 bg-white p-5">
                    <p class="text-xs text-gray-500">Trocas ativas</p>
                    <p class="mt-1 text-2xl font-semibold text-gray-950">{{ $trocas['ativas'] }}</p>
                </div>
                <div class="rounded-lg border border-gray-200 bg-white p-5">
                    <p class="text-xs text-gray-500">Aprovadas</p>
                    <p class="mt-1 text-2xl font-semibold text-green-600">{{ $trocas['aprovadas'] }}</p>
                </div>
                <div class="rounded-lg border border-gray-200 bg-white p-5">
                    <p class="text-xs text-gray-500">Recusadas</p>
                    <p class="mt-1 text-2xl font-semibold text-red-600">{{ $trocas['recusadas'] }}</p>
                </div>
            </section>

            <section class="rounded-lg border border-gray-200 bg-white">
                <p class="border-b border-gray-100 px-4 py-3 text-sm font-semibold text-gray-700">Todas as trocas</p>
                <div class="overflow-x-auto">
                    <table class="w-full text-sm">
                        <thead>
                            <tr class="text-left text-xs uppercase text-gray-400">
                                <th class="px-4 py-2">Plantão</th>
                                <th class="px-4 py-2">De</th>
                                <th class="px-4 py-2">Para</th>
                                <th class="px-4 py-2">Tipo</th>
                                <th class="px-4 py-2">Status</th>
                                <th class="px-4 py-2">Criada em</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse ($trocas['transfers'] as $t)
                                <tr class="border-t border-gray-50">
                                    <td class="px-4 py-2.5">
                                        {{ $t->shift?->date?->format('d/m/Y') }} · {{ $t->shift?->starts_at?->format('H:i') }}–{{ $t->shift?->ends_at?->format('H:i') }}
                                        <span class="block text-xs text-gray-400">{{ $t->shift?->hospital?->name }}</span>
                                    </td>
                                    <td class="px-4 py-2.5">{{ $t->fromDoctor?->name ?? '—' }}</td>
                                    <td class="px-4 py-2.5">{{ $t->toDoctor?->name ?? '—' }}</td>
                                    <td class="px-4 py-2.5">{{ $t->type->label() }}</td>
                                    <td class="px-4 py-2.5">
                                        <span class="inline-flex rounded-full px-2 py-0.5 text-xs font-medium
                                            {{ $t->status->isActive() ? 'bg-amber-100 text-amber-700' : ($t->status === TransferStatus::Aprovada ? 'bg-green-100 text-green-700' : 'bg-gray-100 text-gray-500') }}">
                                            {{ $t->status->label() }}
                                        </span>
                                    </td>
                                    <td class="px-4 py-2.5 text-xs text-gray-400">{{ $t->created_at->format('d/m/Y H:i') }}</td>
                                </tr>
                            @empty
                                <tr><td colspan="6" class="px-4 py-8 text-center text-sm text-gray-400">Nenhuma troca registrada ainda.</td></tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </section>
        </div>
    @endif
</div>
