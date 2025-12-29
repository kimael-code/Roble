<?php

namespace App\InertiaProps\Security;

use App\Models\Security\Permission;
use App\Models\Security\Role;
use Inertia\Inertia;

/**
 * InertiaProps para la vista Edit de Roles.
 */
class RoleEditProps
{
    public function __construct()
    {
    }

    public function toArray(Role $role): array
    {
        $page = request()->input('page', 1);
        $perPage = request()->input('per_page', 10);

        $permissions = Permission::filter(request()->only(['search']))
            ->paginate($perPage, page: $page);

        return [
            'permissions' => Inertia::merge(fn() => $permissions->items()),
            'pagination' => $permissions->toArray(),
            'filters' => request()->all(['search']),
            'role' => $role,
            'rolePermissions' => $role->permissions()->pluck('description')->all(),
        ];
    }
}
