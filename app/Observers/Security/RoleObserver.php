<?php

namespace App\Observers\Security;

use App\Models\Security\Role;
use App\Models\User;
use App\Notifications\ActionHandledOnModel;

class RoleObserver
{
    /**
     * Handle the Role "created" event.
     */
    public function created(Role $role): void
    {
        session()->flash('message', [
            'content' => "{$role->name} ({$role->description})",
            'title' => '¡GUARDADO!',
            'type' => 'success',
        ]);

        $usersToNotify = User::where(function ($query)
        {
            $query->permission('create new roles')
                ->orWhereHas('roles', function ($q)
                {
                    $q->where('name', 'Superusuario');
                });
        })
            ->where('id', '!=', auth()->id())
            ->whereNull('disabled_at')
            ->get();

        foreach ($usersToNotify as $userToNotify)
        {
            $userToNotify->notify(new ActionHandledOnModel(
                auth()->user(),
                [
                    'id' => $role->id,
                    'type' => 'rol',
                    'name' => "{$role->name}",
                    'timestamp' => $role->created_at,
                ],
                'created',
                ['routeName' => 'roles', 'routeParam' => 'role']
            ));
        }
    }

    /**
     * Handle the Role "updated" event.
     */
    public function updated(Role $role): void
    {
        session()->flash('message', [
            'content' => "{$role->name} ({$role->description})",
            'title' => '¡GUARDADO!',
            'type' => 'success',
        ]);

        $usersToNotify = User::where(function ($query)
        {
            $query->permission('update roles')
                ->orWhereHas('roles', function ($q)
                {
                    $q->where('name', 'Superusuario');
                });
        })
            ->where('id', '!=', auth()->id())
            ->whereNull('disabled_at')
            ->get();

        foreach ($usersToNotify as $userToNotify)
        {
            $userToNotify->notify(new ActionHandledOnModel(
                auth()->user(),
                [
                    'id' => $role->id,
                    'type' => 'rol',
                    'name' => "{$role->name}",
                    'timestamp' => $role->updated_at,
                ],
                'updated',
                ['routeName' => 'roles', 'routeParam' => 'role']
            ));
        }
    }

    /**
     * Handle the Role "deleted" event.
     */
    public function deleted(Role $role): void
    {
        session()->flash('message', [
            'content' => "{$role->name} ({$role->description})",
            'title' => '¡ELIMINADO!',
            'type' => 'danger',
        ]);

        $usersToNotify = User::where(function ($query)
        {
            $query->permission('delete roles')
                ->orWhereHas('roles', function ($q)
                {
                    $q->where('name', 'Superusuario');
                });
        })
            ->where('id', '!=', auth()->id())
            ->whereNull('disabled_at')
            ->get();

        foreach ($usersToNotify as $userToNotify)
        {
            $userToNotify->notify(new ActionHandledOnModel(
                auth()->user(),
                [
                    'type' => 'rol',
                    'name' => "{$role->name}",
                    'timestamp' => now(),
                ],
                'deleted',
                ['routeName' => 'roles', 'routeParam' => 'role']
            ));
        }
    }

    /**
     * Handle the Role "restored" event.
     */
    public function restored(Role $role): void
    {
        //
    }

    /**
     * Handle the Role "force deleted" event.
     */
    public function forceDeleted(Role $role): void
    {
        //
    }
}
