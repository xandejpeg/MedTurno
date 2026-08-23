<?php

use App\Models\Tender;
use App\Models\TenderRequirement;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Url;
use Livewire\Volt\Component;

new #[Layout('layouts.admin')] class extends Component
{
    #[Url]
    public ?int $selectedTenderId = null;

    public string $filterStatus = '';

    public function selectTender(int $id): void
    {
        $this->selectedTenderId = $this->selectedTenderId === $id ? null : $id;
    }

    public function cycleRequirementStatus(int $id): void
    {
        $req = TenderRequirement::findOrFail($id);
        $order = ['faltando', 'parcial', 'pronto', 'na_aplicacao'];
        $next = $order[(array_search($req->status, $order, true) + 1) % count($order)];
        $req->update(['status' => $next]);
        $req->tender->recalcProgress();
    }

    public function setTenderStatus(int $id, string $status): void
    {
        Tender::findOrFail($id)->update(['status' => $status]);
    }

    /**
     * @return array<string, mixed>
     */
    public function with(): array
    {
        $tenders = Tender::with('requirements')->orderBy('id')->get();

        $selected = $this->selectedTenderId !== null
            ? $tenders->firstWhere('id', $this->selectedTenderId)
            : null;

        $requirements = collect();
        if ($selected !== null) {
            $requirements = $selected->requirements
                ->when($this->filterStatus !== '', fn ($c) => $c->where('status', $this->filterStatus))
                ->groupBy(fn ($r) => $r->category ?: 'Geral');
        }

        return [
            'tenders' => $tenders,
            'selected' => $selected,
            'requirements' => $requirements,
            'stats' => [
                'total' => $tenders->count(),
                'aplicando' => $tenders->whereIn('status', ['aplicando', 'em_andamento'])->count(),
                'concluidas' => $tenders->where('status', 'concluida')->count(),
                'media' => $tenders->count() > 0 ? (int) round($tenders->avg('progress')) : 0,
            ],
        ];
    }
}; ?>

