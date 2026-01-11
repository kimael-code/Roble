<?php

namespace App\Observers\Security;

use App\Models\Security\Permission;
use App\Models\User;
use App\Notifications\ActionHandledOnModel;

class PermissionObserver
{
    /**
     * Handle the Permission "created" event.
     */
    public function created(Permission $permission): void
    {
        session()->flash('message', [
            'content' => "{$permission->name} ({$permission->description})",
            'title' => '¡GUARDADO!',
            'type' => 'success',
        ]);

        $usersToNotify = User::where(function ($query)
        {
            $query->permission('create new permissions')
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
                    'id' => $permission->id,
                    'type' => 'permiso',
                    'name' => "{$permission->name} ({$permission->description})",
                    'timestamp' => $permission->created_at,
                ],
                'created',
                ['routeName' => 'permissions', 'routeParam' => 'permission']
            ));
        }
    }

    /**
     * Handle the Permission "updated" event.
     */
    public function updated(Permission $permission): void
    {
        session()->flash('message', [
            'content' => "{$permission->name} ({$permission->description})",
            'title' => '¡GUARDADO!',
            'type' => 'success',
        ]);

        $usersToNotify = User::where(function ($query)
        {
            $query->permission('update permissions')
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
                    'id' => $permission->id,
                    'type' => 'permiso',
                    'name' => "{$permission->name} ({$permission->description})",
                    'timestamp' => $permission->updated_at,
                ],
                'updated',
                ['routeName' => 'permissions', 'routeParam' => 'permission']
            ));
        }
    }

    /**
     * Handle the Permission "deleted" event.
     */
    public function deleted(Permission $permission): void
    {
        session()->flash('message', [
            'content' => "{$permission->name} ({$permission->description})",
            'title' => '¡ELIMINADO!',
            'type' => 'danger',
        ]);

        $usersToNotify = User::where(function ($query)
        {
            $query->permission('delete permissions')
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
                    'type' => 'permiso',
                    'name' => "{$permission->name} ({$permission->description})",
                    'timestamp' => now(),
                ],
                'deleted',
                ['routeName' => 'permissions', 'routeParam' => 'permission']
            ));
        }
    }

    /**
     * Handle the Permission "restored" event.
     */
    public function restored(Permission $permission): void
    {
        //
    }

    /**
     * Handle the Permission "force deleted" event.
     */
    public function forceDeleted(Permission $permission): void
    {
        //
    }
}
