<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class EnsureUserIsAdmin
{
    public function handle(Request $request, Closure $next): Response
    {
        $user = $request->user();

        if (!$user || $user->rol !== 'administrador') {
            return response()->json([
                'status' => 'error',
                'message' => 'Acceso no autorizado. Se requieren permisos de administrador.'
            ], 403);
        }

        return $next($request);
    }
}
