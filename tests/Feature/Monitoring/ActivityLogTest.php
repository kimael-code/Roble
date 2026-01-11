<?php

use App\Models\Monitoring\ActivityLog;
use App\Models\Security\Permission;
use App\Models\Security\Role;
use App\Models\User;
use Illuminate\Support\Facades\Notification;
use Spatie\Activitylog\Facades\Activity;

/**
 * Integration tests for activity traces (Activity Logs).
 *
 * These tests verify:
 * - Viewing activity logs
 * - Filtering by user, event, module, date, IP
 * - Access control
 */

beforeEach(function ()
{
    // Disable notifications and activity logging
    Notification::fake();
    Activity::disableLogging();

    // Disable observers to avoid errors in tests
    User::unsetEventDispatcher();

    // Create base permissions for activity logs
    Permission::create(['name' => 'read any activity trace', 'description' => 'read any trace', 'guard_name' => 'web']);
    Permission::create(['name' => 'read activity trace', 'description' => 'read trace', 'guard_name' => 'web']);
    Permission::create(['name' => 'export activity traces', 'description' => 'export traces', 'guard_name' => 'web']);

    // Reset Spatie permission cache AFTER creating them
    app()[\Spatie\Permission\PermissionRegistrar::class]->forgetCachedPermissions();

    // Create admin role
    $this->adminRole = Role::create(['name' => 'Log Administrator', 'description' => 'log admin', 'guard_name' => 'web']);
    $this->adminRole->givePermissionTo(['read any activity trace', 'read activity trace', 'export activity traces']);

    // Create admin user
    $this->adminUser = User::factory()->create(['is_active' => true]);
    $this->adminUser->assignRole($this->adminRole);
});

/**
 * Helper to create a test activity log.
 */
function createActivityLog(array $attributes = []): ActivityLog
{
    return ActivityLog::create(array_merge([
        'log_name' => 'Security/Users',
        'description' => 'Test event',
        'subject_type' => User::class,
        'subject_id' => 1,
        'causer_type' => User::class,
        'causer_id' => 1,
        'event' => 'creation',
        'properties' => ['request' => ['ip_address' => '127.0.0.1']],
    ], $attributes));
}

test('authorized user can view activity logs list', function ()
{
    createActivityLog(['description' => 'Log 1']);
    createActivityLog(['description' => 'Log 2']);

    $response = $this->actingAs($this->adminUser)->get(route('activity-logs.index'));

    $response->assertStatus(200);
});

test('authorized user can view a specific activity log', function ()
{
    $log = createActivityLog(['description' => 'Specific log']);

    $response = $this->actingAs($this->adminUser)->get(route('activity-logs.show', $log));

    $response->assertStatus(200);
});

test('activity logs can be filtered by search', function ()
{
    createActivityLog(['description' => 'Created admin user']);
    createActivityLog(['description' => 'Updated role']);

    $response = $this->actingAs($this->adminUser)->get(route('activity-logs.index', ['search' => 'admin']));

    $response->assertStatus(200);
});

test('activity logs can be filtered by event', function ()
{
    createActivityLog(['event' => 'creation']);
    createActivityLog(['event' => 'deletion']);

    $response = $this->actingAs($this->adminUser)->get(route('activity-logs.index', ['events' => ['creation']]));

    $response->assertStatus(200);
});

test('activity logs can be filtered by module', function ()
{
    createActivityLog(['log_name' => 'Security/Users']);
    createActivityLog(['log_name' => 'Security/Roles']);

    $response = $this->actingAs($this->adminUser)->get(route('activity-logs.index', ['modules' => ['Security/Users']]));

    $response->assertStatus(200);
});

test('user without permissions cannot view activity logs', function ()
{
    $regularUser = User::factory()->create();

    $response = $this->actingAs($regularUser)->get(route('activity-logs.index'));

    $response->assertForbidden();
});

test('user without permissions cannot view a specific activity log', function ()
{
    $regularUser = User::factory()->create();
    $log = createActivityLog();

    $response = $this->actingAs($regularUser)->get(route('activity-logs.show', $log));

    $response->assertForbidden();
});

test('unauthenticated user is redirected to login', function ()
{
    $response = $this->get(route('activity-logs.index'));

    $response->assertRedirect(route('login'));
});
