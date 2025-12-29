<?php

namespace App\Policies;

use App\Models\Organization\Organization;
use App\Models\User;
use Illuminate\Auth\Access\Response;

class OrganizationPolicy
{
    /**
     * Determine whether the user can view any models.
     */
    public function viewAny(User $user): bool|null
    {
        return $user->can('read any organization') ? true : null;
    }

    /**
     * Determine whether the user can view the model.
     */
    public function view(User $user, Organization $organization): bool|null
    {
        $userBelongsToOrganization = false;

        foreach ($organization->organizationalUnits as $ou)
        {
            if ($user->activeOrganizationalUnits->contains($ou))
            {
                $userBelongsToOrganization = true;
            }
        }

        if ($user->can('read any organization'))
        {
            return true;
        }

        if ($user->can('read organization') && $userBelongsToOrganization)
        {
            return true;
        }

        return null;
    }

    /**
     * Determine whether the user can create models.
     */
    public function create(User $user): bool|null
    {
        return $user->can('create new organizations') ? true : null;
    }

    /**
     * Determine whether the user can update the model.
     */
    public function update(User $user, Organization $organization): bool|null
    {
        return $user->can('update organizations') ? true : null;
    }

    /**
     * Determine whether the user can delete the model.
     */
    public function delete(User $user, Organization $organization): Response|bool|null
    {
        $ThereIsOtherActiveOrganization = (bool) Organization::where('id', '<>', $organization->id)->active()->count();

        if (!$ThereIsOtherActiveOrganization)
        {
            return Response::deny('No es posible eliminar el único ente activo');
        }

        if ($organization->organizationalUnits->isNotEmpty())
        {
            return Response::deny('El ente tiene unidades administrativas asociadas');
        }

        return $user->can('delete organizations') ? true : null;
    }

    /**
     * Determine whether the user can restore the model.
     */
    public function restore(User $user, Organization $organization): bool
    {
        return false;
    }

    /**
     * Determine whether the user can permanently delete the model.
     */
    public function forceDelete(User $user, Organization $organization): bool
    {
        return false;
    }
}
