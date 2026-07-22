<?php

use App\Enums\InvitationStatus;
use App\Enums\InvitationType;
use App\Enums\Role;
use App\Enums\ScheduleStatus;
use App\Models\Hospital;
use App\Models\Invitation;
use App\Services\InvitationService;
use App\Services\ScheduleService;
use Livewire\Attributes\Layout;
use Livewire\Volt\Component;

new #[Layout('layouts.app')] class extends Component
{
    public Hospital $hospital;

    public string $month = '';

    public function mount(Hospital $hospital): void
    {
        abort_unless(auth()->user()->isGestorOf($hospital), 403);

        $this->hospital = $hospital;
        $this->month = now()->format('Y-m');
    }

    public function generateLink(InvitationService $service): void
    {
        $service->createGroupLink($this->hospital, auth()->user());

        session()->flash('status', 'Link de convite gerado! Mande para os médicos.');
    }

    public function criarEscala(ScheduleService $service): void
    {
        if (! preg_match('/^\d{4}-\d{2}$/', $this->month)) {
            return;
        }

        [$year, $month] = array_map('intval', explode('-', $this->month));

        $schedule = $this->hospital->schedules()->where('year', $year)->where('month', $month)->first()
            ?? $service->createMonthly($this->hospital, $year, $month, auth()->user());

        $this->redirect(route('gestor.escala.montar', $schedule), navigate: true);
    }

    /**
     * @return array<string, mixed>
     */
    public function with(): array
    {
        $doctors = $this->hospital->memberships()
            ->where('role', Role::Medico->value)
            ->where('active', true)
            ->with('user')
            ->get()
            ->pluck('user')
            ->filter()
            ->sortBy('name')
            ->values();

        $groupLink = Invitation::query()
            ->where('hospital_id', $this->hospital->id)
            ->where('type', InvitationType::Grupo)
            ->where('status', InvitationStatus::Pendente)
            ->latest()
            ->first();

        return [
            'doctors' => $doctors,
            'groupLink' => $groupLink,
            'linkUrl' => $groupLink !== null ? route('convite.aceitar', ['token' => $groupLink->plain_token]) : null,
            'schedules' => $this->hospital->schedules()->orderByDesc('year')->orderByDesc('month')->get(),
            'statusPublicada' => ScheduleStatus::Publicada,
        ];
    }
}; ?>

