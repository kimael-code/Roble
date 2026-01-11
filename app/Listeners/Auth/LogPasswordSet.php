<?php

namespace App\Listeners\Auth;

use App\Events\Auth\PasswordSet;
use App\Models\Monitoring\ActivityLog;
use App\Models\User;
use App\Support\UserMetadata;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Queue\InteractsWithQueue;
use Spatie\Activitylog\Contracts\Activity;

class LogPasswordSet
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
    public function handle(PasswordSet $event): void
    {
        activity(ActivityLog::LOG_NAMES['auth'])
            ->event(ActivityLog::EVENT_NAMES['password'])
            ->performedOn($event->user)
            ->causedBy($event->user)
            ->withProperties([
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
            ->log('estableció su propia contraseña');
    }
}
