<?php

namespace App\Http\Middleware;

use App\Services\ImpersonationService;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class EnsureUserIsAdmin
{
    public function handle(Request $request, Closure $next): Response
    {
        $user = $request->user();

        // Se o admin está personificando alguém, ele deixa de ser admin nesta
        // sessão. Em vez de um 403 seco (que acontece, por exemplo, num
        // duplo-clique ou refresh), manda de volta ao app com um aviso claro.
        if ($user !== null && ! $user->isAdmin() && $request->session()->has(ImpersonationService::SESSION_KEY)) {
            return redirect()->route('dashboard')->with(
                'status',
                'Você está no modo visualização. Use "Voltar ao painel admin" no topo da tela para retomar seu acesso.'
            );
        }

        abort_unless($user?->isAdmin() && (bool) $user->active, 403);

        return $next($request);
    }
}
