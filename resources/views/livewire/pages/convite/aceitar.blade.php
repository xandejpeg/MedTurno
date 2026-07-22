<?php

use App\Models\User;
use App\Services\InvitationService;
use Illuminate\Support\Facades\Auth;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Locked;
use Livewire\Volt\Component;
use Livewire\WithFileUploads;

new #[Layout('layouts.guest')] class extends Component
{
    use WithFileUploads;

    #[Locked]
    public string $token = '';

    public bool $valid = false;

    public bool $isGroup = false;

    public bool $existingUser = false;

    public string $hospitalName = '';

    public string $boardName = '';

    public string $inviteeName = '';

    // Convite individual
    public string $password = '';

    public string $password_confirmation = '';

    // Link de grupo (cadastro completo)
    public string $name = '';

    public string $email = '';

    public string $cpf = '';

    public string $phone = '';

    public string $crm = '';

    public string $crm_uf = '';

    public $photo = null;

    public function mount(InvitationService $service): void
    {
        $this->token = (string) request()->query('token', '');

        $invitation = $service->findUsableByToken($this->token);

        if ($invitation !== null) {
            $this->valid = true;
            $this->isGroup = $invitation->isGroup();
            $this->hospitalName = $invitation->hospital->name;
            $this->boardName = $invitation->shiftBoard?->name ?? '';

            if (! $this->isGroup) {
                $this->inviteeName = (string) $invitation->name;
                $this->existingUser = User::where('email', $invitation->email)->exists();
            }
        }
    }

    public function accept(InvitationService $service): void
    {
        if ($this->existingUser) {
            $user = $service->accept($this->token);
        } else {
            $this->validate(['password' => ['required', 'string', 'min:8', 'confirmed']]);
            $user = $service->accept($this->token, $this->password);
        }

        $this->loginAndGo($user);
    }

    public function register(InvitationService $service): void
    {
        $validated = $this->validate([
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'email', 'max:255'],
            'cpf' => ['required', 'string', 'min:11', 'max:14'],
            'phone' => ['required', 'regex:/^\([1-9][0-9]\) 9[0-9]{4}-[0-9]{4}$/'],
            'crm' => ['required', 'string', 'max:30'],
            'crm_uf' => ['nullable', 'string', 'max:2'],
            'password' => ['required', 'string', 'min:8', 'confirmed'],
            'photo' => ['nullable', 'image', 'max:15360'],
        ], [
            'phone.required' => 'Informe seu celular com DDD.',
            'phone.regex' => 'Digite um celular válido com DDD, no formato (27) 99999-9999.',
        ], [
            'name' => 'nome completo',
            'cpf' => 'CPF',
            'phone' => 'celular',
        ]);

        $photoPath = $this->photo !== null ? $this->photo->store('fotos', 'public') : null;

        try {
            $user = $service->acceptGroup($this->token, [
                'name' => $validated['name'],
                'email' => $validated['email'],
                'cpf' => preg_replace('/\D/', '', $validated['cpf']),
                'phone' => $validated['phone'],
                'crm' => $validated['crm'],
                'crm_uf' => $validated['crm_uf'] !== '' ? strtoupper($validated['crm_uf']) : null,
                'photo_path' => $photoPath,
                'password' => $validated['password'],
            ]);
        } catch (\InvalidArgumentException $e) {
            $this->addError('email', $e->getMessage());

            return;
        }

        $this->loginAndGo($user);
    }

    private function loginAndGo(User $user): void
    {
        Auth::login($user);
        session()->regenerate();

        $this->redirect(route('dashboard'), navigate: true);
    }
}; ?>

