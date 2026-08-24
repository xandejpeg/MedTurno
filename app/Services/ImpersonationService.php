<?php

declare(strict_types=1);

namespace App\Services;

use App\Models\User;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Session;

/**
 * Permite que um administrador acesse o app "pelos olhos" de outro usuário.
 *
 * O id do admin original fica guardado na sessão, então o retorno é sempre
 * possível. Sessão é regenerada nas duas pontas para evitar fixation.
 */
class ImpersonationService
{
    public const SESSION_KEY = 'impersonator_id';

    /**
     * Admin assume a identidade de outro usuário.
     */
    public function start(User $admin, User $target): void
    {
        abort_unless($admin->isAdmin() && $admin->active, 403);

        // já personificando? mantém o admin ORIGINAL como dono da sessão
        $originalId = Session::get(self::SESSION_KEY, $admin->id);

        abort_if($target->id === $originalId, 400, 'Não é possível personificar a si mesmo.');
        abort_if($target->isAdmin(), 403, 'Não é possível personificar outro administrador.');

        // o hospital selecionado é do admin/usuário anterior, não pode vazar
        Session::forget('current_hospital_id');

        Auth::login($target);
        Session::regenerate();

        // regenerate() preserva os dados, mas gravamos depois para garantir a ordem
        Session::put(self::SESSION_KEY, $originalId);
    }

    /**
     * Volta para o admin original.
     */
    public function stop(): ?User
    {
        $adminId = Session::pull(self::SESSION_KEY);

        if ($adminId === null) {
            return null;
        }

        $admin = User::find($adminId);

        if ($admin === null || ! $admin->isAdmin() || ! $admin->active) {
            Auth::logout();
            Session::invalidate();
            Session::regenerateToken();

            return null;
        }

        Session::forget('current_hospital_id');

        Auth::login($admin);
        Session::regenerate();

        return $admin;
    }

    public function isImpersonating(): bool
    {
        return Session::has(self::SESSION_KEY);
    }

    /**
     * O admin que iniciou a personificação (se houver).
     */
    public function impersonator(): ?User
    {
        $id = Session::get(self::SESSION_KEY);

        return $id === null ? null : User::find($id);
    }
}
