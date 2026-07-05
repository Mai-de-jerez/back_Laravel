<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class AdminMiddleware
{
    public function handle(Request $request, Closure $next): Response
    {
        if (!auth()->check()) {
            return response()->json(['mensaje' => 'No autenticado'], 401);
        }

        if (!auth()->user()->esAdmin()) {
            return response()->json([
                'mensaje' => 'No tienes permisos de administrador',
                'tu_rol' => auth()->user()->rol?->value ?? 'sin rol',
            ], 403);
        }

        return $next($request);
    }
}



