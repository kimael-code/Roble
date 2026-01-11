<?php

namespace App\Actions\Security;

use App\Models\Monitoring\ActivityLog;
use App\Models\User;
use App\Notifications\ActionHandledOnModel;
use App\Support\ActivityLogger;

class DisableUser
{
    public function __construct(
        private ActivityLogger $logger
    ) {
    }

    public function __invoke(User $user): void
    {
        $user->disabled_at = now();
        $user->save();

        $this->logger->logDisabled(
            ActivityLog::LOG_NAMES['users'],
            $user,
            'desactivó usuario [:subject.name] [:subject.email]'
        );

        session()->flash('message', [
            'content' => "{$user->name}",
            'title' => '¡DESACTIVADO!',
            'type' => 'warning',
        ]);

        $this->notifyUsers($user, 'disabled');
    }

    private function notifyUsers(User $disabledUser, string $action): void
    {
        $users = User::permission('disable users')->get()->filter(
            fn(User $user) => $user->id != auth()->user()->id
        )->all();

        foreach ($users as $user)
        {
            $user->notify(new ActionHandledOnModel(
                auth()->user(),
                [
                    'type' => 'usuario',
                    'name' => "{$disabledUser->name}",
                    'timestamp' => now(),
                ],
                $action,
                ['routeName' => 'users', 'routeParam' => 'user']
            ));
        }
    }
}
