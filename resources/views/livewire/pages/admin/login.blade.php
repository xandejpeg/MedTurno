<?php

use App\Livewire\Forms\LoginForm;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Session;
use Illuminate\Validation\ValidationException;
use Livewire\Attributes\Layout;
use Livewire\Volt\Component;

new #[Layout('layouts.guest', params: [
    'loginImage' => 'images/admin-login-cover.png',
    'loginImageAlt' => 'Painel administrativo DoctorTurn',
])] class extends Component
{
    public LoginForm $form;

    public function mount(): void
    {
        if (auth()->user()?->isAdmin()) {
            $this->redirect(route('admin.dashboard', absolute: false), navigate: true);
        }
    }

    public function login(): void
    {
        $this->validate();

        $this->form->authenticate();

        if (! auth()->user()?->isAdmin()) {
            Auth::guard('web')->logout();
            Session::invalidate();
            Session::regenerateToken();

            throw ValidationException::withMessages([
                'form.email' => trans('auth.failed'),
            ]);
        }

        Session::regenerate();

        $this->redirect(route('admin.dashboard', absolute: false), navigate: true);
    }
}; ?>

@include('livewire.pages.auth.partials.login-form', ['adminLogin' => true])