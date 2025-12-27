<?php

namespace App\Providers;

use App\Actions\Fortify\CreateNewUser;
use App\Actions\Fortify\ResetUserPassword;
use App\Models\User;
use Illuminate\Cache\RateLimiting\Limit;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\ServiceProvider;
use Illuminate\Support\Str;
use Inertia\Inertia;
use Laravel\Fortify\Features;
use Laravel\Fortify\Fortify;

class FortifyServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        //
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        $this->configureActions();
        $this->configureRateLimiting();

        // Saltar configuración de vistas si estamos en consola (pero no en tests)
        if ($this->app->runningInConsole() && !$this->app->runningUnitTests())
        {
            return;
        }

        $this->configureViews();
    }

    /**
     * Verifica si el sistema está listo para autenticación.
     *
     * El sistema está listo cuando existe al menos un usuario
     * con el rol Superusuario asignado.
     */
    private function isSystemReadyForAuth(): bool
    {
        // Si las tablas no existen, el sistema no está listo
        if (!Schema::hasTable('roles') || !Schema::hasTable('users'))
        {
            return false;
        }

        // Verificar que existe el rol Superusuario
        $superuserRoleExists = \App\Models\Security\Role::where('name', 'Superusuario')->exists();

        if (!$superuserRoleExists)
        {
            return false;
        }

        // Verificar que existe al menos un usuario con el rol
        return User::role('Superusuario')->exists();
    }

    /**
     * Configure Fortify actions.
     */
    private function configureActions(): void
    {
        Fortify::resetUserPasswordsUsing(ResetUserPassword::class);
        Fortify::createUsersUsing(CreateNewUser::class);
    }

    /**
     * Configure Fortify views with system readiness check.
     *
     * Each view verifies at request time (not boot time) that the system
     * is ready for authentication. This allows proper testing.
     */
    private function configureViews(): void
    {
        Fortify::loginView(function (Request $request)
        {
            $this->ensureSystemReady('login');

            return Inertia::render('auth/Login', [
                'canResetPassword' => Features::enabled(Features::resetPasswords()),
                'canRegister' => Features::enabled(Features::registration()),
                'status' => $request->session()->get('status'),
            ]);
        });

        Fortify::resetPasswordView(function (Request $request)
        {
            $this->ensureSystemReady('password.reset');

            return Inertia::render('auth/ResetPassword', [
                'email' => $request->email,
                'token' => $request->route('token'),
            ]);
        });

        Fortify::requestPasswordResetLinkView(function (Request $request)
        {
            $this->ensureSystemReady('password.request');

            return Inertia::render('auth/ForgotPassword', [
                'status' => $request->session()->get('status'),
            ]);
        });

        Fortify::verifyEmailView(function (Request $request)
        {
            $this->ensureSystemReady('verification.notice');

            return Inertia::render('auth/VerifyEmail', [
                'status' => $request->session()->get('status'),
            ]);
        });

        Fortify::registerView(function ()
        {
            $this->ensureSystemReady('register');

            return Inertia::render('auth/Register');
        });

        Fortify::twoFactorChallengeView(function ()
        {
            $this->ensureSystemReady('two-factor.challenge');

            return Inertia::render('auth/TwoFactorChallenge');
        });

        Fortify::confirmPasswordView(function ()
        {
            $this->ensureSystemReady('password.confirm');

            return Inertia::render('auth/ConfirmPassword');
        });
    }

    /**
     * Verifica que el sistema está listo y aborta si no lo está.
     */
    private function ensureSystemReady(string $routeName): void
    {
        if (!$this->isSystemReadyForAuth())
        {
            Log::warning("Intento de acceso a la ruta '{$routeName}' bloqueado: El sistema no está listo (no existe Superusuario).");
            abort(403, 'Esta funcionalidad no está disponible en este momento.');
        }
    }

    /**
     * Configure rate limiting.
     */
    private function configureRateLimiting(): void
    {
        RateLimiter::for('two-factor', function (Request $request)
        {
            return Limit::perMinute(5)->by($request->session()->get('login.id'));
        });

        RateLimiter::for('login', function (Request $request)
        {
            $throttleKey = Str::transliterate(Str::lower($request->input(Fortify::username())) . '|' . $request->ip());

            return Limit::perMinute(5)->by($throttleKey);
        });
    }
}
