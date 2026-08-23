<?php

use App\Enums\ScheduleStatus;
use App\Enums\ShiftStatus;
use App\Enums\TransferStatus;
use App\Models\Schedule;
use App\Models\Shift;
use App\Models\ShiftTransfer;
use Illuminate\Support\Carbon;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Url;
use Livewire\Volt\Component;

new #[Layout('layouts.app')] class extends Component
{
    #[Url]
    public string $month = '';

    public function mount(): void
    {
        if ($this->month === '' || ! preg_match('/^\d{4}-\d{2}$/', $this->month)) {
            $this->month = now()->format('Y-m');
        }
    }

    public function previousMonth(): void
    {
        $this->month = Carbon::parse($this->month.'-01')->subMonth()->format('Y-m');
    }

    public function nextMonth(): void
    {
        $this->month = Carbon::parse($this->month.'-01')->addMonth()->format('Y-m');
    }

    /**
     * @return array<string, mixed>
     */
    public function with(): array
    {
        $hospital = currentHospital();
        $start = Carbon::parse($this->month.'-01')->startOfMonth();
        $end = $start->copy()->endOfMonth();
        $monthLabel = $start->translatedFormat('F/Y');

        $gestor = null;

        if ($hospital !== null) {
            $payable = [ShiftStatus::Confirmado, ShiftStatus::Concluido];
            $dead = [ShiftStatus::Cancelado, ShiftStatus::NaoCumprido];

            $shifts = Shift::query()
                ->where('hospital_id', $hospital->id)
                ->whereBetween('date', [$start, $end])
                ->whereHas('schedule', fn ($q) => $q->where('status', ScheduleStatus::Publicada))
                ->with(['doctor', 'schedule.board'])
                ->get();

            $active = $shifts->reject(fn ($s) => in_array($s->status, $dead, true));
            $assigned = $active->whereNotNull('user_id');
            $uncovered = $active->whereNull('user_id');

            $custoPrevisto = $assigned->sum(fn ($s) => (float) $s->amount);
            $custoConfirmado = $assigned->filter(fn ($s) => in_array($s->status, $payable, true))->sum(fn ($s) => (float) $s->amount);
            $horas = $assigned->sum(fn ($s) => $s->starts_at->diffInMinutes($s->ends_at)) / 60;

            $prevStart = $start->copy()->subMonth();
            $custoAnterior = (float) Shift::query()
                ->where('hospital_id', $hospital->id)
                ->whereNotNull('user_id')
                ->whereBetween('date', [$prevStart, $prevStart->copy()->endOfMonth()])
                ->whereNotIn('status', array_map(fn ($s) => $s->value, $dead))
                ->whereHas('schedule', fn ($q) => $q->where('status', ScheduleStatus::Publicada))
                ->sum('amount');

            $variacao = $custoAnterior > 0 ? (($custoPrevisto - $custoAnterior) / $custoAnterior) * 100 : null;

            $topDoctors = $assigned
                ->groupBy('user_id')
                ->map(fn ($group) => [
                    'doctor' => $group->first()->doctor,
                    'count' => $group->count(),
                    'total' => $group->sum(fn ($s) => (float) $s->amount),
                ])
                ->sortByDesc('total')
                ->take(5)
                ->values();

            // Visão de alocação (acima/abaixo/conforme o planejado)
            $balances = app(\App\Services\GridService::class)->balancesForSchedule($hospital->schedules()->where('status', ScheduleStatus::Publicada)->where('year', $start->year)->where('month', $start->month)->first() ?? new \App\Models\Schedule);
            $alocacao = [
                'acima' => collect($balances)->filter(fn ($b) => $b['limite'] !== null && $b['consumo_limite'] > $b['limite'])->count(),
                'conforme' => collect($balances)->filter(fn ($b) => $b['limite'] !== null && $b['consumo_limite'] <= $b['limite'])->count(),
                'semLimite' => collect($balances)->filter(fn ($b) => $b['limite'] === null)->count(),
            ];

            // Alertas de conformidade
            $alertas = [];
            foreach ($assigned as $shift) {
                if ($shift->doctor !== null) {
                    $violations = app(\App\Services\ComplianceService::class)->check($hospital, $shift->doctor, $shift);
                    foreach ($violations as $v) {
                        if ($v['blocking']) {
                            $alertas[] = [
                                'doctor' => $shift->doctor->name,
                                'date' => $shift->date->format('d/m'),
                                'message' => $v['message'],
                            ];
                        }
                    }
                }
            }

            $upcoming = Shift::query()
                ->where('hospital_id', $hospital->id)
                ->whereBetween('date', [now()->startOfDay(), now()->copy()->addDays(7)->endOfDay()])
                ->whereNotIn('status', array_map(fn ($s) => $s->value, $dead))
                ->whereHas('schedule', fn ($q) => $q->where('status', ScheduleStatus::Publicada))
                ->with(['doctor', 'schedule.board'])
                ->orderBy('date')->orderBy('starts_at')
                ->limit(8)
                ->get();

            $gestor = [
                'totalPlantoes' => $active->count(),
                'preenchidos' => $assigned->count(),
                'descobertos' => $uncovered->count(),
                'cobertura' => $active->count() > 0 ? round($assigned->count() / $active->count() * 100) : null,
                'custoPrevisto' => $custoPrevisto,
                'custoConfirmado' => $custoConfirmado,
                'variacao' => $variacao,
                'horas' => $horas,
                'medicos' => $hospital->memberships()->where('role', 'medico')->where('active', true)->count(),
                'trocasPendentes' => ShiftTransfer::where('status', TransferStatus::AguardandoGestor)
                    ->whereHas('shift', fn ($q) => $q->where('hospital_id', $hospital->id))->count(),
                'rascunhos' => Schedule::where('hospital_id', $hospital->id)->where('status', ScheduleStatus::Rascunho)->count(),
                'topDoctors' => $topDoctors,
                'upcoming' => $upcoming,
                'alocacao' => $alocacao,
                'alertas' => $alertas,
            ];
        }

        $medico = null;

        if (auth()->user()->doctorHospitals()->exists()) {
            $payable = [ShiftStatus::Pendente, ShiftStatus::Confirmado, ShiftStatus::Concluido, ShiftStatus::EmTroca];

            $myShifts = Shift::query()
                ->where('user_id', auth()->id())
                ->whereBetween('date', [$start, $end])
                ->whereIn('status', array_map(fn ($s) => $s->value, $payable))
                ->whereHas('schedule', fn ($q) => $q->where('status', ScheduleStatus::Publicada))
                ->get();

            $next = Shift::query()
                ->where('user_id', auth()->id())
                ->where('date', '>=', now()->startOfDay())
                ->whereIn('status', [ShiftStatus::Pendente->value, ShiftStatus::Confirmado->value])
                ->whereHas('schedule', fn ($q) => $q->where('status', ScheduleStatus::Publicada))
                ->with('hospital')
                ->orderBy('date')->orderBy('starts_at')
                ->first();

            $medico = [
                'plantoesMes' => $myShifts->count(),
                'valorMes' => $myShifts->sum(fn ($s) => (float) $s->amount),
                'pendentes' => $myShifts->where('status', ShiftStatus::Pendente)->count(),
                'proximo' => $next,
            ];
        }

        return [
            'hospital' => $hospital,
            'gestor' => $gestor,
            'medico' => $medico,
            'monthLabel' => $monthLabel,
        ];
    }
}; ?>

