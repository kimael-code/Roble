<?php

namespace App\InertiaProps\Security;

use App\Http\Resources\Monitoring\ActivityLogCollection;
use App\Http\Resources\Security\PermissionCollection;
use App\Http\Resources\Security\UserCollection;
use App\Models\Monitoring\ActivityLog;
use App\Models\Security\Role;
use App\Models\User;
use App\Support\PaginationBuilder;
use App\Support\PermissionChecker;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Arr;

/**
 * InertiaProps para la vista Show de Roles.
 */
class RoleShowProps
{
    public function __construct(
        private PermissionChecker $permissions,
        private PaginationBuilder $pagination
    ) {
    }

    public function toArray(Role $role): array
    {
        $search = request()->only(['search']);

        return [
            'can' => Arr::except($this->permissions->checkResource('roles'), 'read'),
            'filters' => request()->all(['search']),
            'role' => $role,
            'permissions' => fn() => $this->getPermissions($role, $search),
            'users' => fn() => $this->getUsers($role, $search),
            'logs' => fn() => $this->getLogs($role, $search),
        ];
    }

    private function getPermissions(Role $role, array $search): PermissionCollection
    {
        return new PermissionCollection(
            $this->pagination->paginate(
                $role->permissions()->filter($search)->latest(),
                pageName: 'page_p',
                perPageName: 'per_page_p'
            )
        );
    }

    private function getUsers(Role $role, array $search): UserCollection
    {
        return new UserCollection(
            $this->pagination->paginate(
                User::filter($search)->role($role->name)->latest(),
                pageName: 'page_u',
                perPageName: 'per_page_u'
            )
        );
    }

    private function getLogs(Role $role, array $search): ActivityLogCollection
    {
        return new ActivityLogCollection(
            $this->pagination->paginate(
                ActivityLog::filter($search)
                    ->whereHasMorph(
                        'subject',
                        Role::class,
                        fn(Builder $query) => $query->where('id', $role->id)
                    )
                    ->latest(),
                pageName: 'page_l',
                perPageName: 'per_page_l'
            )
        );
    }
}

