<?php

namespace Tests\Feature\Auth;

use App\Models\User;
use Illuminate\Auth\Notifications\ResetPassword;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Facades\Password;
use Livewire\Volt\Volt;

test('reset password link screen can be rendered', function () {
    $response = $this->get('/forgot-password');

    $response
        ->assertSeeVolt('pages.auth.forgot-password')
        ->assertSee('Recuperar senha')
        ->assertSee('Enviar link de recuperação')
        ->assertStatus(200);
});

test('reset password link can be requested', function () {
    Notification::fake();

    $user = User::factory()->create();

    Volt::test('pages.auth.forgot-password')
        ->set('email', $user->email)
        ->call('sendPasswordResetLink');

    Notification::assertSentTo($user, ResetPassword::class, function ($notification) use ($user) {
        $url = $notification->toMail($user)->actionUrl;

        expect($url)
            ->toContain('/reset-password/'.$notification->token)
            ->toContain('email='.urlencode($user->email));

        return true;
    });
});

test('unknown email receives the same safe response without sending a notification', function () {
    Notification::fake();

    Volt::test('pages.auth.forgot-password')
        ->set('email', 'nao-cadastrado@example.com')
        ->call('sendPasswordResetLink')
        ->assertHasNoErrors()
        ->assertSee(__(Password::RESET_LINK_SENT));

    Notification::assertNothingSent();
});

test('reset password screen can be rendered', function () {
    Notification::fake();

    $user = User::factory()->create();

    Volt::test('pages.auth.forgot-password')
        ->set('email', $user->email)
        ->call('sendPasswordResetLink');

    Notification::assertSentTo($user, ResetPassword::class, function ($notification) use ($user) {
        $response = $this->get('/reset-password/'.$notification->token.'?email='.urlencode($user->email));

        $response
            ->assertSeeVolt('pages.auth.reset-password')
            ->assertSee('Crie uma nova senha')
            ->assertSee('Confirme a nova senha')
            ->assertSee($user->email)
            ->assertStatus(200);

        return true;
    });
});

test('password can be reset with valid token', function () {
    Notification::fake();

    $user = User::factory()->create();

    Volt::test('pages.auth.forgot-password')
        ->set('email', $user->email)
        ->call('sendPasswordResetLink');

    Notification::assertSentTo($user, ResetPassword::class, function ($notification) use ($user) {
        $newPassword = 'NovaSenhaSegura123!';

        $component = Volt::test('pages.auth.reset-password', ['token' => $notification->token])
            ->set('email', $user->email)
            ->set('password', $newPassword)
            ->set('password_confirmation', $newPassword);

        $component->call('resetPassword');

        $component
            ->assertRedirect('/login')
            ->assertHasNoErrors();

        expect(Hash::check($newPassword, $user->fresh()->password))->toBeTrue();

        return true;
    });
});

test('password confirmation must match', function () {
    Notification::fake();

    $user = User::factory()->create();

    Volt::test('pages.auth.forgot-password')
        ->set('email', $user->email)
        ->call('sendPasswordResetLink');

    Notification::assertSentTo($user, ResetPassword::class, function ($notification) use ($user) {
        Volt::test('pages.auth.reset-password', ['token' => $notification->token])
            ->set('email', $user->email)
            ->set('password', 'NovaSenhaSegura123!')
            ->set('password_confirmation', 'SenhaDiferente123!')
            ->call('resetPassword')
            ->assertHasErrors(['password' => 'confirmed']);

        return true;
    });
});

test('admin returns to admin login after resetting password', function () {
    Notification::fake();

    $admin = User::factory()->create(['is_admin' => true]);

    Volt::test('pages.auth.forgot-password')
        ->set('email', $admin->email)
        ->call('sendPasswordResetLink');

    Notification::assertSentTo($admin, ResetPassword::class, function ($notification) use ($admin) {
        Volt::test('pages.auth.reset-password', ['token' => $notification->token])
            ->set('email', $admin->email)
            ->set('password', 'NovaSenhaSegura123!')
            ->set('password_confirmation', 'NovaSenhaSegura123!')
            ->call('resetPassword')
            ->assertRedirect('/admin')
            ->assertHasNoErrors();

        return true;
    });
});
