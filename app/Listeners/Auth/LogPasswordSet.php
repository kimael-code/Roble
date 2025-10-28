<?php

namespace App\Listeners\Auth;

use App\Events\Auth\PasswordSet;
use App\Models\Monitoring\ActivityLog;
use App\Models\User;
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
                'causer', User::with('person')->find($event->user->id)->toArray(),
                'request' => [
                    'ip_address' => request()->ip(),
                    'user_agent' => request()->header('user-agent'),
                    'user_agent_lang' => request()->header('accept-language'),
                    'referer' => request()->header('referer'),
                    'http_method' => request()->method(),
                    'request_url' => request()->fullUrl(),
                ],
            ])
            ->log(__('set their own password'));
    }
}
