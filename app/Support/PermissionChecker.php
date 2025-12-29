<?php

namespace App\Support;

use Illuminate\Support\Facades\Auth;

/**
 * Centraliza la verificación de permisos del usuario autenticado.
 *
 * Esta clase elimina la duplicación del método getPermissions() que
 * estaba repetido en todas las clases Props.
 */
class PermissionChecker
{
    /**
     * Verifica UN solo permiso para el usuario autenticado.
     *
     * @param string $permission Nombre del permiso a verificar
     * @param bool $strict Si true, usa hasPermissionTo() ignorando Superusuario
     * @return bool True si el usuario tiene el permiso
     */
    public function can(string $permission, bool $strict = false): bool
    {
        $user = Auth::user();

        return $strict
            ? $user->hasPermissionTo($permission)
            : $user->can($permission);
    }

    /**
     * Verifica múltiples permisos para el usuario autenticado.
     *
     * @param array $permissions Lista de nombres de permisos
     * @param bool $strict Si true, usa hasPermissionTo() ignorando Superusuario
     * @return array Array asociativo con permiso => bool
     */
    public function check(array $permissions, bool $strict = false): array
    {
        $user = Auth::user();
        $result = [];

        foreach ($permissions as $permission)
        {
            $key = $this->normalizeKey($permission);
            $result[$key] = $strict
                ? $user->hasPermissionTo($permission)
                : $user->can($permission);
        }

        return $result;
    }

    /**
     * Verifica permisos CRUD estándar para un recurso.
     *
     * @param string $resource Nombre del recurso (ej: 'users', 'roles')
     * @param bool $strict Si true, usa hasPermissionTo() ignorando Superusuario
     * @return array Array con claves: create, read, update, delete, export
     */
    public function checkResource(string $resource, bool $strict = false): array
    {
        $singular = str($resource)->singular();

        $permissions = [
            "create new {$resource}",
            "read {$singular}",
            "update {$resource}",
            "delete {$resource}",
            "export {$resource}",
        ];

        // Permisos adicionales específicos para usuarios
        if ($resource === 'users')
        {
            $permissions = array_merge($permissions, [
                'force delete users',
                'restore users',
                'enable users',
                'disable users',
                'reset user passwords',
            ]);
        }

        $result = $this->check($permissions, $strict);

        // Mapear export a ambas propiedades de exportación
        if (isset($result['export']))
        {
            $result['export_collection'] = $result['export'];
            $result['export_record'] = $result['export'];
            unset($result['export']);
        }

        return $result;
    }

    /**
     * Normaliza el nombre del permiso a una clave corta.
     *
     * Ejemplos:
     * - 'create new users' => 'create'
     * - 'read user' => 'read'
     * - 'force delete users' => 'delete_force'
     * - 'resend activation' => 'resend_activation'
     *
     * @param string $permission Nombre completo del permiso
     * @return string Clave normalizada
     */
    private function normalizeKey(string $permission): string
    {
        $map = [
            'create new' => 'create',
            'read' => 'read',
            'update' => 'update',
            'delete' => 'delete',
            'force delete' => 'delete_force',
            'export' => 'export',
            'restore' => 'restore',
            'enable' => 'enable',
            'disable' => 'disable',
            'send' => 'send',
            'resend activation' => 'resend_activation',
            'reset' => 'reset_password',
        ];

        foreach ($map as $pattern => $key)
        {
            if (str_starts_with($permission, $pattern))
            {
                return $key;
            }
        }

        // Fallback: reemplazar espacios por guiones bajos
        return str_replace(' ', '_', $permission);
    }
}
