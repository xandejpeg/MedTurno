<?php

use App\Enums\Role;
use App\Enums\ScheduleStatus;
use App\Enums\ShiftStatus;
use App\Models\Shift;
use App\Models\User;
use App\Services\NotificationService;
use App\Services\ShiftService;
use App\Services\TransferService;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Locked;
use Livewire\Volt\Component;

new #[Layout('layouts.app')] class extends Component
{
    #[Locked]
    public int $shiftId;

    public bool $showTransfer = false;

    public string $colleagueId = '';

    public string $reason = '';

    public function mount(Shift $shift): void
    {
        abort_unless($shift->user_id === auth()->id(), 403);
        abort_unless($shift->schedule->status === ScheduleStatus::Publicada, 404);

        $this->shiftId = $shift->id;
    }

    public function shift(): Shift
    {
        $shift = Shift::with(['hospital', 'schedule.board', 'template'])->findOrFail($this->shiftId);
        abort_unless($shift->user_id === auth()->id(), 403);

        return $shift;
    }

    /**
     * @return array<string, mixed>
     */
    public function with(): array
    {
        $shift = $this->shift();

        $colleagues = User::query()
            ->whereKeyNot(auth()->id())
            ->whereHas('hospitalMemberships', fn ($q) => $q
                ->where('hospital_id', $shift->hospital_id)
                ->where('role', Role::Medico)
                ->where('active', true))
            ->orderBy('name')
            ->get();

        $checkins = $shift->checkins()->where('user_id', auth()->id())->orderBy('checked_at')->get();

        return [
            'shift' => $shift,
            'colleagues' => $colleagues,
            'activeTransfer' => $shift->activeTransfer()?->load('toDoctor'),
            'checkins' => $checkins,
            'checkedIn' => $checkins->where('type', 'in')->isNotEmpty(),
            'checkedOut' => $checkins->where('type', 'out')->isNotEmpty(),
        ];
    }

    public function checkin(string $type, ?float $lat = null, ?float $lng = null): void
    {
        try {
            app(\App\Services\CheckinService::class)->record($this->shift(), auth()->user(), $type, $lat !== null ? 'gps' : 'manual', $lat, $lng);
            session()->flash('status', $type === 'in' ? 'Check-in registrado!' : 'Check-out registrado!');
        } catch (\InvalidArgumentException $e) {
            $this->addError('confirm', $e->getMessage());
        }
    }

    public function requestTransfer(TransferService $service): void
    {
        $this->validate(
            ['colleagueId' => ['required'], 'reason' => ['nullable', 'string', 'max:500']],
            [], ['colleagueId' => 'colega'],
        );

        try {
            $colleague = User::findOrFail((int) $this->colleagueId);
            $service->requestDirect($this->shift(), auth()->user(), $colleague, $this->reason !== '' ? $this->reason : null);
            $this->reset(['showTransfer', 'colleagueId', 'reason']);
            session()->flash('status', 'Proposta enviada! Aguardando o colega aceitar.');
        } catch (\InvalidArgumentException $e) {
            $this->addError('colleagueId', $e->getMessage());
        }
    }

    public function announce(TransferService $service): void
    {
        try {
            $service->announce($this->shift(), auth()->user());
            session()->flash('status', 'Plantão anunciado no mural! Os colegas do quadro foram avisados.');
        } catch (\InvalidArgumentException $e) {
            $this->addError('confirm', $e->getMessage());
        }
    }

    public function cancelAnnouncement(TransferService $service): void
    {
        $transfer = $this->shift()->activeTransfer();

        try {
            if ($transfer === null) {
                throw new \InvalidArgumentException('Nenhum anúncio ativo.');
            }

            $service->cancelAnnouncement($transfer, auth()->user());
            session()->flash('status', 'Anúncio cancelado — o plantão continua com você.');
        } catch (\InvalidArgumentException $e) {
            $this->addError('confirm', $e->getMessage());
        }
    }

    public function confirm(ShiftService $service, NotificationService $notifications): void
    {
        $shift = $this->shift();

        try {
            $service->confirm($shift, auth()->user(), $notifications);
            session()->flash('status', 'Plantão confirmado!');
        } catch (\InvalidArgumentException $e) {
            $this->addError('confirm', $e->getMessage());
        }
    }
}; ?>

