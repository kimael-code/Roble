<?php

use App\Http\Controllers\{
    DashboardController,
    InstallerController,
    Monitoring\ActivityLogController,
    Monitoring\LogFileController,
    Monitoring\MaintenanceController,
    NotificationController,
    Organization\OrganizationalUnitController,
    Organization\OrganizationController,
    Security\PermissionController,
    Security\RoleController,
    Security\UserController,
};
use App\Http\Middleware\ValidateSuperusers;
use App\Models\User;
use Illuminate\Foundation\Http\Middleware\HandlePrecognitiveRequests;
use Illuminate\Support\Facades\Route;
use Inertia\Inertia;

Route::get(
    '/',
    fn() => Inertia::render('Welcome', [
        'suExiste' => User::with('roles')->get()->filter(
            fn($user) => $user->roles->where('id', 1)->toArray()
        )->count() > 0,
    ])
)->name('home');

Route::controller(InstallerController::class)->prefix('su-installer')->group(function ()
{
    Route::get('/', 'index')->middleware(ValidateSuperusers::class)->name('su-installer.index');
    Route::get('/wizard', 'wizard')->middleware(ValidateSuperusers::class)->name('su-installer.wizard');
    Route::post('/register', 'store')->middleware([HandlePrecognitiveRequests::class])->name('su-installer.register');
});

Route::middleware(['auth', 'verified', 'password.set',])->group(function ()
{
    Route::get('dashboard', DashboardController::class)->name('dashboard');

    Route::post('users/batch-enable', [UserController::class, 'batchEnable'])->name('users.batchEnable');
    Route::post('users/batch-disable', [UserController::class, 'batchDisable'])->name('users.batchDisable');

    Route::delete('users/batch-destroy', [UserController::class, 'batchDestroy'])->name('users.batchDestroy');
    Route::delete('permissions/batch-destroy', [PermissionController::class, 'batchDestroy'])->name('permissions.batchDestroy');
    Route::delete('roles/batch-destroy', [RoleController::class, 'batchDestroy'])->name('roles.batchDestroy');
    Route::delete('organizations/batch-destroy', [OrganizationController::class, 'batchDestroy'])->name('organizations.batchDestroy');
    Route::delete('organizational-units/batch-destroy', [OrganizationalUnitController::class, 'batchDestroy'])->name('organizational-units.batchDestroy');

    Route::controller(NotificationController::class)->group(function ()
    {
        Route::get('notifications', 'index')->name('notifications.index');
        Route::put('notifications/{notification}/mark-as-read', 'markAsRead')->name('notifications.mark-as-read');
        Route::post('notifications', 'markAllAsRead')->name('notifications.mark-all-as-read');
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
            ->middleware('can: manage maintenance mode');
        Route::post('maintenance-mode/toggle', 'toggle')
            ->name('maintenance.toggle')
            ->middleware(['can: manage maintenance mode', HandlePrecognitiveRequests::class]);
    });

    Route::resource('activity-logs', ActivityLogController::class)->only(['index', 'show',]);

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
require __DIR__ . '/auth.php';
require __DIR__ . '/exporters.php';
