<?php

use App\Models\Monitoring\ActivityLog;
use App\Models\Security\Permission;
use App\Models\Security\Role;
use App\Models\User;
use Illuminate\Support\Facades\Notification;
use Spatie\Activitylog\Facades\Activity;

/**
 * Tests de integración para trazas de actividad (Activity Logs).
 *
 * Estos tests verifican:
 * - Visualización de trazas de actividad
 * - Filtrado por usuario, evento, módulo, fecha, IP
 * - Control de acceso
 */

beforeEach(function ()
{
    // Disable notifications and activity logging
    Notification::fake();
    Activity::disableLogging();

    // Desactivar observadores de modelos para evitar errores en tests
    User::unsetEventDispatcher();

    // Crear permisos base para trazas de actividad
    Permission::create(['name' => 'read any activity trace', 'description' => 'leer cualquier traza', 'guard_name' => 'web']);
    Permission::create(['name' => 'read activity trace', 'description' => 'leer traza', 'guard_name' => 'web']);
    Permission::create(['name' => 'export activity traces', 'description' => 'exportar trazas', 'guard_name' => 'web']);

    // Reset Spatie permission cache AFTER creating them
    app()[\Spatie\Permission\PermissionRegistrar::class]->forgetCachedPermissions();

    // Crear rol de administrador
    $this->adminRole = Role::create(['name' => 'Administrador de Logs', 'description' => 'admin de logs', 'guard_name' => 'web']);
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
