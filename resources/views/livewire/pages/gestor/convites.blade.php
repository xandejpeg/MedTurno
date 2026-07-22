<?php

use App\Enums\InvitationStatus;
use App\Enums\InvitationType;
use App\Enums\Role;
use App\Models\Hospital;
use App\Models\HospitalMembership;
use App\Models\Invitation;
use App\Models\ShiftBoard;
use App\Services\InvitationService;
use Livewire\Attributes\Layout;
use Livewire\Volt\Component;

new #[Layout('layouts.app')] class extends Component
{
    public string $hospitalId = '';

    public string $boardId = '';

    public function mount(): void
    {
        $hospital = currentHospital();
        abort_unless($hospital !== null, 403);
        $this->hospitalId = (string) $hospital->id;
    }

    public function updatedHospitalId(): void
    {
        $this->boardId = '';

        if ($this->hospital() !== null) {
            session(['current_hospital_id' => (int) $this->hospitalId]);
        }
    }

    /**
     * Hospital selecionado na tela (entre os que o gestor administra).
     */
    private function hospital(): ?Hospital
    {
        return auth()->user()->managedHospitals()->whereKey((int) $this->hospitalId)->first()
            ?? currentHospital();
    }

    /**
     * @return array<string, mixed>
     */
    public function with(): array
    {
        $hospital = $this->hospital();
        abort_unless($hospital !== null, 404);

        $hospitalIds = auth()->user()->managedHospitals()->pluck('hospitals.id');

        $medicos = HospitalMembership::query()
            ->whereIn('hospital_id', $hospitalIds)
            ->where('role', Role::Medico->value)
            ->with(['user', 'hospital', 'invitation.shiftBoard'])
            ->get()
            ->sortBy(fn ($m) => $m->user->name)
            ->values();

        return [
            'hospital' => $hospital,
            'hospitais' => auth()->user()->managedHospitals()->orderBy('name')->get(),
            'boards' => $hospital->shiftBoards()->where('active', true)->orderBy('name')->get(),
            'boardsByHospital' => ShiftBoard::query()
                ->whereIn('hospital_id', $hospitalIds)
                ->where('active', true)
                ->orderBy('name')
                ->get()
                ->groupBy('hospital_id'),
            'groupLinks' => Invitation::query()
                ->where('hospital_id', $hospital->id)
                ->where('type', InvitationType::Grupo)
                ->where('status', InvitationStatus::Pendente)
                ->with('shiftBoard')
                ->withCount('memberships')
                ->latest()
                ->get(),
            'medicos' => $medicos,
            'completos' => $medicos->filter(fn ($m) => $m->user->cadastroCompleto())->count(),
        ];
    }

    public function generate(InvitationService $service): void
    {
        $hospital = $this->hospital();
        abort_unless($hospital !== null, 404);
        $this->authorize('update', $hospital);

        $board = $this->boardId !== ''
            ? $hospital->shiftBoards()->where('active', true)->whereKey((int) $this->boardId)->first()
            : null;

        $service->createGroupLink($hospital, auth()->user(), $board);

        $this->boardId = '';
        session()->flash('status', 'Link de grupo gerado! Copie e mande no WhatsApp.');
    }

    public function revoke(int $invitationId): void
    {
        $hospital = $this->hospital();
        abort_unless($hospital !== null, 404);
        $this->authorize('update', $hospital);

        Invitation::query()
            ->where('hospital_id', $hospital->id)
            ->where('type', InvitationType::Grupo)
            ->whereKey($invitationId)
            ->update(['status' => InvitationStatus::Cancelado]);
    }

    /**
     * Coloca o médico num quadro do hospital dele — assim ele passa a poder
     * ser escalado naquele quadro.
     */
    public function addToBoard(int $userId, int $boardId): void
    {
        $hospitalIds = auth()->user()->managedHospitals()->pluck('hospitals.id');

        $board = ShiftBoard::query()
            ->whereIn('hospital_id', $hospitalIds)
            ->whereKey($boardId)
            ->firstOrFail();

        $this->authorize('update', $board->hospital);

        abort_unless(
            HospitalMembership::query()
                ->where('user_id', $userId)
                ->where('hospital_id', $board->hospital_id)
                ->where('role', Role::Medico->value)
                ->exists(),
            422,
        );

        $board->doctors()->syncWithoutDetaching([$userId]);

        session()->flash('status', 'Médico adicionado ao quadro “'.$board->name.'” — já pode escalá-lo.');
    }
}; ?>

