<?php

namespace App\Observers\Organization;

use App\Models\Organization\Organization;
use App\Models\User;
use App\Notifications\ActionHandledOnModel;

class OrganizationObserver
{
    /**
     * Handle the Organization "created" event.
     */
    public function created(Organization $organization): void
    {
        session()->flash('message', [
            'content' => "{$organization->rif} ({$organization->name})",
            'title' => '¡GUARDADO!',
            'type' => 'success',
        ]);

        $usersToNotify = User::where(function ($query)
        {
            $query->permission('create new organizations')
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
                    'id' => $organization->id,
                    'type' => 'ente',
                    'name' => "{$organization->rif} ({$organization->name})",
                    'timestamp' => $organization->created_at,
                ],
                'created',
                ['routeName' => 'organizations', 'routeParam' => 'organization']
            ));
        }
    }

    /**
     * Handle the Organization "updated" event.
     */
    public function updated(Organization $organization): void
    {
        session()->flash('message', [
            'content' => "{$organization->rif} ({$organization->name})",
            'title' => '¡GUARDADO!',
            'type' => 'success',
        ]);

        $usersToNotify = User::where(function ($query)
        {
            $query->permission('update organizations')
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
                    'id' => $organization->id,
                    'type' => 'ente',
                    'name' => "{$organization->rif} ({$organization->name})",
                    'timestamp' => $organization->updated_at,
                ],
                'updated',
                ['routeName' => 'organizations', 'routeParam' => 'organization']
            ));
        }
    }

    /**
     * Handle the Organization "deleted" event.
     */
    public function deleted(Organization $organization): void
    {
        session()->flash('message', [
            'content' => "{$organization->rif} ({$organization->name})",
            'title' => '¡ELIMINADO!',
            'type' => 'danger',
        ]);

        $usersToNotify = User::where(function ($query)
        {
            $query->permission('delete organizations')
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
                    'type' => 'ente',
                    'name' => "{$organization->rif} ({$organization->name})",
                    'timestamp' => now(),
                ],
                'deleted',
                ['routeName' => 'organizations', 'routeParam' => 'organization']
            ));
        }
    }

    /**
     * Handle the Organization "restored" event.
     */
    public function restored(Organization $organization): void
    {
        //
    }

    /**
     * Handle the Organization "force deleted" event.
     */
    public function forceDeleted(Organization $organization): void
    {
        //
    }
}
