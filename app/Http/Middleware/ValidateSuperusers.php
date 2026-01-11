<?php

namespace App\Http\Middleware;

use App\Models\User;
use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Schema;
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
        // Verificar que las tablas necesarias existan
        if (!$this->validateRequiredTablesExist())
        {
            $message = 'El sistema no está correctamente inicializado. ';
            $message .= 'Las tablas necesarias no existen. ';
            $message .= 'Por favor ejecute las migraciones de la base de datos.';

            abort(500, $message);
        }

        // Verificar si ya existe un superusuario activo
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

    /**
     * Valida que las tablas necesarias para el instalador existan.
     */
    private function validateRequiredTablesExist(): bool
    {
        $requiredTables = [
            'users',
            'people',
            'roles',
            'model_has_roles',
            'organizational_units',
        ];

        foreach ($requiredTables as $table)
        {
            if (!Schema::hasTable($table))
            {
                return false;
            }
        }

        return true;
    }
}
