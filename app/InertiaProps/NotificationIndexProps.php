<?php

namespace App\InertiaProps;

use App\Models\User;
use Illuminate\Contracts\Auth\Authenticatable;
use Inertia\Inertia;

/**
 * InertiaProps para la vista Index de Notifications.
 */
class NotificationIndexProps
{
    public function toArray(Authenticatable|User $user): array
    {
        $page = request()->input('page', 1);
        $perPage = request()->input('per_page', 10);
        $notifications = $user->notifications()->paginate($perPage, page: $page);

        return [
            'notifications' => Inertia::merge(fn() => $notifications->items()),
            'pagination' => $notifications->toArray(),
        ];
    }
}
