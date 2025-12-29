<?php

namespace App\InertiaProps\Security;

use App\Http\Resources\Security\RoleCollection;
use App\Models\Security\Permission;
use App\Models\Security\Role;
use App\Support\PaginationBuilder;
use App\Support\PermissionChecker;
use Inertia\Inertia;

/**
 * InertiaProps para la vista Index de Roles.
 */
class RoleIndexProps
{
    public function __construct(
        private PermissionChecker $permissions,
        private PaginationBuilder $pagination
    ) {
    }

    public function toArray(): array
    {
        $allowedFilters = ['search', 'sort_by', 'permissions'];

        return [
            'can' => $this->permissions->checkResource('roles'),
            'filters' => request()->all($allowedFilters),
            'permissions' => Inertia::optional(fn() => $this->getPermissionsForFilter()),
            'roles' => fn() => $this->getRoles($allowedFilters),
        ];
    }

    private function getRoles(array $allowedFilters): RoleCollection
    {
        return new RoleCollection(
            $this->pagination->paginate(
                Role::filter(request()->only($allowedFilters))
            )
        );
    }

    private function getPermissionsForFilter(): array
    {
        return Permission::select(['name', 'description'])
            ->orderBy('description', 'asc')
            ->get()
            ->toArray();
    }
}
