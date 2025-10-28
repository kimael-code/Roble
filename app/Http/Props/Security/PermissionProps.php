<?php

namespace App\Http\Props\Security;

use App\Http\Resources\Monitoring\ActivityLogCollection;
use App\Http\Resources\Security\PermissionCollection;
use App\Http\Resources\Security\RoleCollection;
use App\Http\Resources\Security\UserCollection;
use App\Models\Monitoring\ActivityLog;
use App\Models\Security\Permission;
use App\Models\Security\Role;
use App\Models\User;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Request;
use Inertia\Inertia;

class PermissionProps
{
    private static function getPermissions(): array
    {
        return [
            'create' => Auth::user()->can('create new permissions'),
            'read' => Auth::user()->can('read permission'),
            'update' => Auth::user()->can('update permissions'),
            'delete' => Auth::user()->can('delete permissions'),
            'export' => Auth::user()->can('export permissions'),
        ];
    }

    public static function index(): array
    {
        $filtersOnly = Request::only([
            'search',
            'sort_by',
            'roles',
            'users',
            'set_menu',
        ]);
        $filtersAll = Request::all([
            'search',
            'sort_by',
            'roles',
            'users',
            'set_menu',
        ]);
        $perPage = Request::input('per_page', 10);

        return [
            'can' => self::getPermissions(),
            'filters' => $filtersAll,
            'roles' => Inertia::optional(fn() => Role::select(['id', 'name'])->get()),
            'users' => Inertia::optional(fn() => User::select(['id', 'email'])->withTrashed()->get()),
            'permissions' => fn() => new PermissionCollection(
                Permission::filter($filtersOnly)
                    ->paginate($perPage)
                    ->withQueryString()
            ),
        ];
    }

    public static function show(Permission $permission): array
    {
        return [
            'can'        => Arr::except(self::getPermissions(), 'read'),
            'filters'    => Request::only(['search', 'name']),
            'permission' => $permission,
            'roles'      => fn() => new RoleCollection(
                $permission->roles()->filter(Request::only(['search', 'name']))
                    ->latest()
                    ->paginate(10, pageName: 'page_r')
            ),
            'users'      => fn() => new UserCollection(
                User::filter(Request::only(['search', 'name']))
                    ->permission($permission->name)
                    ->latest()
                    ->paginate(10, pageName: 'page_u')
            ),
            'logs' => fn() => new ActivityLogCollection(
                ActivityLog::filter(Request::only(['search']))
                    ->whereHasMorph(
                        'subject',
                        Permission::class,
                        fn(Builder $query) => $query->where('id', $permission->id)
                    )
                    ->latest()
                    ->paginate(10, pageName: 'page_l')
                    ->withQueryString()
            ),
        ];
    }

    public static function edit(Permission $permission): array
    {
        return [
            'permission' => $permission,
        ];
    }
}

