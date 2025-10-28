<?php

namespace App\Actions\Security;

use App\Models\Monitoring\ActivityLog;
use App\Models\User;
use App\Notifications\ActionHandledOnModel;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\Hash;

class EnableUser
{
    public static function handle(User $user): void
    {
        $user->disabled_at = null;
        $user->deleted_at = null;
        $user->is_password_set = false;
        $user->password = $user?->person?->id_card ? Hash::make($user->person->id_card) : Hash::make($user->name);

        $user->save();

        activity(ActivityLog::LOG_NAMES['users'])
            ->causedBy(auth()->user())
            ->performedOn($user)
            ->event(ActivityLog::EVENT_NAMES['enabled'])
            ->withProperties([
                'attributes' => Arr::except($user->getChanges(), ['password']),
                'old' => Arr::except($user->getPrevious(), ['password']),
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
            ->log(__('enabled user [:modelName] [:modelEmail]', [
                'modelName' => $user->name,
                'modelEmail' => $user->email,
            ]));

        session()->flash('message', [
            'message' => "{$user->name}",
            'title' => __('ENABLED!'),
            'type'  => 'success',
        ]);

        $users = User::permission('enable users')->get()->filter(
            fn(User $user) => $user->id != auth()->user()->id
        )->all();

        foreach ($users as $user)
        {
            $user->notify(new ActionHandledOnModel(
                auth()->user(),
                [
                    'type' => __('user'),
                    'name' => "{$user->name}",
                    'timestamp' => now(),
                ],
                'enabled',
                ['routeName' => 'users', 'routeParam' => 'user']
            ));
        }
    }
}
