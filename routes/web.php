<?php

use App\Http\Controllers\{
    BatchDeletionController,
    BatchDisableController,
    BatchEnableController,
    DashboardController,
    Monitoring\ActivityLogController,
    Monitoring\LogFileController,
    Monitoring\MaintenanceController,
    NotificationController,
    Organization\OrganizationalUnitController,
    Organization\OrganizationController,
    Security\PermissionController,
    Security\RoleController,
    Security\UserActivationController,
    Security\UserController,
    SuInstallerController,
};
use App\Http\Middleware\ValidateSuperusers;
use App\Models\User;
use Illuminate\Foundation\Http\Middleware\HandlePrecognitiveRequests;
use Illuminate\Support\Facades\Route;
use Inertia\Inertia;
use Laravel\Fortify\Features;

Route::get('/', fn() => Inertia::render('Welcome', [
    'suExists' => User::with('roles')->get()->filter(
        fn($user) => $user->roles->where('id', 1)->toArray()
    )->count() > 0,
    'canRegister' => Features::enabled(Features::registration()),
]))->name('home');

Route::controller(SuInstallerController::class)->prefix('su-installer')->group(function ()
{
    Route::get('/', 'index')->middleware(ValidateSuperusers::class)->name('su-installer.index');
    Route::get('/wizard', 'wizard')->middleware(ValidateSuperusers::class)->name('su-installer.wizard');
    Route::post('/register', 'store')->middleware([HandlePrecognitiveRequests::class])->name('su-installer.store');
});

Route::get('activate/{user}', [UserActivationController::class, 'show'])
    ->name('user.activate')
    ->middleware('signed');
Route::post('activate/{user}', [UserActivationController::class, 'update'])
    ->name('user.activate.update')
    ->middleware('signed');

Route::middleware(['auth', 'verified', 'activated'])->group(function ()
{
    Route::get('dashboard', [DashboardController::class, 'index'])->name('dashboard');
    Route::post('batch-activation/{resource}', BatchEnableController::class)->name('batch-activation');
    Route::post('batch-deactivation/{resource}', BatchDisableController::class)->name('batch-deactivation');
    Route::post('batch-deletion/{resource}', BatchDeletionController::class)->name('batch-deletion');

    Route::controller(NotificationController::class)->group(function ()
    {
        Route::get('notifications', 'index')->name('notifications.index');
        Route::put('notifications/{notification}/mark-as-read', 'markAsRead')->name('notifications.mark-as-read');
        Route::put('notifications/{notification}/mark-read', 'markAsReadOnly')->name('notifications.mark-read');
        Route::post('notifications', 'markAllAsRead')->name('notifications.mark-all-as-read');
        Route::delete('notifications/{notification}', 'destroy')->name('notifications.destroy');
        Route::delete('notifications', 'destroyAll')->name('notifications.destroy-all');
    });

    Route::controller(LogFileController::class)->group(function ()
    {
        Route::get('log-files', 'index')->middleware('can:read any system log')->name('log-files.index');
        Route::get('log-files/{file}', 'export')->middleware('can:export system logs')->name('log-files.export');
        Route::delete('log-files/{file}', 'delete')->middleware('can:delete system logs')->name('log-files.destroy');
    });

    Route::controller(MaintenanceController::class)->group(function ()
    {
        Route::get('maintenance-mode', 'index')
            ->name('maintenance.index')
            ->middleware('can:manage maintenance mode');
        Route::post('maintenance-mode/toggle', 'toggle')
            ->name('maintenance.toggle')
            ->middleware(['can:manage maintenance mode', HandlePrecognitiveRequests::class]);
    });

    Route::resource('activity-logs', ActivityLogController::class)->only(['index', 'show',]);

    Route::post('users/{user}/reset-password', [UserController::class, 'resetPassword'])
        ->middleware(['throttle:users.reset-password'])
        ->name('users.reset-password');
    Route::post('users/{user}/resend-activation', [UserController::class, 'resendActivation'])
        ->name('users.resend-activation');
    Route::post('users/{user}/manually-activate', [UserController::class, 'manuallyActivate'])
        ->name('users.manually-activate');
    Route::put('users/{user}/restore', [UserController::class, 'restore'])
        ->withTrashed()
        ->name('users.restore');
    Route::put('users/{user}/enable', [UserController::class, 'enable'])
        ->withTrashed()
        ->name('users.enable');
    Route::put('users/{user}/disable', [UserController::class, 'disable'])
        ->withTrashed()
        ->name('users.disable');
    Route::delete('users/{user}/force-destroy', [UserController::class, 'forceDestroy'])
        ->withTrashed()
        ->name('users.force-destroy');
    Route::resource('users', UserController::class)
        ->withTrashed(['show', 'edit', 'update', 'destroy'])
        ->middleware(HandlePrecognitiveRequests::class);

    Route::resources([
        'permissions' => PermissionController::class,
        'roles' => RoleController::class,
        'organizations' => OrganizationController::class,
        'organizational-units' => OrganizationalUnitController::class,
    ], [
        'middleware' => [HandlePrecognitiveRequests::class],
    ]);
});

require __DIR__ . '/settings.php';
require __DIR__ . '/exporters.php';