<div class="px-4 py-6 sm:px-6 lg:px-10 lg:py-9">
    <header class="mb-6">
        <p class="text-xs font-semibold uppercase text-teal-700">Administrativo</p>
        <h1 class="mt-1 text-2xl font-semibold text-gray-950">Licitações</h1>
        <p class="mt-1 text-sm text-gray-500">Central de controle sobre os editais e o que precisamos adaptar no sistema para aplicar a cada um.</p>
    </header>

    {{-- Métricas --}}
    <section class="mb-6 grid grid-cols-2 gap-4 sm:grid-cols-4">
        <div class="rounded-xl border border-gray-200 bg-white p-4 text-center shadow-sm">
            <p class="text-2xl font-bold text-gray-900">{{ $stats['total'] }}</p>
            <p class="text-xs text-gray-500">Editais</p>
        </div>
        <div class="rounded-xl border border-teal-100 bg-white p-4 text-center shadow-sm">
            <p class="text-2xl font-bold text-teal-600">{{ $stats['aplicando'] }}</p>
            <p class="text-xs text-gray-500">Aplicando</p>
        </div>
        <div class="rounded-xl border border-green-100 bg-white p-4 text-center shadow-sm">
            <p class="text-2xl font-bold text-green-600">{{ $stats['concluidas'] }}</p>
            <p class="text-xs text-gray-500">Concluídas</p>
        </div>
        <div class="rounded-xl border border-indigo-100 bg-white p-4 text-center shadow-sm">
            <p class="text-2xl font-bold text-indigo-600">{{ $stats['media'] }}%</p>
            <p class="text-xs text-gray-500">Aderência média</p>
        </div>
    </section>

    <div class="grid gap-6 lg:grid-cols-[360px_1fr]">
        {{-- Lista de editais --}}
        <section class="space-y-3">
            @foreach ($tenders as $tender)
                <button wire:click="selectTender({{ $tender->id }})" type="button"
                    class="block w-full rounded-xl border p-4 text-left transition shadow-sm
                        {{ $selectedTenderId === $tender->id ? 'border-teal-400 bg-teal-50/60 ring-1 ring-teal-300' : 'border-gray-200 bg-white hover:border-teal-200' }}">
                    <div class="flex items-start justify-between gap-2">
                        <div class="min-w-0">
                            <p class="truncate font-semibold text-gray-900">{{ $tender->title }}</p>
                            <p class="mt-0.5 text-xs text-gray-500">{{ $tender->orgao }}@if ($tender->numero) · {{ $tender->numero }}@endif</p>
                        </div>
                        <span class="shrink-0 rounded-full px-2 py-0.5 text-xs font-medium
                            {{ $tender->status === 'concluida' ? 'bg-green-100 text-green-700' : ($tender->status === 'aplicando' || $tender->status === 'em_andamento' ? 'bg-teal-100 text-teal-700' : 'bg-gray-100 text-gray-600') }}">
                            {{ $tender->statusLabel() }}
                        </span>
                    </div>
                    <div class="mt-3">
                        <div class="flex items-center justify-between text-xs text-gray-500">
                            <span>Aderência</span>
                            <span class="font-semibold text-gray-700">{{ $tender->progress }}%</span>
                        </div>
                        <div class="mt-1 h-2 overflow-hidden rounded-full bg-gray-100">
                            <div class="h-full rounded-full transition-all {{ $tender->progress >= 75 ? 'bg-green-500' : ($tender->progress >= 40 ? 'bg-teal-500' : 'bg-amber-400') }}" style="width: {{ $tender->progress }}%"></div>
                        </div>
                    </div>
                </button>
            @endforeach
        </section>

        {{-- Detalhe do edital --}}
        <section>
            @if (! $selected)
                <div class="flex h-full items-center justify-center rounded-xl border border-dashed border-gray-300 bg-white p-12 text-center">
                    <div>
                        <p class="text-sm font-medium text-gray-500">Selecione um edital para ver os requisitos e o que precisamos adaptar.</p>
                    </div>
                </div>
            @else
                <div class="space-y-4">
                    <div class="rounded-xl border border-gray-200 bg-white p-5 shadow-sm">
                        <div class="flex flex-wrap items-start justify-between gap-3">
                            <div>
                                <h2 class="text-lg font-semibold text-gray-900">{{ $selected->title }}</h2>
                                <p class="text-sm text-gray-500">{{ $selected->orgao }}@if ($selected->numero) · {{ $selected->numero }}@endif</p>
                            </div>
                            <div class="flex flex-wrap gap-1.5">
                                @foreach (['analise' => 'Em análise', 'aplicando' => 'Aplicando', 'em_andamento' => 'Em andamento', 'concluida' => 'Concluída'] as $st => $lbl)
                                    <button wire:click="setTenderStatus({{ $selected->id }}, '{{ $st }}')" type="button"
                                        class="rounded-full px-3 py-1 text-xs font-medium transition {{ $selected->status === $st ? 'bg-teal-600 text-white' : 'bg-gray-100 text-gray-600 hover:bg-gray-200' }}">
                                        {{ $lbl }}
                                    </button>
                                @endforeach
                            </div>
                        </div>
                        @if ($selected->notes)
                            <p class="mt-3 rounded-lg bg-gray-50 p-3 text-xs text-gray-600">{{ $selected->notes }}</p>
                        @endif
                    </div>

                    {{-- Filtro de requisitos --}}
                    <div class="flex flex-wrap items-center gap-2">
                        <span class="text-xs font-medium text-gray-500">Filtrar:</span>
                        @foreach (['' => 'Todos', 'pronto' => 'Pronto', 'parcial' => 'Parcial', 'faltando' => 'Faltando', 'na_aplicacao' => 'Na aplicação'] as $val => $lbl)
                            <button wire:click="$set('filterStatus', '{{ $val }}')" type="button"
                                class="rounded-full px-3 py-1 text-xs font-medium transition {{ $filterStatus === $val ? 'bg-gray-800 text-white' : 'bg-gray-100 text-gray-600 hover:bg-gray-200' }}">
                                {{ $lbl }}
                            </button>
                        @endforeach
                    </div>

                    {{-- Requisitos por categoria --}}
                    @forelse ($requirements as $category => $reqs)
                        <div class="rounded-xl border border-gray-200 bg-white shadow-sm">
                            <p class="border-b border-gray-100 px-4 py-2.5 text-sm font-semibold text-gray-700">{{ $category }}</p>
                            <ul class="divide-y divide-gray-50">
                                @foreach ($reqs as $req)
                                    <li class="flex items-center justify-between gap-3 px-4 py-3">
                                        <div class="min-w-0">
                                            <p class="text-sm text-gray-800">{{ $req->title }}</p>
                                            @if ($req->description)
                                                <p class="mt-0.5 text-xs text-gray-500">{{ $req->description }}</p>
                                            @endif
                                        </div>
                                        <button wire:click="cycleRequirementStatus({{ $req->id }})" type="button" title="Clique para mudar o status"
                                            class="shrink-0 rounded-full px-3 py-1 text-xs font-medium transition
                                                {{ $req->status === 'pronto' ? 'bg-green-100 text-green-700 hover:bg-green-200' : ($req->status === 'na_aplicacao' ? 'bg-teal-100 text-teal-700 hover:bg-teal-200' : ($req->status === 'parcial' ? 'bg-amber-100 text-amber-700 hover:bg-amber-200' : 'bg-red-100 text-red-700 hover:bg-red-200')) }}">
                                            {{ $req->statusLabel() }}
                                        </button>
                                    </li>
                                @endforeach
                            </ul>
                        </div>
                    @empty
                        <div class="rounded-xl border border-dashed border-gray-300 bg-white p-8 text-center text-sm text-gray-400">Nenhum requisito neste filtro.</div>
                    @endforelse
                </div>
            @endif
        </section>
    </div>
</div>
