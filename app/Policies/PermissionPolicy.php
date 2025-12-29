<?php

namespace App\Policies;

use App\Models\Security\Permission;
use App\Models\User;
use Illuminate\Auth\Access\Response;

class PermissionPolicy
{
    /**
     * Determine whether the user can view any models.
     */
    public function viewAny(User $user): bool|null
    {
        return $user->can('read any permission') ? true : null;
    }

    /**
     * Determine whether the user can view the model.
     */
    public function view(User $user, Permission $permission): bool|null
    {
        return $user->can('read permission') ? true : null;
    }

    /**
     * Determine whether the user can create models.
     */
    public function create(User $user): bool|null
    {
        return $user->can('create new permissions') ? true : null;
    }

    /**
     * Determine whether the user can update the model.
     */
    public function update(User $user, Permission $permission): bool|null
    {
        return $user->can('update permissions') ? true : null;
    }

    /**
     * Determine whether the user can delete the model.
     */
    public function delete(User $user, Permission $permission): Response|bool|null
    {
        if ($permission->roles->isNotEmpty())
        {
            return Response::deny('Existen roles que tienen asociado este permiso');
        }

        if ($permission->users->isNotEmpty())
        {
            return Response::deny('Existen usuarios que tienen otorgado este permiso');
        }

        return $user->can('delete permissions') ? true : null;
    }

    /**
     * Determine whether the user can restore the model.
     */
    public function restore(User $user, Permission $permission): bool
    {
        return false;
    }

    /**
     * Determine whether the user can permanently delete the model.
     */
    public function forceDelete(User $user, Permission $permission): bool
    {
        return false;
    }
}
