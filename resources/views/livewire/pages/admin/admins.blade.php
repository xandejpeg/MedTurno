<?php

use App\Models\User;
use Illuminate\Auth\Events\Registered;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Password;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Url;
use Livewire\Volt\Component;

new #[Layout('layouts.admin')] class extends Component
{
    #[Url]
    public string $tab = 'listar';

    // Form criar novo admin
    public string $name = '';

    public string $email = '';

    public string $password = '';

    public string $password_confirmation = '';

    public ?int $editingId = null;

    public string $editingName = '';

    public string $editingEmail = '';

    public bool $editingIsActive = true;

    public function updatedTab(): void
    {
        if ($this->tab !== 'editar') {
            $this->editingId = null;
        }
    }

    public function createAdmin(): void
    {
        $validated = $this->validate([
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'email', 'max:255', Rule::unique(User::class)],
            'password' => ['required', 'string', 'min:8', 'confirmed'],
        ]);

        $user = User::create([
            'name' => $validated['name'],
            'email' => $validated['email'],
            'password' => $validated['password'],
            'is_admin' => true,
            'active' => true,
            'email_verified_at' => now(),
        ]);

        event(new Registered($user));

        $this->reset(['name', 'email', 'password', 'password_confirmation']);
        $this->tab = 'listar';

        $this->dispatch('admin-created');
        session()->flash('status', 'Administrador criado com sucesso.');
    }

    public function selectEdit(int $id): void
    {
        $admin = User::where('is_admin', true)->findOrFail($id);
        $this->editingId = $admin->id;
        $this->editingName = $admin->name;
        $this->editingEmail = $admin->email;
        $this->editingIsActive = (bool) $admin->active;
        $this->tab = 'editar';
    }

    public function updateAdmin(): void
    {
        $this->validate([
            'editingName' => ['required', 'string', 'max:255'],
            'editingEmail' => ['required', 'email', 'max:255', Rule::unique(User::class, 'email')->ignore($this->editingId)],
        ]);

        $admin = User::where('is_admin', true)->findOrFail($this->editingId);
        $admin->update([
            'name' => $this->editingName,
            'email' => $this->editingEmail,
            'active' => $this->editingIsActive,
        ]);

        $this->tab = 'listar';
        $this->editingId = null;

        session()->flash('status', 'Administrador atualizado.');
    }

    public function toggleActive(int $id): void
    {
        $admin = User::where('is_admin', true)->findOrFail($id);
        $admin->update(['active' => ! $admin->active]);

        session()->flash('status', $admin->active ? 'Admin reativado.' : 'Admin desativado.');
    }

    public function sendResetLink(int $id): void
    {
        $admin = User::where('is_admin', true)->findOrFail($id);
        $status = Password::sendResetLink(['email' => $admin->email]);

        session()->flash(
            'status',
            $status === Password::RESET_LINK_SENT ? 'Link de redefinição enviado.' : 'Não foi possível enviar o link.'
        );
    }

    public function cancelEdit(): void
    {
        $this->editingId = null;
        $this->tab = 'listar';
    }

    /**
     * @return array<string, mixed>
     */
    public function with(): array
    {
        return [
            'admins' => User::where('is_admin', true)->orderBy('name')->get(),
        ];
    }
}; ?>

