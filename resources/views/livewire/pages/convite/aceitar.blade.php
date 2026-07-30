<?php

use App\Models\User;
use App\Services\InvitationService;
use App\Support\PhoneNumber;
use Illuminate\Support\Facades\Auth;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Locked;
use Livewire\Volt\Component;

new #[Layout('layouts.guest')] class extends Component
{
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

    public string $phoneCountry = 'BR';

    public string $phoneNumber = '';

    public string $crm = '';

    public string $crm_uf = '';

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
            'phoneCountry' => ['required', 'string', 'size:2'],
            'phoneNumber' => ['required', 'string', 'max:30'],
            'crm' => ['required', 'string', 'max:30'],
            'crm_uf' => ['nullable', 'string', 'max:2'],
            'password' => ['required', 'string', 'min:8', 'confirmed'],
        ], [
            'phoneNumber.required' => 'Informe o celular com código de área.',
        ], [
            'name' => 'nome completo',
            'cpf' => 'CPF',
            'phoneNumber' => 'celular',
        ]);

        $phone = PhoneNumber::toE164($validated['phoneCountry'], $validated['phoneNumber']);

        if ($phone === null) {
            $this->addError('phoneNumber', 'Digite um celular válido para o país selecionado.');

            return;
        }

        try {
            $user = $service->acceptGroup($this->token, [
                'name' => $validated['name'],
                'email' => $validated['email'],
                'cpf' => preg_replace('/\D/', '', $validated['cpf']),
                'phone' => $phone,
                'crm' => $validated['crm'],
                'crm_uf' => $validated['crm_uf'] !== '' ? strtoupper($validated['crm_uf']) : null,
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

                <div>
                    <x-input-label for="cpf" value="CPF *" />
                    <x-text-input wire:model="cpf" id="cpf" class="block mt-1 w-full" type="text" required placeholder="000.000.000-00" />
                    <x-input-error :messages="$errors->get('cpf')" class="mt-2" />
                </div>

                <x-phone-input country-model="phoneCountry" number-model="phoneNumber" id="group-phone" label="Celular" required />

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

                <x-primary-button class="w-full justify-center" wire:loading.attr="disabled" wire:target="register">Criar conta e entrar</x-primary-button>
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

</div>