<div>
    @if (! $valid)
        <div class="text-center space-y-4">
            <h1 class="text-xl font-semibold text-gray-900">Convite inválido</h1>
            <p class="text-gray-600">Este convite não existe, já foi usado ou expirou. Peça ao gestor para reenviar.</p>
            <a href="{{ route('login') }}" class="underline text-sm text-gray-600 hover:text-gray-900" wire:navigate>Ir para o login</a>
        </div>
    @elseif ($isGroup)
        <div class="space-y-4">
            <div class="text-center">
                <h1 class="text-xl font-semibold text-gray-900">Bem-vindo(a) ao {{ $hospitalName }}</h1>
                <p class="text-gray-600 mt-1">
                    Preencha seu cadastro para entrar na equipe médica
                    @if ($boardName) do quadro <strong>{{ $boardName }}</strong> @endif.
                </p>
            </div>

            <form wire:submit="register" class="space-y-4">
                <div>
                    <x-input-label for="name" value="Nome completo *" />
                    <x-text-input wire:model="name" id="name" class="block mt-1 w-full" type="text" required autofocus autocomplete="name" />
                    <x-input-error :messages="$errors->get('name')" class="mt-2" />
                </div>

                <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                    <div>
                        <x-input-label for="cpf" value="CPF *" />
                        <x-text-input wire:model="cpf" id="cpf" class="block mt-1 w-full" type="text" required placeholder="000.000.000-00" />
                        <x-input-error :messages="$errors->get('cpf')" class="mt-2" />
                    </div>
                    <div>
                        <x-input-label for="phone" value="Celular *" />
                        <div wire:ignore class="mt-1">
                            <input type="tel" id="phone" required autocomplete="tel" inputmode="numeric"
                                   maxlength="15" placeholder="(27) 99999-9999" aria-describedby="phone-error"
                                   class="block w-full border-gray-300 focus:border-teal-500 focus:ring-teal-500 rounded-md shadow-sm" />
                        </div>
                        <x-input-error id="phone-error" :messages="$errors->get('phone')" class="mt-2" />
                    </div>
                </div>

                <div>
                    <x-input-label for="email" value="E-mail *" />
                    <x-text-input wire:model="email" id="email" class="block mt-1 w-full" type="email" required autocomplete="email" />
                    <x-input-error :messages="$errors->get('email')" class="mt-2" />
                </div>

                <div class="grid grid-cols-3 gap-4">
                    <div class="col-span-2">
                        <x-input-label for="crm" value="CRM *" />
                        <x-text-input wire:model="crm" id="crm" class="block mt-1 w-full" type="text" required />
                        <x-input-error :messages="$errors->get('crm')" class="mt-2" />
                    </div>
                    <div>
                        <x-input-label for="crm_uf" value="UF" />
                        <x-text-input wire:model="crm_uf" id="crm_uf" class="block mt-1 w-full uppercase" type="text" maxlength="2" placeholder="PE" />
                        <x-input-error :messages="$errors->get('crm_uf')" class="mt-2" />
                    </div>
                </div>

                <div>
                    <x-input-label value="Foto (opcional)" />
                    <div class="mt-1 flex items-center gap-4">
                        @if ($photo)
                            <img src="{{ $photo->temporaryUrl() }}" alt="prévia" class="h-16 w-16 rounded-full object-cover border border-gray-200" />
                        @else
                            <span class="h-16 w-16 rounded-full bg-gray-100 flex items-center justify-center text-gray-400 text-xs">sem foto</span>
                        @endif
                        <label class="cursor-pointer inline-flex items-center px-3 py-2 bg-white border border-gray-300 rounded-md text-sm text-gray-700 hover:bg-gray-50">
                            <span>Tirar foto / enviar</span>
                            <input type="file" wire:model="photo" accept="image/*" class="hidden" />
                        </label>
                    </div>
                    <div wire:loading wire:target="photo" class="text-xs text-gray-500 mt-1">Enviando foto…</div>
                    <x-input-error :messages="$errors->get('photo')" class="mt-2" />
                </div>

                <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                    <div>
                        <x-input-label for="password" value="Senha *" />
                        <x-text-input wire:model="password" id="password" class="block mt-1 w-full" type="password" required autocomplete="new-password" />
                        <x-input-error :messages="$errors->get('password')" class="mt-2" />
                    </div>
                    <div>
                        <x-input-label for="password_confirmation" value="Confirme a senha *" />
                        <x-text-input wire:model="password_confirmation" id="password_confirmation" class="block mt-1 w-full" type="password" required autocomplete="new-password" />
                    </div>
                </div>

                <x-primary-button class="w-full justify-center" wire:loading.attr="disabled" wire:target="register,photo">Criar conta e entrar</x-primary-button>
            </form>
        </div>
    @else
        <div class="space-y-4">
            <div class="text-center">
                <h1 class="text-xl font-semibold text-gray-900">Olá, {{ $inviteeName }}!</h1>
                <p class="text-gray-600 mt-1">Você foi convidado(a) para a equipe do <strong>{{ $hospitalName }}</strong>.</p>
            </div>

            <form wire:submit="accept" class="space-y-4">
                @if (! $existingUser)
                    <div>
                        <x-input-label for="password" value="Defina sua senha" />
                        <x-text-input wire:model="password" id="password" class="block mt-1 w-full" type="password" required autofocus autocomplete="new-password" />
                        <x-input-error :messages="$errors->get('password')" class="mt-2" />
                    </div>

                    <div>
                        <x-input-label for="password_confirmation" value="Confirme a senha" />
                        <x-text-input wire:model="password_confirmation" id="password_confirmation" class="block mt-1 w-full" type="password" required autocomplete="new-password" />
                    </div>
                @else
                    <p class="text-sm text-gray-600 text-center">Você já tem uma conta no DoctorTurn — é só aceitar pra entrar nessa equipe também.</p>
                @endif

                <x-primary-button class="w-full justify-center">Aceitar convite</x-primary-button>
            </form>
        </div>
    @endif

    @script
    <script>
        (function initPhoneMask() {
            const input = document.getElementById('phone');
            if (! input) return;

            const format = (value) => {
                const digits = value.replace(/\D/g, '').slice(0, 11);

                if (digits.length <= 2) return digits.length ? `(${digits}` : '';
                if (digits.length <= 7) return `(${digits.slice(0, 2)}) ${digits.slice(2)}`;

                return `(${digits.slice(0, 2)}) ${digits.slice(2, 7)}-${digits.slice(7)}`;
            };

            input.value = format($wire.get('phone') || '');
            input.addEventListener('input', () => {
                input.value = format(input.value);
                $wire.set('phone', input.value, false);
            });
        })();
    </script>
    @endscript
</div>
