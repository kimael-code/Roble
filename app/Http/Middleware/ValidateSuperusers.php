<?php

namespace App\Http\Middleware;

use App\Models\User;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class ValidateSuperusers
{
    /**
     * Handle an incoming request.
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        $superusersCount = User::with('roles')->active()->get()->filter(
            fn($user) => $user->roles->where('id', 1)->toArray()
        )->count();

        if ($superusersCount > 0)
        {
            abort(403, __('auth.superuser_already_exists'));
        }

        return $next($request);
    }
}
