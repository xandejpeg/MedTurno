<?php

use App\Models\Hospital;
use App\Models\Schedule;
use App\Services\NotificationPreviewService;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Url;
use Livewire\Volt\Component;

new #[Layout('layouts.admin')] class extends Component
{
    #[Url]
    public ?int $hospitalId = null;

    #[Url]
    public ?int $scheduleId = null;

    #[Url]
    public ?int $doctorId = null;

    #[Url]
    public string $canal = 'ambos';

    public function updatedHospitalId(): void
    {
        $this->scheduleId = null;
        $this->doctorId = null;
    }

    public function updatedScheduleId(): void
    {
        $this->doctorId = null;
    }

    public function selecionarMedico(int $id): void
    {
        $this->doctorId = $this->doctorId === $id ? null : $id;
    }

    /**
     * @return array<string, mixed>
     */
    public function with(): array
    {
        $hospitais = Hospital::orderBy('name')->get();

        $escalas = $this->hospitalId === null
            ? collect()
            : Schedule::where('hospital_id', $this->hospitalId)
                ->with(['hospital', 'board'])
                ->orderByDesc('year')->orderByDesc('month')
                ->get();

        $escala = $this->scheduleId === null
            ? null
            : $escalas->firstWhere('id', $this->scheduleId)
                ?? Schedule::with(['hospital', 'board'])->find($this->scheduleId);

        $previas = [];

        if ($escala !== null) {
            $previas = app(NotificationPreviewService::class)->forSchedule($escala);

            if ($this->doctorId !== null) {
                $previas = array_values(array_filter(
                    $previas,
                    fn (array $p) => $p['doctor']->id === $this->doctorId
                ));
            }
        }

        return [
            'hospitais' => $hospitais,
            'escalas' => $escalas,
            'escala' => $escala,
            'previas' => $previas,
            'zapAtivo' => (bool) config('services.whatsapp.enabled'),
            'mailer' => config('mail.default'),
            'modoTeste' => (bool) config('services.notification_test.enabled'),
        ];
    }
}; ?>

