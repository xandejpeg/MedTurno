<?php

namespace App\Http\Middleware;

use App\Models\Hospital;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class ApiTokenAuth
{
    public function handle(Request $request, Closure $next): Response
    {
        $token = $request->bearerToken() ?? $request->header('X-Api-Token');

        if ($token === null || $token === '') {
            return response()->json(['message' => 'Token de API não informado.'], 401);
        }

        $hospital = Hospital::where('api_token', $token)->first();

        if ($hospital === null) {
            return response()->json(['message' => 'Token de API inválido.'], 401);
        }

        $request->attributes->set('hospital', $hospital);

        return $next($request);
    }
}
