<?php

namespace App\Actions\Security;

use App\Models\Monitoring\ActivityLog;
use App\Models\User;
use App\Notifications\ActionHandledOnModel;

class DisableUser
{
    public static function handle(User $user): void
    {
        $user->disabled_at = now();
        $user->save();

        activity(ActivityLog::LOG_NAMES['users'])
            ->causedBy(auth()->user())
            ->performedOn($user)
            ->event(ActivityLog::EVENT_NAMES['disabled'])
            ->withProperties([
                'attributes' => $user->getChanges(),
                'old' => $user->getPrevious(),
                'request' => [
                    'ip_address' => request()->ip(),
                    'user_agent' => request()->header('user-agent'),
                    'user_agent_lang' => request()->header('accept-language'),
                    'referer' => request()->header('referer'),
                    'http_method' => request()->method(),
                    'request_url' => request()->fullUrl(),
                ],
                'causer' => User::with('person')->find(auth()->user()->id)->toArray(),
            ])
            ->log(__('disabled user [:modelName] [:modelEmail]', [
                'modelName' => $user->name,
                'modelEmail' => $user->email,
            ]));

        session()->flash('message', [
            'message' => "{$user->name}",
            'title' => __('DISABLED!'),
            'type'  => 'warning',
        ]);

        $users = User::permission('disable users')->get()->filter(
            fn (User $user) => $user->id != auth()->user()->id
        )->all();

        foreach ($users as $user)
        {
            $user->notify(new ActionHandledOnModel(
                auth()->user(),
                [
                    'type' => __('user'),
                    'name' => "({$user->name})",
                    'timestamp' => now(),
                ],
                'disabled',
                ['routeName' => 'users', 'routeParam' => 'user']
            ));
        }
    }
}
