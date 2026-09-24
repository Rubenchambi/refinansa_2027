<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class CheckRole
{
    /**
     * Handle an incoming request.
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     */
    public function handle(Request $request, Closure $next, ...$roles): Response
    {
        $user = $request->user();

        // Verificar si el usuario está autenticado y si su role coincide con los permitidos
        if (!$user || !in_array($user->role, $roles)) {
            return response()->json([
                $user,
                'message' => 'Acceso no autorizado. No cuentas con los permisos necesarios para realizar esta acción.'
            ], 403);
        }

        return $next($request);
    }
}