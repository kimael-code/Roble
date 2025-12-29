<?php

namespace App\InertiaProps\Security;

use App\Http\Resources\Monitoring\ActivityLogCollection;
use App\Http\Resources\Security\PermissionCollection;
use App\Http\Resources\Security\RoleCollection;
use App\Models\Monitoring\ActivityLog;
use App\Models\User;
use App\Support\PaginationBuilder;
use App\Support\PermissionChecker;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Arr;

/**
 * InertiaProps para la vista Show de Users.
 */
class UserShowProps
{
    public function __construct(
        private PermissionChecker $permissions,
        private PaginationBuilder $pagination
    ) {
    }

    public function toArray(User $user): array
    {
        $search = request()->only(['search']);

        return [
            'can' => Arr::except($this->permissions->checkResource('users'), 'read'),
            'filters' => request()->all(['search']),
            'user' => $user->load(['person', 'activeOrganizationalUnits']),
            'permissions' => fn() => $this->getPermissions($user, $search),
            'permissionsCount' => fn() => $user->getAllPermissions()->count(),
            'roles' => fn() => $this->getRoles($user, $search),
            'logs' => fn() => $this->getLogs($user, $search),
        ];
    }

    private function getPermissions(User $user, array $search): PermissionCollection
    {
        $permissions = $user->getAllPermissions()->filter(function ($permission) use ($search)
        {
            if (isset($search['search']))
            {
                return str_contains($permission->description, $search['search']);
            }
            return $permission;
        })->all();

        return new PermissionCollection($permissions);
    }

    private function getRoles(User $user, array $search): RoleCollection
    {
        return new RoleCollection(
            $this->pagination->paginate(
                $user->roles()->filter($search)->latest(),
                pageName: 'page_r',
                perPageName: 'per_page_r'
            )
        );
    }

    private function getLogs(User $user, array $search): ActivityLogCollection
    {
        return new ActivityLogCollection(
            $this->pagination->paginate(
                ActivityLog::filter($search)
                    ->whereHasMorph(
                        'causer',
                        User::class,
                        fn(Builder $query) => $query->where('id', $user->id)
                    )
                    ->latest(),
                pageName: 'page_l',
                perPageName: 'per_page_l'
            )
        );
    }
}

