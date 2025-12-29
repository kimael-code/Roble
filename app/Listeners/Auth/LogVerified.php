<?php

namespace App\Listeners\Auth;

use App\Models\Monitoring\ActivityLog;
use App\Models\User;
use App\Support\UserMetadata;
use Illuminate\Auth\Events\Verified;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Queue\InteractsWithQueue;

class LogVerified
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
    public function handle(Verified $event): void
    {
        activity(ActivityLog::LOG_NAMES['auth'])
            ->event(ActivityLog::EVENT_NAMES['verified'])
            // @phpstan-ignore-next-line argument.type (User implementa MustVerifyEmail)
            ->causedBy($event->user)
            ->withProperties([
                // @phpstan-ignore-next-line argument.type (User implementa MustVerifyEmail)
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
            ->log('verificó su correo electrónico');
    }
}
