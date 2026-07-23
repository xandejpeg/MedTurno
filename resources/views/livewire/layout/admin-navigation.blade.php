<?php

use App\Livewire\Actions\Logout;
use Livewire\Volt\Component;

new class extends Component
{
    public function logout(Logout $logout): void
    {
        $logout();

        $this->redirect(route('admin.login', absolute: false), navigate: true);
    }
}; ?>

<button wire:click="logout" type="button" class="rounded px-2 py-1 text-xs text-teal-100/70 hover:bg-white/10 hover:text-white" title="Sair">
    Sair
</button>