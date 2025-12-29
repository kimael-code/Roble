<?php

namespace App\InertiaProps\Security;

use App\Http\Resources\Monitoring\ActivityLogCollection;
use App\Http\Resources\Security\RoleCollection;
use App\Http\Resources\Security\UserCollection;
use App\Models\Monitoring\ActivityLog;
use App\Models\Security\Permission;
use App\Models\User;
use App\Support\PaginationBuilder;
use App\Support\PermissionChecker;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Arr;

/**
 * InertiaProps para la vista Show de Permissions.
 */
class PermissionShowProps
{
    public function __construct(
        private PermissionChecker $permissions,
        private PaginationBuilder $pagination
    ) {
    }

    public function toArray(Permission $permission): array
    {
        $search = request()->only(['search', 'name']);

        return [
            'can' => Arr::except($this->permissions->checkResource('permissions'), 'read'),
            'filters' => request()->all(['search', 'name']),
            'permission' => $permission,
            'roles' => fn() => $this->getRoles($permission, $search),
            'users' => fn() => $this->getUsers($permission, $search),
            'logs' => fn() => $this->getLogs($permission, $search),
        ];
    }

    private function getRoles(Permission $permission, array $search): RoleCollection
    {
        return new RoleCollection(
            $this->pagination->paginate(
                $permission->roles()->filter($search)->latest(),
                pageName: 'page_r',
                perPageName: 'per_page_r'
            )
        );
    }

    private function getUsers(Permission $permission, array $search): UserCollection
    {
        return new UserCollection(
            $this->pagination->paginate(
                User::filter($search)->permission($permission->name)->latest(),
                pageName: 'page_u',
                perPageName: 'per_page_u'
            )
        );
    }

    private function getLogs(Permission $permission, array $search): ActivityLogCollection
    {
        return new ActivityLogCollection(
            $this->pagination->paginate(
                ActivityLog::filter(request()->only(['search']))
                    ->whereHasMorph(
                        'subject',
                        Permission::class,
                        fn(Builder $query) => $query->where('id', $permission->id)
                    )
                    ->latest(),
                pageName: 'page_l',
                perPageName: 'per_page_l'
            )
        );
    }
}

