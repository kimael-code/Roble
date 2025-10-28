<?php

namespace App\Listeners\Auth;

use App\Models\Monitoring\ActivityLog;
use App\Models\User;
use App\Notifications\UserSelfRegistration;
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
        activity(ActivityLog::LOG_NAMES['auth'])
            ->event(ActivityLog::EVENT_NAMES['created'])
            ->causedBy($event->user)
            ->performedOn($event->user)
            ->withProperties([
                'attributes' => $event->user->toArray(),
                'causer' => User::with('person')->find($event->user->id)->toArray(),
                'request' => [
                    'ip_address' => request()->ip(),
                    'user_agent' => request()->header('user-agent'),
                    'user_agent_lang' => request()->header('accept-language'),
                    'referer' => request()->header('referer'),
                    'http_method' => request()->method(),
                    'request_url' => request()->fullUrl(),
                ],
            ])
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
            $user->notify(new UserSelfRegistration(
                $event->user,
                [
                    'id' => $event->user->id,
                    'type' => 'usuario',
                    'name' => "{$event->user->name}",
                    'timestamp' => $event->user->created_at,
                ],
            ));
        }
    }
}
