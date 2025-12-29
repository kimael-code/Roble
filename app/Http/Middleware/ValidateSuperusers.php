<?php

namespace App\Http\Middleware;

use App\Models\User;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class ValidateSuperusers
{
    /**
     * Handle an incoming request.
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        $superusersCount = User::with('roles')->active()->get()->filter(
            fn($user) => $user->roles->where('name', 'Superusuario')->isNotEmpty()
        )->count();

        if ($superusersCount > 0)
        {
            $message = 'Ya existe un Superusuario activo en el sistema.';
            $message .= ' Para crear o asignar el rol de Superusuario a otros ';
            $message .= ' usuarios, debe acceder al módulo Seguridad/Usuarios';
            $message .= ' del sistema desde una cuenta de Superusuario activa.';

            abort(403, $message);
        }

        return $next($request);
    }
}
