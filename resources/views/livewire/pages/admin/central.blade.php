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

    public ?string $selectedEmailTemplate = null;

    public ?int $selectedLogId = null;

    public function selectUser(int $id): void
    {
        $this->selectedUserId = $this->selectedUserId === $id ? null : $id;
        $this->selectedLogId = null;
    }

    public function selectEmailTemplate(string $key): void
    {
        $this->selectedEmailTemplate = $this->selectedEmailTemplate === $key ? null : $key;
    }

    public function selectLog(int $id): void
    {
        $this->selectedLogId = $this->selectedLogId === $id ? null : $id;
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

        $selectedLog = $this->selectedLogId !== null
            ? CommunicationLog::with('user')->find($this->selectedLogId)
            : null;

        return [
            'emailTemplates' => $this->emailTemplates(),
            'whatsappVersion' => config('services.whatsapp.schedule_published_template') ?: '—',
            'whatsappEnabled' => (bool) config('services.whatsapp.enabled'),
            'whatsappBody' => $this->whatsappBody(),
            'users' => $users,
            'logs' => $logs,
            'selectedUser' => $this->selectedUserId !== null ? $users->firstWhere('id', $this->selectedUserId) : null,
            'selectedLogs' => $selectedLogs,
            'selectedLog' => $selectedLog,
        ];
    }

    /**
     * Catálogo de e-mails programados do sistema.
     *
     * @return array<string, array{label: string, subject: string, body: string, desc: string}>
     */
    private function emailTemplates(): array
    {
        return [
            'escala' => [
                'label' => 'Escala publicada',
                'subject' => 'Sua escala de {mês} está publicada — DoctorTurn',
                'body' => "Olá, {nome}!\n\nA escala {quadro} — {mês} do hospital {hospital} foi publicada.\n\nAcesse o DoctorTurn para ver seus plantões e confirmá-los.",
                'desc' => 'Enviado aos médicos com plantão quando uma escala é publicada.',
            ],
            'escala_admin' => [
                'label' => 'Escala publicada (cópia administrativa)',
                'subject' => 'Escala de {mês} publicada — DoctorTurn',
                'body' => "Olá, {nome}!\n\nA escala {quadro} — {mês} do hospital {hospital} foi publicada.\n\nEsta é a sua cópia administrativa da distribuição.",
                'desc' => 'Cópia enviada ao endereço administrativo configurado.',
            ],
            'convite' => [
                'label' => 'Convite de médico',
                'subject' => 'Convite para o {hospital} — DoctorTurn',
                'body' => "Você foi convidado para fazer parte da equipe do {hospital}.\n\nClique no link para criar sua conta e começar a receber escalas.",
                'desc' => 'Enviado quando o gestor convida um médico por e-mail.',
            ],
            'boas_vindas' => [
                'label' => 'Boas-vindas (novo administrador)',
                'subject' => 'Bem-vindo ao DoctorTurn',
                'body' => "Olá, {nome}!\n\nSua conta de administrador foi criada no DoctorTurn.\n\nAcesse a plataforma para começar.",
                'desc' => 'Enviado na criação de uma conta de administrador.',
            ],
            'troca' => [
                'label' => 'Troca atualizada',
                'subject' => '{evento da troca} — DoctorTurn',
                'body' => "Olá, {nome}!\n\nHá uma atualização sobre uma troca de plantão.\n\n{detalhes da troca}",
                'desc' => 'Enviado nas mudanças de estado de uma troca de plantão.',
            ],
        ];
    }

    /**
     * Corpo do template de WhatsApp ativo (escala_publicada_v3).
     */
    private function whatsappBody(): string
    {
        return "Olá, {{1}}! A escala {{2}} de {{3}}, referente a {{4}}, foi publicada no *DoctorTurn*.\n\nAcesse a plataforma para consultar seus plantões e confirmar sua escala:\nhttps://doctorturn.com.br/medico";
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

        $calendar = null;
        $metrics = null;

        if ($this->selectedScheduleId !== null) {
            $selectedSchedule = Schedule::with(['hospital', 'board', 'shifts.doctor'])
                ->find($this->selectedScheduleId);

            if ($selectedSchedule !== null) {
                [$calendar, $metrics] = $this->buildScheduleCalendar($selectedSchedule);
            }
        }

        return [
            'managers' => $managers,
            'selectedManager' => $this->selectedManagerId !== null ? $managers->firstWhere('id', $this->selectedManagerId) : null,
            'schedulesByHospital' => $schedules,
            'selectedSchedule' => $selectedSchedule,
            'calendar' => $calendar,
            'metrics' => $metrics,
        ];
    }

    /**
     * Monta o calendário mensal e as métricas de uma escala.
     *
     * @return array{0: array<string, mixed>, 1: array<string, int>}
     */
    private function buildScheduleCalendar(Schedule $schedule): array
    {
        $shifts = $schedule->shifts->groupBy(fn ($s) => $s->date->toDateString());

        $start = \Illuminate\Support\Carbon::create($schedule->year, $schedule->month, 1);
        $end = $start->copy()->endOfMonth();

        $days = [];
        $cursor = $start->copy();
        while ($cursor->lte($end)) {
            $dayShifts = $shifts->get($cursor->toDateString(), collect());
            $days[] = [
                'date' => $cursor->copy(),
                'items' => $dayShifts->sortBy('starts_at')->values(),
            ];
            $cursor->addDay();
        }

        $total = $schedule->shifts->count();
        $filled = $schedule->shifts->whereNotNull('user_id')->count();
        $doctors = $schedule->shifts->whereNotNull('user_id')->pluck('user_id')->unique()->count();

        $metrics = [
            'total' => $total,
            'filled' => $filled,
            'empty' => $total - $filled,
            'doctors' => $doctors,
        ];

        return [[
            'days' => $days,
            'leadingBlanks' => (int) $start->dayOfWeek,
            'monthLabel' => ucfirst($start->translatedFormat('F \d\e Y')),
        ], $metrics];
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
            {{-- Cards de canais --}}
            <section class="grid gap-4 lg:grid-cols-2">
                {{-- E-mails programados --}}
                <div class="rounded-xl border border-blue-100 bg-gradient-to-br from-blue-50 to-white p-5 shadow-sm">
                    <div class="flex items-center gap-2">
                        <span class="inline-flex h-9 w-9 items-center justify-center rounded-lg bg-blue-600 text-white">
                            <svg class="h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 8l7.89 5.26a2 2 0 002.22 0L21 8M5 19h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z"/></svg>
                        </span>
                        <div>
                            <p class="text-sm font-semibold text-gray-900">E-mails programados</p>
                            <p class="text-xs text-gray-500">{{ count($comunicacao['emailTemplates']) }} modelos configurados</p>
                        </div>
                    </div>
                    <div class="mt-4 space-y-1.5">
                        @foreach ($comunicacao['emailTemplates'] as $key => $tpl)
                            <button wire:click="selectEmailTemplate('{{ $key }}')" type="button"
                                class="flex w-full items-center justify-between rounded-lg border px-3 py-2 text-left text-sm transition
                                    {{ $selectedEmailTemplate === $key ? 'border-blue-300 bg-blue-100/70 text-blue-900' : 'border-gray-100 bg-white text-gray-700 hover:border-blue-200 hover:bg-blue-50' }}">
                                <span class="font-medium">{{ $tpl['label'] }}</span>
                                <svg class="h-4 w-4 shrink-0 transition {{ $selectedEmailTemplate === $key ? 'rotate-180' : '' }}" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"/></svg>
                            </button>
                            @if ($selectedEmailTemplate === $key)
                                <div class="rounded-lg border border-blue-200 bg-white p-4 shadow-inner">
                                    <p class="text-xs text-gray-500">{{ $tpl['desc'] }}</p>
                                    <p class="mt-2 text-xs font-semibold uppercase text-gray-400">Assunto</p>
                                    <p class="text-sm text-gray-800">{{ $tpl['subject'] }}</p>
                                    <p class="mt-3 text-xs font-semibold uppercase text-gray-400">Corpo</p>
                                    <div class="mt-1 whitespace-pre-line rounded-md bg-gray-50 p-3 font-mono text-xs leading-relaxed text-gray-700">{{ $tpl['body'] }}</div>
                                </div>
                            @endif
                        @endforeach
                    </div>
                </div>

                {{-- WhatsApp programado --}}
                <div class="rounded-xl border border-green-100 bg-gradient-to-br from-green-50 to-white p-5 shadow-sm">
                    <div class="flex items-center gap-2">
                        <span class="inline-flex h-9 w-9 items-center justify-center rounded-lg bg-green-600 text-white">
                            <svg class="h-5 w-5" fill="currentColor" viewBox="0 0 24 24"><path d="M17.5 14.4c-.3-.15-1.76-.87-2.03-.97-.27-.1-.47-.15-.67.15-.2.3-.77.97-.94 1.17-.17.2-.35.22-.65.07-.3-.15-1.26-.46-2.4-1.48-.88-.79-1.48-1.76-1.65-2.06-.17-.3-.02-.46.13-.61.14-.13.3-.35.45-.52.15-.17.2-.3.3-.5.1-.2.05-.37-.02-.52-.08-.15-.67-1.62-.92-2.22-.24-.58-.49-.5-.67-.51h-.57c-.2 0-.52.07-.8.37-.27.3-1.04 1.02-1.04 2.5 0 1.47 1.07 2.9 1.22 3.1.15.2 2.11 3.22 5.1 4.51.71.31 1.27.49 1.7.63.72.23 1.37.2 1.88.12.58-.09 1.76-.72 2.01-1.42.25-.7.25-1.29.17-1.42-.07-.13-.27-.2-.57-.35zM12.05 21.79h-.01a9.87 9.87 0 01-5.03-1.38l-.36-.21-3.74.98 1-3.65-.24-.37a9.83 9.83 0 01-1.51-5.26c0-5.44 4.43-9.87 9.88-9.87a9.82 9.82 0 016.98 2.9 9.82 9.82 0 012.9 6.98c0 5.44-4.43 9.87-9.87 9.87zm8.42-18.29A11.8 11.8 0 0012.04 0C5.46 0 .1 5.35.1 11.93c0 2.1.55 4.16 1.6 5.97L0 24l6.24-1.64a11.9 11.9 0 005.8 1.48h.01c6.58 0 11.93-5.35 11.93-11.93 0-3.19-1.24-6.18-3.5-8.42z"/></svg>
                        </span>
                        <div>
                            <p class="text-sm font-semibold text-gray-900">WhatsApp programado</p>
                            <p class="text-xs text-gray-500">
                                <span class="inline-flex items-center gap-1 rounded-full px-2 py-0.5 font-medium {{ $comunicacao['whatsappEnabled'] ? 'bg-green-100 text-green-700' : 'bg-gray-100 text-gray-500' }}">
                                    {{ $comunicacao['whatsappEnabled'] ? 'Ativo' : 'Desativado' }}
                                </span>
                            </p>
                        </div>
                    </div>
                    <p class="mt-3 text-xs font-semibold uppercase text-gray-400">Template ativo</p>
                    <p class="text-sm font-medium text-gray-800">{{ $comunicacao['whatsappVersion'] }}</p>

                    <div class="mt-4 rounded-xl bg-[#0b141a] p-4">
                        <p class="mb-3 text-[11px] font-medium uppercase tracking-wide text-gray-400">Simulação de conversa</p>
                        <div class="flex flex-col gap-2">
                            <div class="flex justify-start">
                                <div class="max-w-[85%] rounded-lg rounded-tl-none bg-[#202c33] px-3 py-2 text-sm text-gray-100 shadow">
                                    <div class="whitespace-pre-line leading-relaxed">{!! str_replace('*DoctorTurn*', '<strong>DoctorTurn</strong>', e($comunicacao['whatsappBody'])) !!}</div>
                                    <div class="mt-1 text-right text-[10px] text-gray-400">13:24 ✓✓</div>
                                </div>
                            </div>
                        </div>
                    </div>
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

                {{-- Painel individual / envios --}}
                <section class="space-y-4">
                    @if ($comunicacao['selectedUser'])
                        @php $su = $comunicacao['selectedUser']; @endphp
                        <div class="rounded-xl border border-teal-100 bg-gradient-to-r from-teal-600 to-teal-700 p-5 text-white shadow">
                            <div class="flex items-center gap-3">
                                <span class="inline-flex h-11 w-11 items-center justify-center rounded-full bg-white/20 text-base font-bold">
                                    {{ \Illuminate\Support\Str::of($su->name)->explode(' ')->take(2)->map(fn ($p) => \Illuminate\Support\Str::substr($p, 0, 1))->implode('') }}
                                </span>
                                <div>
                                    <p class="font-semibold">{{ $su->name }}</p>
                                    <p class="text-xs text-teal-100">{{ $su->email }} · {{ $su->phone ?? 'sem celular' }}</p>
                                </div>
                            </div>
                        </div>

                        <div class="rounded-xl border border-gray-200 bg-white p-4">
                            <p class="text-sm font-semibold text-gray-800">Mensagens enviadas</p>
                            <div class="mt-3 space-y-2">
                                @forelse ($comunicacao['selectedLogs'] as $log)
                                    <button wire:click="selectLog({{ $log->id }})" type="button"
                                        class="flex w-full items-center justify-between gap-3 rounded-lg border px-4 py-2.5 text-left text-sm transition
                                            {{ $selectedLogId === $log->id ? 'border-teal-300 bg-teal-50' : 'border-gray-100 bg-gray-50 hover:border-gray-200' }}">
                                        <div class="min-w-0">
                                            <span class="inline-flex rounded-full px-2 py-0.5 text-xs font-medium {{ $log->channel === 'whatsapp' ? 'bg-green-100 text-green-700' : 'bg-blue-100 text-blue-700' }}">{{ $log->channelLabel() }}</span>
                                            <span class="ml-2 text-gray-700">{{ $log->subject ?? $log->template ?? '—' }}</span>
                                        </div>
                                        <span class="shrink-0 text-xs text-gray-400">{{ $log->created_at->format('d/m H:i') }}</span>
                                    </button>

                                    @if ($selectedLogId === $log->id && $comunicacao['selectedLog'])
                                        @php $sl = $comunicacao['selectedLog']; @endphp
                                        <div class="rounded-xl bg-[#0b141a] p-4">
                                            <div class="flex justify-start">
                                                <div class="max-w-[90%] rounded-lg rounded-tl-none px-3 py-2 text-sm shadow
                                                    {{ $sl->channel === 'whatsapp' ? 'bg-[#202c33] text-gray-100' : 'bg-white text-gray-800 border border-gray-200' }}">
                                                    @if ($sl->channel !== 'whatsapp' && $sl->subject)
                                                        <p class="mb-1 border-b border-gray-100 pb-1 text-xs font-semibold text-gray-500">{{ $sl->subject }}</p>
                                                    @endif
                                                    <div class="whitespace-pre-line leading-relaxed">{!! str_replace('*DoctorTurn*', '<strong>DoctorTurn</strong>', e($sl->body ?? $sl->subject ?? $sl->template ?? '—')) !!}</div>
                                                    <div class="mt-1 text-right text-[10px] {{ $sl->channel === 'whatsapp' ? 'text-gray-400' : 'text-gray-400' }}">{{ $sl->created_at->format('H:i') }} ✓✓</div>
                                                </div>
                                            </div>
                                        </div>
                                    @endif
                                @empty
                                    <p class="text-sm text-gray-400">Nenhuma mensagem registrada para este usuário ainda.</p>
                                @endforelse
                            </div>
                        </div>
                    @else
                        <div class="rounded-xl border border-gray-200 bg-white p-4">
                            <p class="text-sm font-semibold text-gray-800">Envios recentes (todos os médicos)</p>
                            <div class="mt-3 space-y-2">
                                @forelse ($comunicacao['logs'] as $log)
                                    <div class="flex items-center justify-between gap-3 rounded-lg border border-gray-100 bg-gray-50 px-4 py-2.5 text-sm">
                                        <div class="min-w-0">
                                            <span class="inline-flex rounded-full px-2 py-0.5 text-xs font-medium {{ $log->channel === 'whatsapp' ? 'bg-green-100 text-green-700' : 'bg-blue-100 text-blue-700' }}">{{ $log->channelLabel() }}</span>
                                            <span class="ml-2 font-medium text-gray-800">{{ $log->user?->name ?? '—' }}</span>
                                            <span class="block text-xs text-gray-400">{{ $log->subject ?? $log->template ?? '—' }} · para {{ $log->recipient }}</span>
                                        </div>
                                        <span class="shrink-0 text-xs text-gray-400">{{ $log->created_at->format('d/m H:i') }}</span>
                                    </div>
                                @empty
                                    <p class="text-sm text-gray-400">Nenhum envio registrado ainda. Os próximos envios de e-mail e WhatsApp passam a ser listados aqui automaticamente.</p>
                                @endforelse
                            </div>
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

                    @if ($plantoes['selectedSchedule'] && $plantoes['calendar'])
                        @php $ss = $plantoes['selectedSchedule']; $cal = $plantoes['calendar']; $mt = $plantoes['metrics']; @endphp

                        {{-- Métricas --}}
                        <div class="grid grid-cols-2 gap-4 sm:grid-cols-4">
                            <div class="rounded-xl border border-teal-100 bg-white p-4 text-center shadow-sm">
                                <p class="text-2xl font-bold text-teal-700">{{ $mt['total'] }}</p>
                                <p class="text-xs text-gray-500">Plantões</p>
                            </div>
                            <div class="rounded-xl border border-green-100 bg-white p-4 text-center shadow-sm">
                                <p class="text-2xl font-bold text-green-600">{{ $mt['filled'] }}</p>
                                <p class="text-xs text-gray-500">Preenchidos</p>
                            </div>
                            <div class="rounded-xl border border-red-100 bg-white p-4 text-center shadow-sm">
                                <p class="text-2xl font-bold text-red-500">{{ $mt['empty'] }}</p>
                                <p class="text-xs text-gray-500">Sem médico</p>
                            </div>
                            <div class="rounded-xl border border-indigo-100 bg-white p-4 text-center shadow-sm">
                                <p class="text-2xl font-bold text-indigo-600">{{ $mt['doctors'] }}</p>
                                <p class="text-xs text-gray-500">Médicos</p>
                            </div>
                        </div>

                        {{-- Calendário real --}}
                        <div class="rounded-xl border border-gray-200 bg-white p-4 shadow-sm">
                            <div class="mb-3 flex items-center justify-between">
                                <p class="font-semibold text-gray-900">{{ $cal['monthLabel'] }}</p>
                                <p class="text-xs text-gray-400">{{ $ss->hospital->name }} · publicada em {{ $ss->published_at?->format('d/m/Y') }}</p>
                            </div>
                            <div class="grid grid-cols-7 gap-1.5">
                                @foreach (['Dom','Seg','Ter','Qua','Qui','Sex','Sáb'] as $wd)
                                    <div class="pb-1 text-center text-xs font-semibold text-gray-400">{{ $wd }}</div>
                                @endforeach
                                @for ($i = 0; $i < $cal['leadingBlanks']; $i++)
                                    <div></div>
                                @endfor
                                @foreach ($cal['days'] as $day)
                                    <div class="flex min-h-[92px] flex-col gap-1 rounded-lg border border-gray-100 bg-gray-50/50 p-1">
                                        <div class="pr-1 text-right text-xs font-semibold text-gray-500">{{ $day['date']->day }}</div>
                                        @foreach ($day['items'] as $shift)
                                            @php
                                                $isDay = $shift->period === 'dia';
                                                $colors = $isDay
                                                    ? 'border-amber-200 bg-amber-50 text-amber-800'
                                                    : 'border-indigo-200 bg-indigo-50 text-indigo-800';
                                            @endphp
                                            <div class="rounded-md border px-1.5 py-1 text-[10px] leading-tight {{ $shift->doctor ? $colors.' bg-white' : 'border-dashed border-gray-300 text-gray-400' }}" title="{{ $shift->starts_at->format('H:i') }}–{{ $shift->ends_at->format('H:i') }}">
                                                <span class="font-semibold">{{ $shift->starts_at->format('H:i') }}</span>
                                                <span class="block truncate">{{ $shift->doctor?->name ?? 'Sem médico' }}</span>
                                            </div>
                                        @endforeach
                                    </div>
                                @endforeach
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
