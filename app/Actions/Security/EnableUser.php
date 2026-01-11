<?php

namespace App\Actions\Security;

use App\Models\Monitoring\ActivityLog;
use App\Models\User;
use App\Notifications\ActionHandledOnModel;
use App\Support\ActivityLogger;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\Hash;

class EnableUser
{
    public function __construct(
        private ActivityLogger $logger
    ) {
    }

    public function __invoke(User $user): void
    {
        $user->disabled_at = null;
        $user->deleted_at = null;
        $user->password = $user?->person?->id_card
            ? Hash::make($user->person->id_card)
            : Hash::make($user->name);

        $user->save();

        $this->logger->logEnabled(
            ActivityLog::LOG_NAMES['users'],
            $user,
            'activó usuario [:subject.name] [:subject.email]',
            [
                'attributes' => Arr::except($user->getChanges(), ['password']),
                'old' => Arr::except($user->getOriginal(), ['password']),
            ]
        );

        session()->flash('message', [
            'content' => "{$user->name}",
            'title' => '¡ACTIVADO!',
            'type' => 'success',
        ]);

        $this->notifyUsers($user, 'enabled');
    }

    private function notifyUsers(User $enabledUser, string $action): void
    {
        $users = User::permission('enable users')->get()->filter(
            fn(User $user) => $user->id != auth()->user()->id
        )->all();

        foreach ($users as $user)
        {
            $user->notify(new ActionHandledOnModel(
                auth()->user(),
                [
                    'type' => 'usuario',
                    'name' => "{$enabledUser->name}",
                    'timestamp' => now(),
                ],
                $action,
                ['routeName' => 'users', 'routeParam' => 'user']
            ));
        }
    }
}
