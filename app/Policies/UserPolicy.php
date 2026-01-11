<?php

namespace App\Policies;

use App\Models\User;
use Illuminate\Auth\Access\Response;

class UserPolicy
{
    /**
     * Determine whether the user can view any models.
     */
    public function viewAny(User $user): bool|null
    {
        return $user->can('read any user') ? true : null;
    }

    /**
     * Determine whether the user can view the model.
     */
    public function view(User $user, User $model): bool|null
    {
        return $user->can('read user') ? true : null;
    }

    /**
     * Determine whether the user can create models.
     */
    public function create(User $user): bool|null
    {
        return $user->can('create new users') ? true : null;
    }

    /**
     * Determine whether the user can update the model.
     */
    public function update(User $user, User $model): Response|bool|null
    {
        if ($model->hasRole(1) && !$user->hasRole(1))
        {
            return Response::deny('Un Superusuario puede ser modificado sólo por otro Superusuario.');
        }

        return $user->can('update users') ? true : null;
    }

    /**
     * Determine whether the user can delete the model.
     */
    public function delete(User $user, User $model): Response|bool|null
    {
        if ($model->hasRole(1) && !$user->hasRole(1))
        {
            return Response::deny('Un Superusuario puede ser eliminado sólo por otro Superusuario.');
        }

        $creatorUsersCount = User::with('permissions')->get()->filter(
            fn($user) => $user->permissions->where('name', 'create new users')->toArray()
        )->count();

        if ($creatorUsersCount === 1 && $model->can('create new users'))
        {
            return Response::deny("{$model->name} es el único usuario con el permiso para crear nuevos usuarios. Asigne este permiso a otro usuario antes de eliminarlo.");
        }

        if ($user->is($model))
        {
            return Response::deny('Usted no puede eliminar su propio usuario.');
        }

        return $user->can('delete users') ? true : null;
    }

    /**
     * Determine whether the user can restore the model.
     */
    public function restore(User $user, User $model): bool|null
    {
        return $user->can('restore users') ? true : null;
    }

    /**
     * Determine whether the user can permanently delete the model.
     */
    public function forceDelete(User $user, User $model): Response|bool|null
    {
        if ($model->hasRole(1) && !$user->hasRole(1))
        {
            return Response::deny('Un Superusuario puede ser eliminado sólo por otro Superusuario.');
        }

        $creatorUsersCount = User::with('permissions')->get()->filter(
            fn($user) => $user->permissions->where('name', 'create new users')->toArray()
        )->count();

        if ($creatorUsersCount === 1 && $model->can('create new users'))
        {
            return Response::deny("{$model->name} es el único usuario con el permiso para crear nuevos usuarios. Asigne este permiso a otro usuario antes de eliminarlo.");
        }

        if ($user->is($model))
        {
            return Response::deny('Usted no puede eliminar su propio usuario.');
        }

        return $user->can('force delete users') ? true : null;
    }

    /**
     * Determine whether the user can enable the model.
     */
    public function enable(User $user, User $model): bool|null
    {
        return $user->can('enable users') ? true : null;
    }

    /**
     * Determine whether the user can disable the model.
     */
    public function disable(User $user, User $model): Response|bool|null
    {
        $creatorUsersCount = User::with('permissions')->get()->filter(
            fn($user) => $user->permissions->where('name', 'create new users')->toArray()
        )->count();

        if ($creatorUsersCount === 1 && $model->can('create new users'))
        {
            return Response::deny("{$model->name} es el único usuario con el permiso para crear nuevos usuarios. Asigne este permiso a otro usuario antes de desactivarlo.");
        }

        if ($user->is($model))
        {
            return Response::deny('Usted no puede desactivar su propio usuario.');
        }

        return $user->can('disable users') ? true : null;
    }

    public function resetPassword(User $user, User $model): Response|bool|null
    {
        if (!$model->is_active || $model->disabled_at || $model->deleted_at)
        {
            return Response::deny('No se puede restablecer la contraseña de un usuario inactivo.');
        }

        return $user->can('reset user passwords') ? true : null;
    }

    /**
     * Determina si el usuario puede reenviar el enlace de activación.
     *
     * Solo aplicable a usuarios que aún no han activado su cuenta.
     */
    public function resendActivation(User $user, User $model): bool|null
    {
        // Solo permitir para usuarios no activos, no deshabilitados y no eliminados
        if ($model->is_active || $model->disabled_at !== null || $model->deleted_at !== null)
        {
            return null;
        }

        return $user->can('reset user passwords') ? true : null;
    }

    /**
     * Determina si el usuario puede activar manualmente.
     *
     * Solo aplicable a usuarios que aún no han activado su cuenta.
     */
    public function manuallyActivate(User $user, User $model): bool|null
    {
        // Solo permitir para usuarios no activos, no deshabilitados y no eliminados
        if ($model->is_active || $model->disabled_at !== null || $model->deleted_at !== null)
        {
            return null;
        }

        return $user->can('enable users') ? true : null;
    }
}
