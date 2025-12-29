<?php

namespace App\InertiaProps\Security;

use App\Http\Resources\Security\UserCollection;
use App\Models\Security\Permission;
use App\Models\Security\Role;
use App\Models\User;
use App\Support\PaginationBuilder;
use App\Support\PermissionChecker;
use Inertia\Inertia;

/**
 * InertiaProps para la vista Index de Users.
 *
 * Prepara todos los datos necesarios para la vista Vue de listado de usuarios,
 * incluyendo permisos, filtros, y datos paginados.
 */
class UserIndexProps
{
    public function __construct(
        private PermissionChecker $permissions,
        private PaginationBuilder $pagination
    ) {
    }

    /**
     * Convierte los props a array para Inertia.
     *
     * @return array Props para la vista Vue
     */
    public function toArray(): array
    {
        $allowedFilters = [
            'permissions',
            'roles',
            'statuses',
            'search',
            'sort_by',
        ];

        return [
            'can' => $this->permissions->checkResource('users'),
            'filters' => request()->all($allowedFilters),
            'permissions' => Inertia::optional(fn() => $this->getPermissionsForFilter()),
            'roles' => Inertia::optional(fn() => $this->getRolesForFilter()),
            'statuses' => Inertia::optional(fn() => $this->getStatuses()),
            'users' => fn() => $this->getUsers($allowedFilters),
        ];
    }

    /**
     * Obtiene los usuarios paginados y filtrados.
     */
    private function getUsers(array $allowedFilters): UserCollection
    {
        return new UserCollection(
            $this->pagination->paginate(
                User::withTrashed()->filter(
                    request()->only($allowedFilters)
                )
            )
        );
    }

    /**
     * Obtiene los permisos para el filtro.
     */
    private function getPermissionsForFilter(): array
    {
        return Permission::select(['name', 'description'])
            ->orderBy('description', 'asc')
            ->get()
            ->toArray();
    }

    /**
     * Obtiene los roles para el filtro.
     */
    private function getRolesForFilter(): array
    {
        return Role::select(['name'])
            ->orderBy('name', 'asc')
            ->get()
            ->toArray();
    }

    /**
     * Obtiene los estados posibles para el filtro.
     */
    private function getStatuses(): array
    {
        return [
            ['value' => 'active', 'label' => 'Activo'],
            ['value' => 'inactive', 'label' => 'Inactivo'],
            ['value' => 'disabled', 'label' => 'Desactivado'],
            ['value' => 'deleted', 'label' => 'Eliminado'],
        ];
    }
}
