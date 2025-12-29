<?php

namespace App\Listeners\Auth;

use App\Models\Monitoring\ActivityLog;
use App\Models\User;
use App\Support\UserMetadata;
use Illuminate\Auth\Events\OtherDeviceLogout;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Queue\InteractsWithQueue;

class LogOtherDeviceLogout
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
    public function handle(OtherDeviceLogout $event): void
    {
        activity(ActivityLog::LOG_NAMES['auth'])
            ->event(ActivityLog::EVENT_NAMES['logged_out'])
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
                    'guard_name' => $event->guard,
                ],
            ])
            ->log('cerró sesión en otro dispositivo');
    }
}
