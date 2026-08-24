<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Models\User;
use App\Services\ImpersonationService;
use Illuminate\Http\RedirectResponse;

class ImpersonationController extends Controller
{
    /**
     * Admin entra no app como o usuário informado.
     */
    public function start(User $user, ImpersonationService $service): RedirectResponse
    {
        $admin = auth()->user();

        abort_unless($admin !== null, 403);

        $service->start($admin, $user);

        return redirect()->route('dashboard')
            ->with('status', 'Você está vendo o sistema como '.$user->name.'.');
    }

    /**
     * Volta para a sessão do admin original.
     */
    public function stop(ImpersonationService $service): RedirectResponse
    {
        $admin = $service->stop();

        if ($admin === null) {
            return redirect()->route('admin.login');
        }

        return redirect()->route('admin.operadores')
            ->with('status', 'Você voltou para a sua conta de administrador.');
    }
}