<div>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 leading-tight">Detalhe do plantão</h2>
    </x-slot>

    <div class="py-12 pb-24 sm:pb-12">
        <div class="max-w-2xl mx-auto sm:px-6 lg:px-8 space-y-6">

            @if (session('status'))
                <div class="bg-green-50 border border-green-200 text-green-800 rounded-lg p-4 text-sm">
                    {{ session('status') }}
                </div>
            @endif

            <div class="bg-white shadow-sm sm:rounded-lg p-6 space-y-4">
                <div class="flex items-center justify-between">
                    <h3 class="text-lg font-semibold text-gray-900">
                        {{ $shift->date->format('d/m/Y') }} · {{ $shift->starts_at->format('H:i') }}–{{ $shift->ends_at->format('H:i') }}
                    </h3>
                    <span class="text-xs px-2 py-1 rounded-full
                        @if ($shift->status === ShiftStatus::Confirmado) bg-green-100 text-green-800
                        @elseif ($shift->status === ShiftStatus::Pendente) bg-amber-100 text-amber-800
                        @elseif ($shift->status === ShiftStatus::EmTroca) bg-blue-100 text-blue-800
                        @else bg-gray-100 text-gray-700 @endif">
                        {{ $shift->status->label() }}
                    </span>
                </div>

                <dl class="grid grid-cols-1 sm:grid-cols-2 gap-4 text-sm">
                    <div>
                        <dt class="text-gray-500">Hospital</dt>
                        <dd class="font-medium text-gray-900">{{ $shift->hospital->name }}</dd>
                    </div>
                    <div>
                        <dt class="text-gray-500">Quadro</dt>
                        <dd class="font-medium text-gray-900">{{ $shift->schedule->board?->name ?? $shift->hospital->name }}</dd>
                    </div>
                    <div>
                        <dt class="text-gray-500">Valor</dt>
                        <dd class="font-medium text-gray-900">
                            @if ($shift->amount !== null)
                                R$ {{ number_format((float) $shift->amount, 2, ',', '.') }}
                            @else
                                —
                            @endif
                        </dd>
                    </div>
                    @if ($shift->confirmed_at)
                        <div>
                            <dt class="text-gray-500">Confirmado em</dt>
                            <dd class="font-medium text-gray-900">{{ $shift->confirmed_at->format('d/m/Y H:i') }}</dd>
                        </div>
                    @endif
                    @if ($shift->note)
                        <div class="sm:col-span-2">
                            <dt class="text-gray-500">Observação</dt>
                            <dd class="font-medium text-gray-900">{{ $shift->note }}</dd>
                        </div>
                    @endif
                </dl>

                @error('confirm')
                    <p class="text-sm text-red-600">{{ $message }}</p>
                @enderror

                @if ($shift->status === ShiftStatus::EmTroca && $activeTransfer !== null)
                    <div class="bg-blue-50 border border-blue-200 text-blue-800 rounded-lg p-4 text-sm">
                        Troca em andamento com <strong>{{ $activeTransfer->toDoctor->name }}</strong> — {{ $activeTransfer->status->label() }}.
                        <a href="{{ route('medico.trocas') }}" wire:navigate class="underline">Ver minhas trocas</a>
                    </div>
                @endif

                @if ($shift->status === ShiftStatus::Disponivel && $activeTransfer !== null)
                    <div class="bg-red-50 border border-red-200 text-red-800 rounded-lg p-4 text-sm flex flex-wrap items-center justify-between gap-3">
                        <span>Anunciado no mural — {{ $shift->interests()->pending()->count() }} interessado(s).</span>
                        <x-secondary-button wire:click="cancelAnnouncement" wire:confirm="Cancelar o anúncio? O plantão volta pra você." type="button">Cancelar anúncio</x-secondary-button>
                    </div>
                @endif

                <div class="pt-2 flex flex-wrap gap-3">
                    @if ($shift->status === ShiftStatus::Pendente)
                        <x-primary-button wire:click="confirm" type="button">Confirmar plantão</x-primary-button>
                    @endif

                    @if (in_array($shift->status, [ShiftStatus::Pendente, ShiftStatus::Confirmado], true))
                        <x-secondary-button wire:click="$set('showTransfer', true)" type="button">Passar para colega</x-secondary-button>
                        <x-secondary-button wire:click="announce" wire:confirm="Anunciar este plantão no mural? Os colegas do quadro serão avisados." type="button">Anunciar no mural</x-secondary-button>
                    @endif
                </div>
            </div>

            {{-- Check-in / Check-out --}}
            <div class="bg-white shadow-sm sm:rounded-lg p-6" x-data="{ lat: null, lng: null, locating: false, locate() { this.locating = true; navigator.geolocation.getCurrentPosition((p) => { this.lat = p.coords.latitude; this.lng = p.coords.longitude; this.locating = false; }, () => { this.locating = false; }); } }">
                <h3 class="text-sm font-semibold text-gray-800 mb-3">Check-in / Check-out</h3>

                @if ($checkins->isNotEmpty())
                    <ul class="mb-4 space-y-1 text-sm">
                        @foreach ($checkins as $c)
                            <li class="flex items-center gap-2 text-gray-700">
                                <span class="inline-flex rounded-full px-2 py-0.5 text-xs font-medium {{ $c->type === 'in' ? 'bg-green-100 text-green-700' : 'bg-blue-100 text-blue-700' }}">{{ $c->typeLabel() }}</span>
                                {{ $c->checked_at->format('d/m/Y H:i') }}
                                <span class="text-xs text-gray-400">({{ $c->methodLabel() }})</span>
                            </li>
                        @endforeach
                    </ul>
                @endif

                <div class="flex flex-wrap gap-2">
                    @unless ($checkedIn)
                        <x-primary-button wire:click="checkin('in')" type="button">Check-in</x-primary-button>
                        <x-secondary-button type="button" @click="locate(); $watch('lat', v => v && $wire.checkin('in', lat, lng))" x-bind:disabled="locating">
                            <span x-text="locating ? 'Localizando...' : 'Check-in por GPS'"></span>
                        </x-secondary-button>
                    @endunless

                    @if ($checkedIn && ! $checkedOut)
                        <x-primary-button wire:click="checkin('out')" type="button">Check-out</x-primary-button>
                        <x-secondary-button type="button" @click="locate(); $watch('lat', v => v && $wire.checkin('out', lat, lng))" x-bind:disabled="locating">
                            <span x-text="locating ? 'Localizando...' : 'Check-out por GPS'"></span>
                        </x-secondary-button>
                    @endif
                </div>

                {{-- Check-in por QR Code oculto: CheckinService::validateQrPayload() ainda não tem tela de leitura. --}}
            </div>

            @if ($showTransfer)
                <div class="fixed inset-0 bg-gray-900/50 z-50 flex items-center justify-center p-4" wire:click.self="$set('showTransfer', false)">
                    <div class="bg-white rounded-lg shadow-xl max-w-md w-full p-6 space-y-4">
                        <h3 class="text-lg font-semibold text-gray-900">Passar para colega</h3>
                        <p class="text-sm text-gray-500">O colega precisa aceitar e o gestor aprovar.</p>

                        <div>
                            <x-input-label for="colleagueId" value="Colega" />
                            <select id="colleagueId" wire:model="colleagueId" class="mt-1 block w-full border-gray-300 rounded-md shadow-sm">
                                <option value="">Selecione...</option>
                                @foreach ($colleagues as $colleague)
                                    <option value="{{ $colleague->id }}">{{ $colleague->name }}@if ($colleague->specialty) — {{ $colleague->specialty }}@endif</option>
                                @endforeach
                            </select>
                            <x-input-error :messages="$errors->get('colleagueId')" class="mt-2" />
                        </div>

                        <div>
                            <x-input-label for="reason" value="Motivo (opcional)" />
                            <textarea id="reason" wire:model="reason" rows="2" class="mt-1 block w-full border-gray-300 rounded-md shadow-sm"></textarea>
                            <x-input-error :messages="$errors->get('reason')" class="mt-2" />
                        </div>

                        <div class="flex justify-end gap-3">
                            <x-secondary-button wire:click="$set('showTransfer', false)" type="button">Cancelar</x-secondary-button>
                            <x-primary-button wire:click="requestTransfer" type="button">Enviar proposta</x-primary-button>
                        </div>
                    </div>
                </div>
            @endif

            <a href="{{ route('medico.escala') }}" wire:navigate class="inline-block text-sm text-teal-600 hover:underline">← Voltar pra escala</a>
        </div>
    </div>
</div>
