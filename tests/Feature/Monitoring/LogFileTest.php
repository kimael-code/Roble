<?php

use App\Models\Security\Permission;
use App\Models\Security\Role;
use App\Models\User;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Facades\Storage;
use Spatie\Activitylog\Facades\Activity;

/**
 * Integration tests for system log files.
 *
 * These tests verify:
 * - Viewing log files
 * - Exporting logs
 * - Deleting logs
 * - Access control
 */

beforeEach(function ()
{
    // Disable notifications and activity logging
    Notification::fake();
    Activity::disableLogging();
    // Note: Don't use Storage::fake('logs') because Logfile uses storage_path() and Storage::disk() mixed

    // Disable observers to avoid errors in tests
    User::unsetEventDispatcher();

    // Create base permissions
    Permission::create(['name' => 'read any system log', 'description' => 'read any system log', 'guard_name' => 'web']);
    Permission::create(['name' => 'export system logs', 'description' => 'export system logs', 'guard_name' => 'web']);
    Permission::create(['name' => 'delete system logs', 'description' => 'delete system logs', 'guard_name' => 'web']);

    // Reset Spatie permission cache AFTER creating them
    app()[\Spatie\Permission\PermissionRegistrar::class]->forgetCachedPermissions();

    // Create admin role
    $this->adminRole = Role::create(['name' => 'Log Administrator', 'description' => 'log admin', 'guard_name' => 'web']);
    $this->adminRole->givePermissionTo(['read any system log', 'export system logs', 'delete system logs']);

    // Create admin user
    $this->adminUser = User::factory()->create(['is_active' => true]);
    $this->adminUser->assignRole($this->adminRole);
});

test('authorized user can view log files list', function ()
{
    $response = $this->actingAs($this->adminUser)->get(route('log-files.index'));

    $response->assertStatus(200);
});

test('user without read permission cannot view logs', function ()
{
    $regularUser = User::factory()->create();

    $response = $this->actingAs($regularUser)->get(route('log-files.index'));

    $response->assertForbidden();
});

test('unauthenticated user is redirected to login', function ()
{
    $response = $this->get(route('log-files.index'));

    $response->assertRedirect(route('login'));
});
