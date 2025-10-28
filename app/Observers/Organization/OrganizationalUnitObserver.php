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
            'message' => $organizationalUnit->name,
            'title' => __('SAVED!'),
            'type'  => 'success',
        ]);

        $users = User::permission('create new organizational units')->get()->filter(
            fn (User $user) => $user->id != auth()->user()->id
        )->all();

        foreach ($users as $user)
        {
            $user->notify(new ActionHandledOnModel(
                auth()->user(),
                [
                    'id' => $organizationalUnit->id,
                    'type' => __('organizational unit'),
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
            'message' => $organizationalUnit->name,
            'title' => __('SAVED!'),
            'type'  => 'success',
        ]);

        $users = User::permission('update organizational units')->get()->filter(
            fn (User $user) => $user->id != auth()->user()->id
        )->all();

        foreach ($users as $user)
        {
            $user->notify(new ActionHandledOnModel(
                auth()->user(),
                [
                    'id' => $organizationalUnit->id,
                    'type' => __('organizational unit'),
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
            'message' => $organizationalUnit->name,
            'title' => __('DELETED!'),
            'type'  => 'danger',
        ]);

        $users = User::permission('delete organizational units')->get()->filter(
            fn (User $user) => $user->id != auth()->user()->id
        )->all();

        foreach ($users as $user)
        {
            $user->notify(new ActionHandledOnModel(
                auth()->user(),
                [
                    'type' => __('organizational unit'),
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
