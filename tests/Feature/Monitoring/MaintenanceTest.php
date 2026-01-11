<?php

use App\Models\Security\Permission;
use App\Models\Security\Role;
use App\Models\User;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Notification;
use Spatie\Activitylog\Facades\Activity;

/**
 * Integration tests for maintenance mode.
 *
 * These tests verify:
 * - Viewing maintenance mode status
 * - Activating/deactivating maintenance mode
 * - Access control
 */

/**
 * Helper to create admin user with maintenance permissions.
 */
function createMaintenanceAdmin(): User
{
    // Disable observers to avoid errors in tests
    User::unsetEventDispatcher();

    // Create maintenance mode permission if it doesn't exist
    $permission = Permission::firstOrCreate(
        ['name' => 'manage maintenance mode', 'guard_name' => 'web'],
        ['description' => 'manage maintenance mode']
    );

    // Reset Spatie permission cache AFTER creating them
    app()[\Spatie\Permission\PermissionRegistrar::class]->forgetCachedPermissions();

    // Create admin role
    $adminRole = Role::firstOrCreate(
        ['name' => 'System Administrator', 'guard_name' => 'web'],
        ['description' => 'system admin']
    );
    $adminRole->givePermissionTo(['manage maintenance mode']);

    // Create admin user
    $adminUser = User::factory()->create(['is_active' => true]);
    $adminUser->assignRole($adminRole);

    return $adminUser;
}

beforeEach(function ()
{
    // Disable notifications and activity logging
    Notification::fake();
    Activity::disableLogging();

    // Ensure maintenance mode is disabled
    if (app()->isDownForMaintenance())
    {
        Artisan::call('up');
    }
});

afterEach(function ()
{
    // Ensure maintenance mode is disabled after each test
    if (app()->isDownForMaintenance())
    {
        Artisan::call('up');
    }
});

test('authorized user can view maintenance mode status', function ()
{
    $adminUser = createMaintenanceAdmin();

    $response = $this->actingAs($adminUser)->get(route('maintenance.index'));

    $response->assertStatus(200);
});

test('authorized user can activate maintenance mode', function ()
{
    $adminUser = createMaintenanceAdmin();

    $response = $this->actingAs($adminUser)->post(route('maintenance.toggle'));

    $response->assertRedirect();
});

test('user without permissions cannot view maintenance mode', function ()
{
    User::unsetEventDispatcher();
    $user = User::factory()->create(['is_active' => true]);

    $response = $this->actingAs($user)->get(route('maintenance.index'));

    $response->assertForbidden();
});

test('user without permissions cannot manage maintenance mode', function ()
{
    User::unsetEventDispatcher();
    $user = User::factory()->create(['is_active' => true]);

    $response = $this->actingAs($user)->post(route('maintenance.toggle'));

    $response->assertForbidden();
});

test('unauthenticated user is redirected to login', function ()
{
    $response = $this->get(route('maintenance.index'));

    $response->assertRedirect(route('login'));
});
