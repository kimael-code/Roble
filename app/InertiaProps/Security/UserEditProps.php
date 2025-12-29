<?php

namespace App\InertiaProps\Security;

use App\Models\Organization\OrganizationalUnit;
use App\Models\Security\Permission;
use App\Models\Security\Role;
use App\Models\User;
use App\Repositories\EmployeeRepository;
use Inertia\Inertia;

/**
 * InertiaProps para la vista Edit de Users.
 */
class UserEditProps
{
    public function __construct(
        private EmployeeRepository $employeeRepository
    ) {
    }

    public function toArray(User $user): array
    {
        $page = request()->input('page', 1);
        $perPage = request()->input('per_page', 10);
        $search = request()->only(['search']);

        $permissions = Permission::filter($search)->paginate($perPage, page: $page);
        $roles = Role::filter($search)->superuser()->paginate($perPage, page: $page);
        $ous = OrganizationalUnit::active()->filter($search)->paginate($perPage, page: $page);

        return [
            'filters' => request()->all(['search']),
            'user' => $user->load(['roles', 'permissions', 'person', 'activeOrganizationalUnits']),
            'employees' => $search ? $this->employeeRepository->find($search['search'] ?? '') : [],
            'ous' => Inertia::merge(fn() => $ous->items()),
            'permissions' => Inertia::merge(fn() => $permissions->items()),
            'roles' => Inertia::merge(fn() => $roles->items()),
            'paginationOu' => $ous->toArray(),
            'paginationPerm' => $permissions->toArray(),
            'paginationRole' => $roles->toArray(),
        ];
    }
}
