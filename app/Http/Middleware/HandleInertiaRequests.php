<?php

namespace App\Http\Middleware;

use App\Support\Inspiring;
use Illuminate\Http\Request;
use Inertia\Middleware;

class HandleInertiaRequests extends Middleware
{
    /**
     * The root template that's loaded on the first page visit.
     *
     * @see https://inertiajs.com/server-side-setup#root-template
     *
     * @var string
     */
    protected $rootView = 'app';

    /**
     * Determines the current asset version.
     *
     * @see https://inertiajs.com/asset-versioning
     */
    public function version(Request $request): ?string
    {
        return parent::version($request);
    }

    /**
     * Define the props that are shared by default.
     *
     * @see https://inertiajs.com/shared-data
     *
     * @return array<string, mixed>
     */
    public function share(Request $request): array
    {
        [$message, $author] = str(Inspiring::quotes()->random())->explode('-');

        $authData = [
            'user' => null,
            'roles' => [],
            'menuPermissions' => [],
        ];

        if ($user = $request->user())
        {
            $authData['user'] = $user->only([
                'id', 'name', 'email', 'avatar', 'is_active',
                'created_at_human', 'updated_at_human'
            ]);

            $authData['roles'] = $user->getRoleNames()->toArray();

            $authData['menuPermissions'] = $user->getAllPermissions()
                ->where('set_menu', true)
                ->pluck('name')
                ->all();
        }

        return [
            ...parent::share($request),
            'name' => config('app.name'),
            'version' => config('app.version'),
            'quote' => ['message' => trim($message), 'author' => trim($author)],
            'auth' => $authData,
            'flash' => [
                'message' => fn() => $request->session()->get('message'),
                'manualActivation' => fn() => $request->session()->get('manualActivation'),
            ],
            'unreadNotifications' => fn() => $request->user()?->unreadNotifications()->latest()->lazy(5)->all(),
            'sidebarOpen' => !$request->hasCookie('sidebar_state') || $request->cookie('sidebar_state') === 'true',
        ];
    }
}