<div>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 leading-tight">Convites</h2>
    </x-slot>

    <div class="py-12">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8 space-y-6">

            @if (session('status'))
                <div class="bg-green-50 border border-green-200 text-green-800 rounded-lg p-4 text-sm">{{ session('status') }}</div>
            @endif

            {{-- Gerar link de grupo --}}
            <div class="bg-white shadow-sm sm:rounded-lg p-6">
                <h3 class="text-lg font-medium text-gray-900 mb-1">Link de grupo</h3>
                <p class="text-sm text-gray-500 mb-4">
                    Um link único e reutilizável pra jogar no grupo do WhatsApp. Quem abrir cria a conta e já entra
                    como médico do hospital escolhido.
                </p>

                <div class="flex flex-wrap items-end gap-3">
                    <div>
                        <x-input-label for="hospitalId" value="Hospital *" />
                        <select wire:model.live="hospitalId" id="hospitalId" class="block mt-1 border-gray-300 focus:border-teal-500 focus:ring-teal-500 rounded-md shadow-sm">
                            @foreach ($hospitais as $h)
                                <option value="{{ $h->id }}">{{ $h->name }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div>
                        <x-input-label for="boardId" value="Entrar direto num quadro (opcional)" />
                        <select wire:model="boardId" id="boardId" class="block mt-1 border-gray-300 focus:border-teal-500 focus:ring-teal-500 rounded-md shadow-sm">
                            <option value="">Só no hospital</option>
                            @foreach ($boards as $board)
                                <option value="{{ $board->id }}">{{ $board->name }}</option>
                            @endforeach
                        </select>
                    </div>
                    <x-primary-button wire:click="generate" type="button">Gerar link</x-primary-button>
                </div>

                @if ($groupLinks->isNotEmpty())
                    <div class="mt-6 space-y-3">
                        @foreach ($groupLinks as $link)
                            @php
                                $url = route('convite.aceitar', ['token' => $link->plain_token]);
                                $msg = 'Você foi convidado(a) para a equipe médica do '.$hospital->name.' no DoctorTurn. Crie sua conta neste link: '.$url;
                            @endphp
                            <div x-data="{ copied: false }" class="border border-teal-200 bg-teal-50 rounded-lg p-4 space-y-2">
                                <div class="flex items-center justify-between gap-3">
                                    <p class="text-sm font-medium text-teal-900">
                                        {{ $link->shiftBoard?->name ?? 'Hospital todo' }}
                                        <span class="ml-2 text-xs font-normal text-teal-700">{{ $link->memberships_count }} {{ $link->memberships_count === 1 ? 'pessoa entrou' : 'pessoas entraram' }}</span>
                                    </p>
                                    <button wire:click="revoke({{ $link->id }})" wire:confirm="Revogar este link? Quem já tem o link não vai mais conseguir entrar." type="button" class="text-xs text-red-600 hover:text-red-800">Revogar</button>
                                </div>
                                <div class="flex flex-wrap items-center gap-2">
                                    <input type="text" readonly value="{{ $url }}" x-ref="l{{ $link->id }}" class="flex-1 min-w-0 text-sm border-teal-300 rounded-md bg-white text-gray-700" />
                                    <button type="button" x-on:click="navigator.clipboard.writeText($refs.l{{ $link->id }}.value); copied = true; setTimeout(() => copied = false, 2000)" class="inline-flex items-center px-3 py-2 bg-teal-600 text-white text-sm rounded-md hover:bg-teal-700">
                                        <span x-show="!copied">Copiar</span>
                                        <span x-show="copied" style="display:none">Copiado!</span>
                                    </button>
                                    <a href="https://wa.me/?text={{ rawurlencode($msg) }}"
                                       x-data="{ shareMsg: @js($msg) }"
                                       x-on:click="if (navigator.share) { $event.preventDefault(); navigator.share({ text: shareMsg }).catch(() => {}); }"
                                       target="_blank" rel="noopener" class="inline-flex items-center px-3 py-2 bg-green-600 text-white text-sm rounded-md hover:bg-green-700">Compartilhar</a>
                                </div>
                            </div>
                        @endforeach
                    </div>
                @endif
            </div>

            {{-- Quem entrou + completude --}}
            <div class="bg-white shadow-sm sm:rounded-lg overflow-hidden">
                <div class="px-6 py-4 border-b border-gray-100 flex items-center justify-between">
                    <h3 class="font-medium text-gray-900">Equipe médica — todos os seus hospitais ({{ $medicos->count() }})</h3>
                    <span class="text-sm text-gray-500">{{ $completos }} com cadastro completo · {{ $medicos->count() - $completos }} incompleto(s)</span>
                </div>

                @forelse ($medicos as $membership)
                    @php $u = $membership->user; @endphp
                    <div class="flex items-center gap-4 px-6 py-4 {{ ! $loop->last ? 'border-b border-gray-100' : '' }}">
                        @if ($u->photo_path)
                            <img src="{{ asset('storage/'.$u->photo_path) }}" alt="{{ $u->name }}" class="h-11 w-11 rounded-full object-cover border border-gray-200 shrink-0" />
                        @else
                            <span class="h-11 w-11 rounded-full bg-gray-100 flex items-center justify-center text-gray-400 text-sm shrink-0">{{ mb_substr($u->name, 0, 1) }}</span>
                        @endif

                        <div class="min-w-0 flex-1">
                            <p class="font-medium text-gray-900 {{ ! $membership->active ? 'line-through text-gray-400' : '' }}">
                                {{ $u->name }}
                                <span class="ml-1 inline-block align-middle text-[11px] font-normal text-teal-700 bg-teal-50 rounded px-1.5 py-0.5">{{ $membership->hospital->name }}</span>
                            </p>
                            <p class="text-sm text-gray-500 truncate">
                                {{ $u->email }}
                                @if ($u->crm) · CRM {{ $u->crm }}{{ $u->crm_uf ? '/'.$u->crm_uf : '' }} @endif
                                @if ($u->phone) · {{ $u->phone }} @endif
                            </p>
                            <p class="text-xs text-gray-400 mt-0.5">
                                @if ($membership->invitation?->isGroup())
                                    entrou pelo link de grupo{{ $membership->invitation->shiftBoard ? ' ('.$membership->invitation->shiftBoard->name.')' : '' }}
                                @elseif ($membership->invitation)
                                    convite individual
                                @else
                                    adicionado manualmente
                                @endif
                            </p>
                        </div>

                        <div class="shrink-0 flex items-center gap-2">
                            @if ($u->cadastroCompleto())
                                <span class="text-xs px-2 py-1 rounded-full bg-green-100 text-green-800">Completo</span>
                            @else
                                <span class="text-xs px-2 py-1 rounded-full bg-amber-100 text-amber-800" title="Faltam campos obrigatórios (CPF, CRM, celular…)">Incompleto</span>
                            @endif

                            @php $hb = $boardsByHospital->get($membership->hospital_id, collect()); @endphp
                            @if ($hb->isNotEmpty())
                                <select
                                    x-on:change="if ($event.target.value) { $wire.addToBoard({{ $u->id }}, $event.target.value); $event.target.value = ''; }"
                                    class="text-xs border-gray-300 rounded-md shadow-sm focus:border-teal-500 focus:ring-teal-500"
                                    title="Adicionar a um quadro">
                                    <option value="">+ Quadro</option>
                                    @foreach ($hb as $b)
                                        <option value="{{ $b->id }}">{{ $b->name }}</option>
                                    @endforeach
                                </select>
                            @endif
                        </div>
                    </div>
                @empty
                    <p class="p-6 text-gray-500">Ninguém entrou ainda. Gere um link de grupo acima e mande no WhatsApp.</p>
                @endforelse
            </div>
        </div>
    </div>
</div>
