<?php

namespace App\Listeners\Auth;

use App\Models\User;
use Illuminate\Auth\Events\Logout;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Queue\InteractsWithQueue;

class LogLogout
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
    public function handle(Logout $event): void
    {
        activity(__('Authentication'))
            ->event('logged_out')
            ->causedBy($event->user)
            ->withProperty('request', [
                'ip_address' => request()->ip(),
                'user_agent' => request()->header('user-agent'),
                'user_agent_lang' => request()->header('accept-language'),
                'referer' => request()->header('referer'),
                'http_method' => request()->method(),
                'request_url' => request()->fullUrl(),
                'guard_name' => $event->guard,
            ])
            ->withProperty('causer', User::with('person')->find($event->user->id)->toArray())
            ->log(__('logged out'));
    }
}