<div>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 leading-tight">
            Painel @if ($hospital) — {{ $hospital->name }} @endif
        </h2>
    </x-slot>

    <div class="py-8">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 space-y-6">

            {{-- Saudação --}}
            <div class="rounded-xl bg-brand-dark text-white p-6 shadow-md relative overflow-hidden">
                <div class="absolute -right-10 -top-16 w-56 h-56 rounded-full bg-brand-teal/15"></div>
                <div class="absolute right-24 -bottom-20 w-40 h-40 rounded-full bg-brand-green/10"></div>
                <p class="text-lg font-semibold relative">Olá, {{ auth()->user()->name }}! 👋</p>
                <p class="text-sm text-teal-100/80 mt-1 relative">
                    @if ($gestor)
                        Aqui está o resumo de <span class="capitalize font-medium text-brand-teal">{{ $monthLabel }}</span>{{ $hospital ? " em {$hospital->name}" : '' }}.
                    @else
                        Bem-vindo(a) ao DoctorTurn.
                    @endif
                </p>
            </div>

            @if ($medico)
                {{-- Resumo do médico --}}
                <div class="grid grid-cols-1 sm:grid-cols-3 gap-4">
                    <div class="bg-white shadow-sm rounded-xl p-5 border-l-4 border-brand-teal">
                        <p class="text-sm text-gray-500">Meus plantões no mês</p>
                        <p class="text-2xl font-bold text-gray-900 mt-1">{{ $medico['plantoesMes'] }}</p>
                        @if ($medico['pendentes'] > 0)
                            <p class="text-xs text-amber-600 mt-1">{{ $medico['pendentes'] }} aguardando confirmação</p>
                        @endif
                        <a href="{{ route('medico.painel') }}" wire:navigate class="text-xs text-brand-teal-dark font-medium hover:underline mt-2 inline-block">Ver meus plantões →</a>
                    </div>
                    <div class="bg-white shadow-sm rounded-xl p-5 border-l-4 border-brand-green">
                        <p class="text-sm text-gray-500">Previsão de recebimento</p>
                        <p class="text-2xl font-bold text-gray-900 mt-1">R$ {{ number_format($medico['valorMes'], 2, ',', '.') }}</p>
                        <p class="text-xs text-gray-400 mt-1">plantões ativos do mês</p>
                    </div>
                    <div class="bg-white shadow-sm rounded-xl p-5 border-l-4 border-brand-dark">
                        <p class="text-sm text-gray-500">Próximo plantão</p>
                        @if ($medico['proximo'])
                            <p class="text-lg font-bold text-gray-900 mt-1">{{ $medico['proximo']->date->translatedFormat('d/m (D)') }}</p>
                            <p class="text-xs text-gray-500">{{ $medico['proximo']->starts_at->format('H:i') }}–{{ $medico['proximo']->ends_at->format('H:i') }} · {{ $medico['proximo']->hospital->name }}</p>
                        @else
                            <p class="text-lg font-medium text-gray-400 mt-1">Nenhum agendado</p>
                        @endif
                    </div>
                </div>
            @endif

            @if ($gestor)
                {{-- Navegação de mês --}}
                <div class="flex items-center justify-between">
                    <x-secondary-button wire:click="previousMonth" type="button">← Anterior</x-secondary-button>
                    <h3 class="text-lg font-semibold text-gray-900 capitalize">{{ $monthLabel }}</h3>
                    <x-secondary-button wire:click="nextMonth" type="button">Próximo →</x-secondary-button>
                </div>

                {{-- KPIs financeiros --}}
                <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-4">
                    <div class="bg-white shadow-sm rounded-xl p-5">
                        <p class="text-sm text-gray-500">Custo previsto do mês</p>
                        <p class="text-2xl font-bold text-brand-dark mt-1">R$ {{ number_format($gestor['custoPrevisto'], 2, ',', '.') }}</p>
                        @if ($gestor['variacao'] !== null)
                            <p class="text-xs mt-1 font-medium {{ $gestor['variacao'] >= 0 ? 'text-amber-600' : 'text-brand-green-dark' }}">
                                {{ $gestor['variacao'] >= 0 ? '▲' : '▼' }} {{ number_format(abs($gestor['variacao']), 1, ',', '.') }}% vs mês anterior
                            </p>
                        @else
                            <p class="text-xs text-gray-400 mt-1">sem base de comparação</p>
                        @endif
                    </div>
                    <div class="bg-white shadow-sm rounded-xl p-5">
                        <p class="text-sm text-gray-500">Já confirmado/concluído</p>
                        <p class="text-2xl font-bold text-brand-green-dark mt-1">R$ {{ number_format($gestor['custoConfirmado'], 2, ',', '.') }}</p>
                        <p class="text-xs text-gray-400 mt-1">
                            {{ $gestor['custoPrevisto'] > 0 ? round($gestor['custoConfirmado'] / $gestor['custoPrevisto'] * 100) : 0 }}% do previsto
                        </p>
                    </div>
                    <div class="bg-white shadow-sm rounded-xl p-5">
                        <p class="text-sm text-gray-500">Cobertura da escala</p>
                        @if ($gestor['cobertura'] !== null)
                            <p class="text-2xl font-bold {{ $gestor['descobertos'] > 0 ? 'text-amber-600' : 'text-brand-teal-dark' }} mt-1">{{ $gestor['cobertura'] }}%</p>
                            <div class="w-full bg-gray-100 rounded-full h-1.5 mt-2">
                                <div class="h-1.5 rounded-full {{ $gestor['descobertos'] > 0 ? 'bg-amber-500' : 'bg-brand-teal' }}" style="width: {{ $gestor['cobertura'] }}%"></div>
                            </div>
                            <p class="text-xs text-gray-400 mt-1">{{ $gestor['preenchidos'] }}/{{ $gestor['totalPlantoes'] }} plantões · {{ $gestor['descobertos'] }} descoberto(s)</p>
                        @else
                            <p class="text-2xl font-bold text-gray-400 mt-1">—</p>
                            <p class="text-xs text-gray-400 mt-1">nenhuma escala publicada</p>
                        @endif
                    </div>
                    <div class="bg-white shadow-sm rounded-xl p-5">
                        <p class="text-sm text-gray-500">Horas de plantão</p>
                        <p class="text-2xl font-bold text-brand-dark mt-1">{{ number_format($gestor['horas'], 0, ',', '.') }}h</p>
                        <p class="text-xs text-gray-400 mt-1">{{ $gestor['medicos'] }} médico(s) na equipe</p>
                    </div>
                </div>

                {{-- Alertas + ações rápidas --}}
                <div class="grid grid-cols-1 sm:grid-cols-3 gap-4">
                    <a href="{{ route('gestor.trocas') }}" wire:navigate class="bg-white shadow-sm rounded-xl p-5 hover:shadow-md transition-shadow border-t-2 {{ $gestor['trocasPendentes'] > 0 ? 'border-amber-500' : 'border-transparent' }}">
                        <p class="text-sm text-gray-500">Trocas aguardando você</p>
                        <p class="text-2xl font-bold {{ $gestor['trocasPendentes'] > 0 ? 'text-amber-600' : 'text-gray-900' }} mt-1">{{ $gestor['trocasPendentes'] }}</p>
                        <p class="text-xs text-brand-teal-dark font-medium mt-1">Ver trocas →</p>
                    </a>
                    <a href="{{ route('gestor.escalas') }}" wire:navigate class="bg-white shadow-sm rounded-xl p-5 hover:shadow-md transition-shadow border-t-2 {{ $gestor['rascunhos'] > 0 ? 'border-brand-teal' : 'border-transparent' }}">
                        <p class="text-sm text-gray-500">Escalas em rascunho</p>
                        <p class="text-2xl font-bold text-gray-900 mt-1">{{ $gestor['rascunhos'] }}</p>
                        <p class="text-xs text-brand-teal-dark font-medium mt-1">Ver escalas →</p>
                    </a>
                    <a href="{{ route('gestor.faturamento') }}" wire:navigate class="bg-white shadow-sm rounded-xl p-5 hover:shadow-md transition-shadow border-t-2 border-transparent">
                        <p class="text-sm text-gray-500">Faturamento detalhado</p>
                        <p class="text-2xl font-bold text-gray-900 mt-1">R$</p>
                        <p class="text-xs text-brand-teal-dark font-medium mt-1">Abrir faturamento →</p>
                    </a>
                </div>

                {{-- Visão de alocação --}}
                <div class="bg-white shadow-sm rounded-xl p-5">
                    <h3 class="text-sm font-semibold text-gray-800 mb-4">Alocação de horas</h3>
                    <div class="grid grid-cols-3 gap-4">
                        <div class="text-center">
                            <p class="text-2xl font-bold text-red-600">{{ $gestor['alocacao']['acima'] }}</p>
                            <p class="text-xs text-gray-500">Acima do limite</p>
                        </div>
                        <div class="text-center">
                            <p class="text-2xl font-bold text-brand-green-dark">{{ $gestor['alocacao']['conforme'] }}</p>
                            <p class="text-xs text-gray-500">Conforme</p>
                        </div>
                        <div class="text-center">
                            <p class="text-2xl font-bold text-gray-400">{{ $gestor['alocacao']['semLimite'] }}</p>
                            <p class="text-xs text-gray-500">Sem limite definido</p>
                        </div>
                    </div>
                </div>

                {{-- Alertas de conformidade --}}
                @if (count($gestor['alertas']) > 0)
                    <div class="bg-red-50 border border-red-200 rounded-xl p-5">
                        <h3 class="text-sm font-semibold text-red-800 mb-3">⚠️ Alertas de conformidade ({{ count($gestor['alertas']) }})</h3>
                        <ul class="space-y-2">
                            @foreach (array_slice($gestor['alertas'], 0, 5) as $alerta)
                                <li class="text-sm text-red-700">
                                    <strong>{{ $alerta['doctor'] }}</strong> — {{ $alerta['date'] }}: {{ $alerta['message'] }}
                                </li>
                            @endforeach
                            @if (count($gestor['alertas']) > 5)
                                <li class="text-xs text-red-500">+ {{ count($gestor['alertas']) - 5 }} mais...</li>
                            @endif
                        </ul>
                    </div>
                @endif

                <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
                    {{-- Próximos 7 dias --}}
                    <div class="bg-white shadow-sm rounded-xl overflow-hidden">
                        <div class="px-6 py-4 border-b border-gray-100 flex items-center justify-between">
                            <h3 class="font-semibold text-gray-900">Próximos 7 dias</h3>
                            <span class="text-xs text-gray-400">plantões publicados</span>
                        </div>
                        @forelse ($gestor['upcoming'] as $shift)
                            <div class="px-6 py-3 border-b border-gray-50 last:border-b-0 flex items-center justify-between gap-3 text-sm">
                                <div class="min-w-0">
                                    <p class="font-medium text-gray-900 truncate">
                                        {{ $shift->date->translatedFormat('d/m (D)') }} · {{ $shift->starts_at->format('H:i') }}–{{ $shift->ends_at->format('H:i') }}
                                    </p>
                                    <p class="text-xs text-gray-500 truncate">{{ $shift->schedule->board?->name ?? $shift->hospital->name }} · {{ $shift->doctor?->name ?? 'Sem médico' }}</p>
                                </div>
                                <span class="shrink-0 text-xs px-2 py-0.5 rounded-full
                                    @if ($shift->status === ShiftStatus::Confirmado) bg-brand-green-soft text-brand-green-dark
                                    @elseif ($shift->status === ShiftStatus::SemMedico || $shift->user_id === null) bg-red-100 text-red-700
                                    @else bg-amber-100 text-amber-700 @endif">
                                    {{ $shift->status->label() }}
                                </span>
                            </div>
                        @empty
                            <p class="px-6 py-8 text-sm text-gray-500 text-center">Nenhum plantão nos próximos 7 dias.</p>
                        @endforelse
                    </div>

                    {{-- Top médicos --}}
                    <div class="bg-white shadow-sm rounded-xl overflow-hidden">
                        <div class="px-6 py-4 border-b border-gray-100 flex items-center justify-between">
                            <h3 class="font-semibold text-gray-900">Top médicos do mês</h3>
                            <span class="text-xs text-gray-400">por valor previsto</span>
                        </div>
                        @php $maxTotal = $gestor['topDoctors']->max('total') ?: 1; @endphp
                        @forelse ($gestor['topDoctors'] as $row)
                            <div class="px-6 py-3 border-b border-gray-50 last:border-b-0">
                                <div class="flex items-center justify-between text-sm">
                                    <p class="font-medium text-gray-900 truncate">{{ $row['doctor']->name }}</p>
                                    <p class="text-gray-700 shrink-0 ms-3">R$ {{ number_format($row['total'], 2, ',', '.') }} <span class="text-xs text-gray-400">· {{ $row['count'] }} plantão(ões)</span></p>
                                </div>
                                <div class="w-full bg-gray-100 rounded-full h-1.5 mt-2">
                                    <div class="h-1.5 rounded-full bg-brand-teal" style="width: {{ round($row['total'] / $maxTotal * 100) }}%"></div>
                                </div>
                            </div>
                        @empty
                            <p class="px-6 py-8 text-sm text-gray-500 text-center">Nenhum plantão atribuído neste mês.</p>
                        @endforelse
                    </div>
                </div>

                {{-- Relatório PDF --}}
                <div class="bg-white shadow-sm rounded-xl overflow-hidden">
                    <div class="px-6 py-4 border-b border-gray-100 flex items-center gap-2">
                        <svg class="w-5 h-5 text-brand-teal" fill="none" stroke="currentColor" stroke-width="1.8" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M19.5 14.25v-2.625a3.375 3.375 0 0 0-3.375-3.375h-1.5A1.125 1.125 0 0 1 13.5 7.125v-1.5a3.375 3.375 0 0 0-3.375-3.375H8.25m6.75 12-3 3m0 0-3-3m3 3v-6m-1.5-9H5.625c-.621 0-1.125.504-1.125 1.125v17.25c0 .621.504 1.125 1.125 1.125h12.75c.621 0 1.125-.504 1.125-1.125V11.25a9 9 0 0 0-9-9Z"/></svg>
                        <h3 class="font-semibold text-gray-900">Relatório do mês em PDF</h3>
                    </div>
                    <form method="GET" action="{{ route('gestor.relatorio') }}" target="_blank" class="p-6">
                        <input type="hidden" name="month" value="{{ $month }}">
                        <p class="text-sm text-gray-500 mb-4">Escolha o que entra no relatório de <span class="capitalize font-medium text-gray-700">{{ $monthLabel }}</span>:</p>
                        <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-3 mb-6">
                            @foreach ([
                                'resumo' => ['Resumo geral', 'KPIs, custos e cobertura'],
                                'financeiro' => ['Financeiro por médico', 'totais a pagar por profissional'],
                                'plantoes' => ['Detalhamento de plantões', 'lista completa dia a dia'],
                                'descobertos' => ['Plantões descobertos', 'vagas ainda sem médico'],
                                'trocas' => ['Trocas do mês', 'histórico de repasses'],
                            ] as $key => [$label, $desc])
                                <label class="flex items-start gap-3 p-3 rounded-lg border border-gray-100 hover:border-brand-teal/40 cursor-pointer transition-colors">
                                    <input type="checkbox" name="sections[]" value="{{ $key }}" checked class="sr-only peer">
                                    <span class="mt-0.5 relative inline-flex h-5 w-9 shrink-0 rounded-full bg-gray-200 peer-checked:bg-brand-teal transition-colors after:content-[''] after:absolute after:top-0.5 after:left-0.5 after:h-4 after:w-4 after:rounded-full after:bg-white after:shadow after:transition-transform peer-checked:after:translate-x-4"></span>
                                    <span>
                                        <span class="block text-sm font-medium text-gray-900">{{ $label }}</span>
                                        <span class="block text-xs text-gray-400">{{ $desc }}</span>
                                    </span>
                                </label>
                            @endforeach
                        </div>
                        <x-primary-button type="submit">Gerar PDF completo</x-primary-button>
                    </form>
                </div>
            @endif

            @if (! $gestor && ! $medico)
                <div class="bg-white shadow-sm rounded-xl p-6 text-gray-500">
                    Você ainda não faz parte de nenhum hospital. Peça um convite ao seu gestor.
                </div>
            @endif
        </div>
    </div>
</div>