<div>
    <x-slot name="header">
        <div class="flex items-center gap-3">
            <a href="{{ route('gestor.hospitais') }}" wire:navigate class="text-gray-400 hover:text-gray-600" title="Voltar">
                <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7"/></svg>
            </a>
            <h2 class="font-semibold text-xl text-gray-800 leading-tight">{{ $hospital->name }}</h2>
        </div>
    </x-slot>

    <div class="py-8">
        <div class="max-w-5xl mx-auto sm:px-6 lg:px-8 space-y-6">
            @if (session('status'))
                <div class="rounded-lg bg-teal-50 text-teal-800 px-4 py-3 text-sm">{{ session('status') }}</div>
            @endif

            <!-- Convidar médicos -->
            <div class="bg-white shadow-sm sm:rounded-lg p-6">
                <h3 class="text-lg font-medium text-gray-900">Convidar médicos</h3>
                <p class="text-sm text-gray-500 mt-1">Gere um link e mande no WhatsApp. Quem abrir se cadastra e entra neste hospital.</p>

                @if ($linkUrl)
                    <div class="mt-4" x-data="{ copied: false }">
                        <div class="flex flex-col sm:flex-row gap-2">
                            <input type="text" readonly value="{{ $linkUrl }}" class="flex-1 rounded-md border-gray-300 bg-gray-50 text-sm text-gray-600" x-ref="link" onclick="this.select()">
                            <button type="button"
                                    @click="navigator.clipboard.writeText($refs.link.value); copied = true; setTimeout(() => copied = false, 2000)"
                                    class="inline-flex items-center justify-center rounded-md bg-gray-800 px-4 py-2 text-sm font-medium text-white hover:bg-gray-700">
                                <span x-show="!copied">Copiar</span>
                                <span x-show="copied" x-cloak>Copiado!</span>
                            </button>
                            <a href="https://wa.me/?text={{ urlencode('Cadastre-se no plantão do '.$hospital->name.': '.$linkUrl) }}" target="_blank"
                               class="inline-flex items-center justify-center gap-1 rounded-md bg-[#25D366] px-4 py-2 text-sm font-medium text-white hover:brightness-95">
                                WhatsApp
                            </a>
                        </div>
                        <button wire:click="generateLink" class="mt-2 text-xs text-gray-400 hover:text-gray-600 underline">Gerar um link novo (invalida o atual)</button>
                    </div>
                @else
                    <div class="mt-4">
                        <x-primary-button wire:click="generateLink">Gerar link de convite</x-primary-button>
                    </div>
                @endif
            </div>

            <!-- Médicos -->
            <div class="bg-white shadow-sm sm:rounded-lg p-6">
                <h3 class="text-lg font-medium text-gray-900 mb-4">Médicos <span class="text-gray-400 font-normal">({{ $doctors->count() }})</span></h3>

                @forelse ($doctors as $doctor)
                    <div class="flex items-center gap-3 py-2 {{ ! $loop->last ? 'border-b border-gray-100' : '' }}">
                        <span class="inline-flex h-9 w-9 shrink-0 items-center justify-center rounded-full bg-teal-100 text-teal-700 text-sm font-bold">
                            {{ \Illuminate\Support\Str::of($doctor->name)->explode(' ')->take(2)->map(fn ($p) => \Illuminate\Support\Str::substr($p, 0, 1))->implode('') }}
                        </span>
                        <div class="min-w-0">
                            <p class="font-medium text-gray-900 truncate">{{ $doctor->name }}</p>
                            <p class="text-xs text-gray-500 truncate">
                                {{ $doctor->crm ? 'CRM '.$doctor->crm.($doctor->crm_uf ? '/'.$doctor->crm_uf : '') : 'Sem CRM' }}
                                @if ($doctor->phone) · {{ $doctor->phone }} @endif
                            </p>
                        </div>
                    </div>
                @empty
                    <p class="text-sm text-gray-400">Nenhum médico ainda. Mande o link de convite acima.</p>
                @endforelse
            </div>

            <!-- Escalas -->
            <div class="bg-white shadow-sm sm:rounded-lg p-6">
                <div class="flex flex-col sm:flex-row sm:items-end sm:justify-between gap-3">
                    <div>
                        <h3 class="text-lg font-medium text-gray-900">Escalas</h3>
                        <p class="text-sm text-gray-500 mt-1">Monte o mês arrastando os médicos para os plantões.</p>
                    </div>
                    <div class="flex items-end gap-2">
                        <div>
                            <label for="month" class="block text-xs text-gray-500 mb-1">Mês</label>
                            <input type="month" id="month" wire:model="month" class="rounded-md border-gray-300 text-sm">
                        </div>
                        <x-primary-button wire:click="criarEscala">Criar escala</x-primary-button>
                    </div>
                </div>

                <div class="mt-4 divide-y divide-gray-100">
                    @forelse ($schedules as $schedule)
                        <a href="{{ route('gestor.escala.montar', $schedule) }}" wire:navigate class="flex items-center justify-between py-3 hover:bg-gray-50 -mx-2 px-2 rounded">
                            <span class="font-medium text-gray-800">{{ ucfirst(\Illuminate\Support\Carbon::create($schedule->year, $schedule->month, 1)->translatedFormat('F \d\e Y')) }}</span>
                            @if ($schedule->status === $statusPublicada)
                                <span class="inline-flex items-center rounded-full bg-teal-100 text-teal-700 px-2.5 py-0.5 text-xs font-medium">Publicada</span>
                            @else
                                <span class="inline-flex items-center rounded-full bg-gray-100 text-gray-600 px-2.5 py-0.5 text-xs font-medium">Rascunho</span>
                            @endif
                        </a>
                    @empty
                        <p class="text-sm text-gray-400 py-3">Nenhuma escala ainda. Escolha o mês e clique em “Criar escala”.</p>
                    @endforelse
                </div>
            </div>
        </div>
    </div>
</div>
