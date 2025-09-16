<?php

namespace App\Listeners\Auth;

use App\Models\User;
use App\Notifications\ActionHandledOnModel;
use Illuminate\Auth\Events\Registered;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Queue\InteractsWithQueue;

class LogRegistered
{
    /**
     * Create the event listener.
     */
    public function __construct()
    {
        //
    }

    /**
     * Handle the event.
     */
    public function handle(Registered $event): void
    {
        activity(__('Authentication'))
            ->event('created')
            ->causedBy($event->user)
            ->withProperty('request', [
                'ip_address' => request()->ip(),
                'user_agent' => request()->header('user-agent'),
                'user_agent_lang' => request()->header('accept-language'),
                'referer' => request()->header('referer'),
                'http_method' => request()->method(),
                'request_url' => request()->fullUrl(),
            ])
            ->withProperty('causer', User::with('person')->find($event->user->id)->toArray())
            ->log(__('registered themselves'));

        session()->flash('message', [
            'message' => "{$event->user->name}",
            'title' => __('SAVED!'),
            'type' => 'success',
        ]);

        $users = User::permission('create new users')->get()->filter(
            fn(User $user) => $user?->id != $event->user->id
        )->all();

        foreach ($users as $user)
        {
            $user->notify(new ActionHandledOnModel(
                $event->user,
                [
                    'id' => $event->user->id,
                    'type' => __('user'),
                    'name' => "{$event->user->name}",
                    'timestamp' => $event->user->created_at,
                ],
                'created',
                ['routeName' => 'users', 'routeParam' => 'user']
            ));
        }
    }
}