<div class="px-4 py-6 sm:px-6 lg:px-10 lg:py-9">
    <header class="mb-6">
        <p class="text-xs font-semibold uppercase text-teal-700">Administração da plataforma</p>
        <h1 class="mt-1 text-2xl font-semibold text-gray-950">Simulador de Notificações</h1>
        <p class="mt-1 text-sm text-gray-500">
            Veja exatamente o e-mail e a mensagem de WhatsApp que cada médico receberia por um plantão real.
        </p>
    </header>

    {{-- Aviso de segurança --}}
    <div class="mb-6 flex items-start gap-3 rounded-lg border border-blue-200 bg-blue-50 px-4 py-3">
        <svg class="mt-0.5 h-5 w-5 shrink-0 text-blue-600" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
            <path stroke-linecap="round" stroke-linejoin="round" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
        </svg>
        <div class="text-sm text-blue-900">
            <p class="font-semibold">Nada é enviado nesta tela.</p>
            <p class="mt-0.5 text-blue-800">
                É apenas uma prévia: o conteúdo é montado com os mesmos dados do envio real, mas
                nenhum e-mail sai da fila e nenhuma mensagem chega ao WhatsApp.
            </p>
        </div>
    </div>

    {{-- Estado das integrações --}}
    <div class="mb-6 grid gap-3 sm:grid-cols-3">
        <div class="rounded-lg border border-gray-200 bg-white px-4 py-3">
            <p class="text-xs font-medium uppercase tracking-wide text-gray-500">WhatsApp</p>
            <p class="mt-1 text-sm font-semibold {{ $zapAtivo ? 'text-green-700' : 'text-amber-700' }}">
                {{ $zapAtivo ? 'Ativo' : 'Desativado' }}
            </p>
        </div>
        <div class="rounded-lg border border-gray-200 bg-white px-4 py-3">
            <p class="text-xs font-medium uppercase tracking-wide text-gray-500">Transporte de e-mail</p>
            <p class="mt-1 text-sm font-semibold text-gray-800">{{ $mailer }}</p>
        </div>
        <div class="rounded-lg border border-gray-200 bg-white px-4 py-3">
            <p class="text-xs font-medium uppercase tracking-wide text-gray-500">Modo de teste</p>
            <p class="mt-1 text-sm font-semibold {{ $modoTeste ? 'text-amber-700' : 'text-gray-800' }}">
                {{ $modoTeste ? 'Ligado (desvia destinatários)' : 'Desligado' }}
            </p>
        </div>
    </div>

    {{-- Seletores --}}
    <div class="mb-6 grid gap-4 rounded-lg border border-gray-200 bg-white p-4 sm:grid-cols-3">
        <div>
            <label class="block text-xs font-semibold uppercase tracking-wide text-gray-500">Hospital</label>
            <select wire:model.live="hospitalId" class="mt-1 w-full rounded-md border-gray-300 text-sm shadow-sm focus:border-teal-500 focus:ring-teal-500">
                <option value="">Selecione…</option>
                @foreach ($hospitais as $h)
                    <option value="{{ $h->id }}">{{ $h->name }}</option>
                @endforeach
            </select>
        </div>

        <div>
            <label class="block text-xs font-semibold uppercase tracking-wide text-gray-500">Escala</label>
            <select wire:model.live="scheduleId" @disabled($escalas->isEmpty())
                    class="mt-1 w-full rounded-md border-gray-300 text-sm shadow-sm focus:border-teal-500 focus:ring-teal-500 disabled:bg-gray-100">
                <option value="">{{ $escalas->isEmpty() ? 'Escolha um hospital' : 'Selecione…' }}</option>
                @foreach ($escalas as $e)
                    <option value="{{ $e->id }}">
                        {{ str_pad((string) $e->month, 2, '0', STR_PAD_LEFT) }}/{{ $e->year }} — {{ $e->status }}
                    </option>
                @endforeach
            </select>
        </div>

        <div>
            <label class="block text-xs font-semibold uppercase tracking-wide text-gray-500">Canal</label>
            <select wire:model.live="canal" class="mt-1 w-full rounded-md border-gray-300 text-sm shadow-sm focus:border-teal-500 focus:ring-teal-500">
                <option value="ambos">E-mail + WhatsApp</option>
                <option value="email">Só e-mail</option>
                <option value="whatsapp">Só WhatsApp</option>
            </select>
        </div>
    </div>

    @if ($escala === null)
        <p class="rounded-lg border border-dashed border-gray-300 bg-white px-4 py-10 text-center text-sm text-gray-500">
            Escolha um hospital e uma escala para ver as notificações que seriam disparadas.
        </p>
    @elseif (empty($previas))
        <p class="rounded-lg border border-dashed border-amber-300 bg-amber-50 px-4 py-10 text-center text-sm text-amber-800">
            Esta escala não tem nenhum plantão atribuído a médicos — ninguém seria notificado.
        </p>
    @else
        <div class="mb-4 flex flex-wrap items-center justify-between gap-2">
            <p class="text-sm text-gray-600">
                <strong>{{ count($previas) }}</strong> destinatário(s)
                · escala {{ str_pad((string) $escala->month, 2, '0', STR_PAD_LEFT) }}/{{ $escala->year }}
                · {{ $escala->hospital->name }}
            </p>
            @if ($doctorId !== null)
                <button wire:click="$set('doctorId', null)" type="button"
                        class="rounded-md bg-gray-100 px-3 py-1.5 text-xs font-medium text-gray-700 hover:bg-gray-200">
                    Ver todos os médicos
                </button>
            @endif
        </div>

        <div class="space-y-5">
            @foreach ($previas as $p)
                <article class="overflow-hidden rounded-lg border border-gray-200 bg-white shadow-sm">
                    {{-- Cabeçalho do médico --}}
                    <header class="flex flex-wrap items-start justify-between gap-3 border-b border-gray-100 bg-gray-50 px-4 py-3">
                        <div class="min-w-0">
                            <h2 class="truncate text-base font-semibold text-gray-900">{{ $p['doctor']->name }}</h2>
                            <p class="truncate text-xs text-gray-500">
                                {{ $p['doctor']->email ?: 'sem e-mail' }}
                                @if ($p['doctor']->crm)
                                    · CRM {{ $p['doctor']->crm }}/{{ $p['doctor']->crm_uf }}
                                @endif
                                · {{ $p['shiftsCount'] }} plantão(ões) nesta escala
                            </p>
                        </div>
                        <div class="flex shrink-0 gap-1.5">
                            @if ($p['email']['enviaria'])
                                <span class="rounded-full bg-green-100 px-2 py-0.5 text-xs font-medium text-green-700">e-mail ✓</span>
                            @else
                                <span class="rounded-full bg-red-100 px-2 py-0.5 text-xs font-medium text-red-700">e-mail ✗</span>
                            @endif
                            @if ($p['whatsapp']['enviaria'])
                                <span class="rounded-full bg-green-100 px-2 py-0.5 text-xs font-medium text-green-700">zap ✓</span>
                            @else
                                <span class="rounded-full bg-amber-100 px-2 py-0.5 text-xs font-medium text-amber-700">zap ✗</span>
                            @endif
                            <button wire:click="selecionarMedico({{ $p['doctor']->id }})" type="button"
                                    class="rounded-md bg-white px-2.5 py-1 text-xs font-medium text-gray-600 ring-1 ring-gray-300 hover:bg-gray-50">
                                {{ $doctorId === $p['doctor']->id ? 'Recolher' : 'Isolar' }}
                            </button>
                        </div>
                    </header>

                    <div class="grid gap-0 lg:grid-cols-2">
                        {{-- E-MAIL --}}
                        @if ($canal !== 'whatsapp')
                            <section class="border-b border-gray-100 p-4 lg:border-b-0 lg:border-r">
                                <div class="mb-3 flex items-center gap-2">
                                    <svg class="h-4 w-4 text-gray-400" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                                        <path stroke-linecap="round" stroke-linejoin="round" d="M3 8l7.89 5.26a2 2 0 002.22 0L21 8M5 19h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z" />
                                    </svg>
                                    <h3 class="text-xs font-semibold uppercase tracking-wide text-gray-500">E-mail</h3>
                                </div>

                                <dl class="mb-3 space-y-1 text-xs">
                                    <div class="flex gap-2">
                                        <dt class="w-16 shrink-0 text-gray-400">De</dt>
                                        <dd class="min-w-0 break-all text-gray-700">{{ $p['email']['remetente'] }}</dd>
                                    </div>
                                    <div class="flex gap-2">
                                        <dt class="w-16 shrink-0 text-gray-400">Para</dt>
                                        <dd class="min-w-0 break-all font-medium text-gray-900">{{ $p['email']['destinatario'] ?: '—' }}</dd>
                                    </div>
                                    <div class="flex gap-2">
                                        <dt class="w-16 shrink-0 text-gray-400">Assunto</dt>
                                        <dd class="min-w-0 text-gray-900">{{ $p['email']['assunto'] }}</dd>
                                    </div>
                                </dl>

                                @if (! $p['email']['enviaria'])
                                    <p class="mb-3 rounded-md bg-red-50 px-3 py-2 text-xs text-red-700">
                                        Não seria enviado: {{ $p['email']['motivo'] }}
                                    </p>
                                @endif

                                @if ($p['email']['erro'] !== null)
                                    <p class="mb-3 rounded-md bg-red-50 px-3 py-2 text-xs text-red-700">
                                        Erro ao montar o HTML: {{ $p['email']['erro'] }}
                                    </p>
                                @endif

                                {{-- corpo em texto --}}
                                <div class="mb-3 rounded-md border border-gray-200 bg-gray-50 px-3 py-2">
                                    <p class="mb-1 text-[10px] font-semibold uppercase tracking-wide text-gray-400">Conteúdo</p>
                                    <p class="whitespace-pre-line text-xs text-gray-700">{{ $p['email']['corpoTexto'] }}</p>
                                </div>

                                {{-- HTML real do e-mail, isolado em iframe --}}
                                @if ($p['email']['html'] !== null)
                                    <details class="group">
                                        <summary class="cursor-pointer text-xs font-medium text-teal-700 hover:text-teal-800">
                                            Ver o e-mail renderizado
                                        </summary>
                                        <div class="mt-2 overflow-hidden rounded-md border border-gray-200">
                                            <iframe title="Prévia do e-mail para {{ $p['doctor']->name }}"
                                                    sandbox=""
                                                    class="h-96 w-full bg-white"
                                                    srcdoc="{{ $p['email']['html'] }}"></iframe>
                                        </div>
                                    </details>
                                @endif
                            </section>
                        @endif

                        {{-- WHATSAPP --}}
                        @if ($canal !== 'email')
                            <section class="p-4">
                                <div class="mb-3 flex items-center gap-2">
                                    <svg class="h-4 w-4 text-gray-400" xmlns="http://www.w3.org/2000/svg" fill="currentColor" viewBox="0 0 24 24">
                                        <path d="M17.472 14.382c-.297-.149-1.758-.867-2.03-.967-.273-.099-.471-.148-.67.15-.197.297-.767.966-.94 1.164-.173.199-.347.223-.644.075-.297-.15-1.255-.463-2.39-1.475-.883-.788-1.48-1.761-1.653-2.059-.173-.297-.018-.458.13-.606.15-.148.347-.371.52-.569.174-.198.232-.339.347-.567.115-.229.058-.43-.03-.6-.086-.17-.67-1.61-.918-2.203-.242-.58-.487-.5-.669-.51-.173-.008-.371-.01-.57-.01-.198 0-.52.074-.792.372-.272.297-1.04 1.016-1.04 2.479 0 1.462 1.065 2.875 1.213 3.074.149.198 2.096 3.2 5.077 4.487.709.306 1.262.489 1.694.625.712.227 1.36.195 1.871.118.571-.085 1.758-.719 2.006-1.413.248-.694.248-1.289.173-1.413-.074-.124-.272-.198-.57-.347z" />
                                    </svg>
                                    <h3 class="text-xs font-semibold uppercase tracking-wide text-gray-500">WhatsApp</h3>
                                </div>

                                <dl class="mb-3 space-y-1 text-xs">
                                    <div class="flex gap-2">
                                        <dt class="w-24 shrink-0 text-gray-400">Template</dt>
                                        <dd class="min-w-0 break-all font-mono text-gray-700">{{ $p['whatsapp']['template'] }}</dd>
                                    </div>
                                    <div class="flex gap-2">
                                        <dt class="w-24 shrink-0 text-gray-400">Cadastrado</dt>
                                        <dd class="min-w-0 text-gray-700">{{ $p['whatsapp']['telefoneBruto'] ?: '—' }}</dd>
                                    </div>
                                    <div class="flex gap-2">
                                        <dt class="w-24 shrink-0 text-gray-400">Normalizado</dt>
                                        <dd class="min-w-0 font-mono {{ $p['whatsapp']['telefoneNormalizado'] ? 'text-gray-900' : 'text-red-600' }}">
                                            {{ $p['whatsapp']['telefoneNormalizado'] ?: 'inválido' }}
                                        </dd>
                                    </div>
                                </dl>

                                @if (! $p['whatsapp']['enviaria'])
                                    <p class="mb-3 rounded-md bg-amber-50 px-3 py-2 text-xs text-amber-800">
                                        Não seria enviado: {{ $p['whatsapp']['motivo'] }}
                                    </p>
                                @endif

                                {{-- bolha estilo WhatsApp --}}
                                <div class="rounded-lg bg-[#0b141a] p-3">
                                    <div class="ml-auto max-w-[95%] rounded-lg rounded-tr-none bg-[#005c4b] px-3 py-2">
                                        <p class="whitespace-pre-line text-xs leading-relaxed text-[#e9edef]">{!!
                                            preg_replace('/\*(.+?)\*/', '<strong>$1</strong>', e($p['whatsapp']['corpo']))
                                        !!}</p>
                                        <p class="mt-1 text-right text-[10px] text-[#8696a0]">
                                            {{ now()->format('H:i') }} ✓✓
                                        </p>
                                    </div>
                                </div>

                                {{-- parâmetros do template --}}
                                <details class="mt-3">
                                    <summary class="cursor-pointer text-xs font-medium text-teal-700 hover:text-teal-800">
                                        Ver parâmetros enviados à Meta
                                    </summary>
                                    <div class="mt-2 space-y-1 rounded-md border border-gray-200 bg-gray-50 px-3 py-2">
                                        @foreach ($p['whatsapp']['parametros'] as $chave => $valor)
                                            <div class="flex gap-2 text-xs">
                                                <span class="w-10 shrink-0 font-mono text-gray-400">{{ $chave }}</span>
                                                <span class="min-w-0 break-words text-gray-700">{{ $valor }}</span>
                                            </div>
                                        @endforeach
                                        <div class="flex gap-2 border-t border-gray-200 pt-1 text-xs">
                                            <span class="w-10 shrink-0 text-gray-400">lang</span>
                                            <span class="text-gray-700">{{ $p['whatsapp']['idioma'] }}</span>
                                        </div>
                                    </div>
                                </details>
                            </section>
                        @endif
                    </div>

                    {{-- Notificação interna + plantões --}}
                    <footer class="border-t border-gray-100 bg-gray-50 px-4 py-3">
                        <div class="grid gap-3 sm:grid-cols-2">
                            <div>
                                <p class="mb-1 text-[10px] font-semibold uppercase tracking-wide text-gray-400">
                                    Notificação no app
                                </p>
                                <p class="text-xs font-medium text-gray-800">{{ $p['interna']['title'] }}</p>
                                <p class="text-xs text-gray-600">{{ $p['interna']['body'] }}</p>
                            </div>
                            <div>
                                <p class="mb-1 text-[10px] font-semibold uppercase tracking-wide text-gray-400">
                                    Plantões deste médico
                                </p>
                                <div class="flex flex-wrap gap-1">
                                    @foreach ($p['shifts']->take(12) as $s)
                                        <span class="rounded bg-white px-1.5 py-0.5 text-[11px] text-gray-700 ring-1 ring-gray-200">
                                            {{ $s->date?->format('d/m') }}{{ $s->period ? ' '.$s->period : '' }}
                                        </span>
                                    @endforeach
                                    @if ($p['shifts']->count() > 12)
                                        <span class="px-1 text-[11px] text-gray-400">+{{ $p['shifts']->count() - 12 }}</span>
                                    @endif
                                </div>
                            </div>
                        </div>
                    </footer>
                </article>
            @endforeach
        </div>
    @endif
</div>
