<?php

namespace App\InertiaProps\Security;

use App\Http\Resources\Security\PermissionCollection;
use App\Models\Security\Permission;
use App\Models\Security\Role;
use App\Models\User;
use App\Support\PaginationBuilder;
use App\Support\PermissionChecker;
use Inertia\Inertia;

/**
 * InertiaProps para la vista Index de Permissions.
 */
class PermissionIndexProps
{
    public function __construct(
        private PermissionChecker $permissions,
        private PaginationBuilder $pagination
    ) {
    }

    public function toArray(): array
    {
        $allowedFilters = ['search', 'sort_by', 'roles', 'users'];

        return [
            'can' => $this->permissions->checkResource('permissions'),
            'filters' => request()->all($allowedFilters),
            'roles' => Inertia::optional(fn() => $this->getRolesForFilter()),
            'users' => Inertia::optional(fn() => $this->getUsersForFilter()),
            'permissions' => fn() => $this->getPermissions($allowedFilters),
        ];
    }

    private function getPermissions(array $allowedFilters): PermissionCollection
    {
        return new PermissionCollection(
            $this->pagination->paginate(
                Permission::filter(request()->only($allowedFilters))
            )
        );
    }

    private function getRolesForFilter(): array
    {
        return Role::select(['name'])
            ->orderBy('name', 'asc')
            ->get()
            ->toArray();
    }

    private function getUsersForFilter(): array
    {
        return User::select(['name'])
            ->orderBy('name', 'asc')
            ->withTrashed()
            ->get()
            ->toArray();
    }
}
