<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class CheckPermission
{
    /**
     * Handle an incoming request.
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     */
    public function handle(Request $request, Closure $next, string ...$permissions): Response
    {
        $user = $request->user();

        if (!$user) {
            if ($request->expectsJson()) {
                return response()->json(['success' => false, 'message' => 'No autenticado.'], 401);
            }
            return redirect()->route('login');
        }

        // Si es Super Administrador, tiene acceso total inmediato
        if ($user->hasRole('super-admin')) {
            return $next($request);
        }

        // Si no se pasaron permisos específicos, solo verificar que tenga un rol activo
        if (empty($permissions)) {
            return $next($request);
        }

        // Verificar si cuenta con al menos uno de los permisos requeridos
        foreach ($permissions as $permission) {
            if ($user->hasPermission($permission)) {
                return $next($request);
            }
        }

        if ($request->expectsJson()) {
            return response()->json([
                'success' => false,
                'message' => 'Acceso denegado: Tu perfil no cuenta con los permisos necesarios para realizar esta acción.',
            ], 403);
        }

        abort(403, 'Acceso denegado: No cuentas con autorización para acceder a este módulo.');
    }
}
