<?php

namespace App\Providers;

use App\Contracts\EmployeeRepository as EmployeeContract;
use App\Models\User;
use App\Repositories\JsonEmployeeRepository;
use Illuminate\Cache\RateLimiting\Limit;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        $this->app->bind(EmployeeContract::class, JsonEmployeeRepository::class);
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        // ningún usuario, ni siquiera superusuarios, pueden pasar por alto
        // las políticas definidas. Por lo que la verificación del rol
        // Superusuario se ejecuta luego de haberse ejecutado las políticas
        Gate::after(fn(User $user) => $user->hasRole(1));

        RateLimiter::for('users.reset-password', function (Request $request)
        {
            return Limit::perMinute(2)->by($request->user()->id ?: $request->ip());
        });
    }
}
