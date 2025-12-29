<?php

namespace App\Observers\Organization;

use App\Models\Organization\OrganizationalUnit;
use App\Models\User;
use App\Notifications\ActionHandledOnModel;

class OrganizationalUnitObserver
{
    /**
     * Handle the OrganizationalUnit "created" event.
     */
    public function created(OrganizationalUnit $organizationalUnit): void
    {
        session()->flash('message', [
            'content' => $organizationalUnit->name,
            'title' => '¡GUARDADO!',
            'type' => 'success',
        ]);

        $usersToNotify = User::where(function ($query)
        {
            $query->permission('create new organizational units')
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
                    'id' => $organizationalUnit->id,
                    'type' => 'unidad administrativa',
                    'name' => $organizationalUnit->name,
                    'timestamp' => $organizationalUnit->created_at,
                ],
                'created',
                ['routeName' => 'organizational-units', 'routeParam' => 'organizational_unit']
            ));
        }
    }

    /**
     * Handle the OrganizationalUnit "updated" event.
     */
    public function updated(OrganizationalUnit $organizationalUnit): void
    {
        session()->flash('message', [
            'content' => $organizationalUnit->name,
            'title' => '¡GUARDADO!',
            'type' => 'success',
        ]);

        $usersToNotify = User::where(function ($query)
        {
            $query->permission('update organizational units')
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
                    'id' => $organizationalUnit->id,
                    'type' => 'unidad administrativa',
                    'name' => $organizationalUnit->name,
                    'timestamp' => $organizationalUnit->updated_at,
                ],
                'updated',
                ['routeName' => 'organizational-units', 'routeParam' => 'organizational_unit']
            ));
        }
    }

    /**
     * Handle the OrganizationalUnit "deleted" event.
     */
    public function deleted(OrganizationalUnit $organizationalUnit): void
    {
        session()->flash('message', [
            'content' => $organizationalUnit->name,
            'title' => '¡ELIMINADO!',
            'type' => 'danger',
        ]);

        $usersToNotify = User::where(function ($query)
        {
            $query->permission('delete organizational units')
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
                    'type' => 'unidad administrativa',
                    'name' => $organizationalUnit->name,
                    'timestamp' => now(),
                ],
                'deleted',
                ['routeName' => 'organizational-units', 'routeParam' => 'organizational_unit']
            ));
        }
    }

    /**
     * Handle the OrganizationalUnit "restored" event.
     */
    public function restored(OrganizationalUnit $organizationalUnit): void
    {
        //
    }

    /**
     * Handle the OrganizationalUnit "force deleted" event.
     */
    public function forceDeleted(OrganizationalUnit $organizationalUnit): void
    {
        //
    }
}