<div class="px-4 py-6 sm:px-6 lg:px-10 lg:py-9">
    <header class="mb-8 flex flex-col gap-2 sm:flex-row sm:items-end sm:justify-between">
        <div>
            <p class="text-xs font-semibold uppercase text-teal-700">Administração da plataforma</p>
            <h1 class="mt-1 text-2xl font-semibold text-gray-950">Administradores</h1>
            <p class="mt-1 text-sm text-gray-500">Gerenciar contas com acesso ao painel admin.</p>
        </div>
    </header>

    @if (session('status'))
        <div class="mb-4 rounded-md border border-green-200 bg-green-50 px-4 py-3 text-sm text-green-700">
            {{ session('status') }}
        </div>
    @endif

    <!-- Tabs -->
    <div class="mb-6 flex gap-2 border-b border-gray-200">
        <button wire:click="$set('tab', 'listar')" type="button"
                class="border-b-2 px-4 py-2 text-sm font-medium {{ $tab === 'listar' ? 'border-teal-600 text-teal-700' : 'border-transparent text-gray-500 hover:text-gray-700' }}">
            Listar
        </button>
        <button wire:click="$set('tab', 'novo')" type="button"
                class="border-b-2 px-4 py-2 text-sm font-medium {{ $tab === 'novo' ? 'border-teal-600 text-teal-700' : 'border-transparent text-gray-500 hover:text-gray-700' }}">
            Novo Admin
        </button>
        @if ($editingId)
            <button wire:click="$set('tab', 'editar')" type="button"
                    class="border-b-2 px-4 py-2 text-sm font-medium {{ $tab === 'editar' ? 'border-teal-600 text-teal-700' : 'border-transparent text-gray-500 hover:text-gray-700' }}">
                Editar
            </button>
        @endif
    </div>

    <!-- Tab: Listar -->
    @if ($tab === 'listar')
        <div class="overflow-hidden rounded-lg border border-gray-200 bg-white shadow-sm">
            <table class="w-full text-sm">
                <thead class="bg-gray-50 text-xs uppercase text-gray-500">
                    <tr>
                        <th class="px-4 py-3 text-left font-semibold">Nome</th>
                        <th class="px-4 py-3 text-left font-semibold">E-mail</th>
                        <th class="px-4 py-3 text-left font-semibold">Status</th>
                        <th class="px-4 py-3 text-left font-semibold">Criado em</th>
                        <th class="px-4 py-3 text-right font-semibold">Ações</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-100">
                    @foreach ($admins as $admin)
                        <tr class="hover:bg-gray-50">
                            <td class="px-4 py-3 font-medium text-gray-900">{{ $admin->name }}</td>
                            <td class="px-4 py-3 text-gray-600">{{ $admin->email }}</td>
                            <td class="px-4 py-3">
                                @if ($admin->active)
                                    <span class="inline-flex items-center rounded-full bg-green-100 px-2 py-0.5 text-xs font-medium text-green-700">Ativo</span>
                                @else
                                    <span class="inline-flex items-center rounded-full bg-red-100 px-2 py-0.5 text-xs font-medium text-red-700">Inativo</span>
                                @endif
                            </td>
                            <td class="px-4 py-3 text-gray-500">{{ $admin->created_at?->format('d/m/Y') }}</td>
                            <td class="px-4 py-3">
                                <div class="flex justify-end gap-2">
                                    <button wire:click="selectEdit({{ $admin->id }})" type="button"
                                            class="rounded-md bg-teal-50 px-2.5 py-1 text-xs font-medium text-teal-700 hover:bg-teal-100">
                                        Editar
                                    </button>
                                    <button wire:click="sendResetLink({{ $admin->id }})" type="button"
                                            class="rounded-md bg-blue-50 px-2.5 py-1 text-xs font-medium text-blue-700 hover:bg-blue-100">
                                        Resetar senha
                                    </button>
                                    @if ($admin->id !== auth()->id())
                                        <button wire:click="toggleActive({{ $admin->id }})" wire:confirm="Confirma {{ $admin->active ? 'desativar' : 'reativar' }} este admin?"
                                                type="button"
                                                class="rounded-md px-2.5 py-1 text-xs font-medium {{ $admin->active ? 'bg-red-50 text-red-700 hover:bg-red-100' : 'bg-green-50 text-green-700 hover:bg-green-100' }}">
                                            {{ $admin->active ? 'Desativar' : 'Reativar' }}
                                        </button>
                                    @endif
                                </div>
                            </td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>

    <!-- Tab: Novo -->
    @elseif ($tab === 'novo')
        <div class="mx-auto max-w-xl rounded-lg border border-gray-200 bg-white p-6 shadow-sm">
            <h2 class="mb-1 text-lg font-semibold text-gray-900">Criar novo administrador</h2>
            <p class="mb-5 text-sm text-gray-500">O usuário terá acesso completo ao painel admin.</p>

            <form wire:submit="createAdmin" class="space-y-4">
                <div>
                    <label class="mb-1 block text-sm font-medium text-gray-700">Nome</label>
                    <input type="text" wire:model="name" required
                           class="w-full rounded-md border-gray-300 shadow-sm focus:border-teal-500 focus:ring-teal-500">
                    @error('name') <p class="mt-1 text-xs text-red-600">{{ $message }}</p> @enderror
                </div>

                <div>
                    <label class="mb-1 block text-sm font-medium text-gray-700">E-mail</label>
                    <input type="email" wire:model="email" required
                           class="w-full rounded-md border-gray-300 shadow-sm focus:border-teal-500 focus:ring-teal-500">
                    @error('email') <p class="mt-1 text-xs text-red-600">{{ $message }}</p> @enderror
                </div>

                <div>
                    <label class="mb-1 block text-sm font-medium text-gray-700">Senha</label>
                    <input type="password" wire:model="password" required
                           class="w-full rounded-md border-gray-300 shadow-sm focus:border-teal-500 focus:ring-teal-500">
                    @error('password') <p class="mt-1 text-xs text-red-600">{{ $message }}</p> @enderror
                </div>

                <div>
                    <label class="mb-1 block text-sm font-medium text-gray-700">Confirmar senha</label>
                    <input type="password" wire:model="password_confirmation" required
                           class="w-full rounded-md border-gray-300 shadow-sm focus:border-teal-500 focus:ring-teal-500">
                    @error('password_confirmation') <p class="mt-1 text-xs text-red-600">{{ $message }}</p> @enderror
                </div>

                <div class="flex gap-3 pt-2">
                    <button type="submit"
                            class="rounded-md bg-teal-600 px-4 py-2 text-sm font-semibold text-white shadow-sm hover:bg-teal-700">
                        Criar administrador
                    </button>
                    <button wire:click="$set('tab', 'listar')" type="button"
                            class="rounded-md border border-gray-300 px-4 py-2 text-sm font-medium text-gray-700 hover:bg-gray-50">
                        Cancelar
                    </button>
                </div>
            </form>
        </div>

    <!-- Tab: Editar -->
    @elseif ($tab === 'editar' && $editingId)
        <div class="mx-auto max-w-xl rounded-lg border border-gray-200 bg-white p-6 shadow-sm">
            <h2 class="mb-5 text-lg font-semibold text-gray-900">Editar administrador</h2>

            <form wire:submit="updateAdmin" class="space-y-4">
                <div>
                    <label class="mb-1 block text-sm font-medium text-gray-700">Nome</label>
                    <input type="text" wire:model="editingName" required
                           class="w-full rounded-md border-gray-300 shadow-sm focus:border-teal-500 focus:ring-teal-500">
                    @error('editingName') <p class="mt-1 text-xs text-red-600">{{ $message }}</p> @enderror
                </div>

                <div>
                    <label class="mb-1 block text-sm font-medium text-gray-700">E-mail</label>
                    <input type="email" wire:model="editingEmail" required
                           class="w-full rounded-md border-gray-300 shadow-sm focus:border-teal-500 focus:ring-teal-500">
                    @error('editingEmail') <p class="mt-1 text-xs text-red-600">{{ $message }}</p> @enderror
                </div>

                <div class="flex items-center gap-2">
                    <input type="checkbox" wire:model="editingIsActive" id="editingIsActive"
                           class="rounded border-gray-300 text-teal-600 focus:ring-teal-500">
                    <label for="editingIsActive" class="text-sm text-gray-700">Conta ativa</label>
                </div>

                <div class="flex gap-3 pt-2">
                    <button type="submit"
                            class="rounded-md bg-teal-600 px-4 py-2 text-sm font-semibold text-white shadow-sm hover:bg-teal-700">
                        Salvar
                    </button>
                    <button wire:click="cancelEdit" type="button"
                            class="rounded-md border border-gray-300 px-4 py-2 text-sm font-medium text-gray-700 hover:bg-gray-50">
                        Cancelar
                    </button>
                </div>
            </form>
        </div>
    @endif
</div>
