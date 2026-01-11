<?php

namespace App\InertiaProps\Security;

use App\Models\Security\Permission;
use Inertia\Inertia;

/**
 * InertiaProps para la vista Create de Roles.
 */
class RoleCreateProps
{
    public function __construct()
    {
    }

    public function toArray(): array
    {
        $page = request()->input('page', 1);
        $perPage = request()->input('per_page', 10);

        $permissions = Permission::filter(request()->only(['search']))
            ->paginate($perPage, page: $page);

        return [
            'permissions' => Inertia::merge(fn() => $permissions->items()),
            'pagination' => $permissions->toArray(),
            'filters' => request()->all(['search']),
        ];
    }
}
