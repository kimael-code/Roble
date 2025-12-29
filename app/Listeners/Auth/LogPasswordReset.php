<?php

namespace App\Listeners\Auth;

use App\Models\Monitoring\ActivityLog;
use App\Models\User;
use App\Support\UserMetadata;
use Illuminate\Auth\Events\PasswordReset;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Queue\InteractsWithQueue;

class LogPasswordReset
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
    public function handle(PasswordReset $event): void
    {
        activity(ActivityLog::LOG_NAMES['auth'])
            ->event(ActivityLog::EVENT_NAMES['password'])
            ->causedBy($event->user)
            ->performedOn($event->user)
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
            ->log('restableció su propia contraseña');
    }
}
