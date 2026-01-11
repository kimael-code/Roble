<?php

namespace App\Listeners\Auth;

use App\Models\Monitoring\ActivityLog;
use App\Models\User;
use App\Notifications\UserSelfRegistration;
use App\Support\UserMetadata;
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
                // @phpstan-ignore-next-line method.notFound (User implementa toArray)
                'attributes' => $event->user->toArray(),
                // @phpstan-ignore-next-line argument.type (User implementa Authenticatable)
                'causer' => UserMetadata::capture($event->user),
                'request' => [
                    'ip_address' => request()->ip(),
                    'user_agent' => request()->header('user-agent'),
                    'user_agent_lang' => request()->header('accept-language'),
                    'referer' => request()->header('referer'),
                    'http_method' => request()->method(),
                    'request_url' => request()->fullUrl(),
                ],
            ])
            ->log('se registró a sí mismo como usuario');

            session()->flash('message', [
            // @phpstan-ignore-next-line property.notFound (User tiene propiedad name)
            'content' => $event->user->name,
            'title' => '¡Bienvenido!',
            'type' => 'success',
        ]);

        $users = User::permission('create new users')->get()->filter(
            // @phpstan-ignore-next-line property.notFound (User tiene propiedad id)
            fn(User $user) => $user?->id != $event->user->id
        )->all();

        foreach ($users as $user)
        {
            $user->notify(new UserSelfRegistration(
                $event->user,
                [
                    // @phpstan-ignore-next-line property.notFound (User tiene id, name, created_at)
                    'id' => $event->user->id,
                    'type' => 'usuario',
                    // @phpstan-ignore-next-line property.notFound (User tiene id, name, created_at)
                    'name' => "{$event->user->name}",
                    // @phpstan-ignore-next-line property.notFound (User tiene id, name, created_at)
                    'timestamp' => $event->user->created_at,
                ],
            ));
        }
    }
}
