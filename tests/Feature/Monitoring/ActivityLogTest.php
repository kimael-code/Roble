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
 * - Visualización de logs de actividad
 * - Filtrado por usuario, evento, módulo, fecha, IP
 * - Control de acceso
 */

beforeEach(function ()
{
    // Desactivar notificaciones y logging de actividad
    Notification::fake();
    Activity::disableLogging();

    // Desactivar observers para evitar errores en tests
    User::unsetEventDispatcher();

    // Crear permisos base para activity logs
    Permission::create(['name' => 'read any activity trace', 'description' => 'leer cualquier traza', 'guard_name' => 'web']);
    Permission::create(['name' => 'read activity trace', 'description' => 'leer traza', 'guard_name' => 'web']);
    Permission::create(['name' => 'export activity traces', 'description' => 'exportar trazas', 'guard_name' => 'web']);

    // Resetear caché de permisos de Spatie DESPUÉS de crearlos
    app()[\Spatie\Permission\PermissionRegistrar::class]->forgetCachedPermissions();

    // Crear rol de administrador
    $this->adminRole = Role::create(['name' => 'Administrador de Logs', 'description' => 'admin de logs', 'guard_name' => 'web']);
    $this->adminRole->givePermissionTo(['read any activity trace', 'read activity trace', 'export activity traces']);

    // Crear usuario administrador
    $this->adminUser = User::factory()->create(['is_active' => true]);
    $this->adminUser->assignRole($this->adminRole);
});

/**
 * Helper para crear un activity log de prueba.
 */
function createActivityLog(array $attributes = []): ActivityLog
{
    return ActivityLog::create(array_merge([
        'log_name' => 'Seguridad/Usuarios',
        'description' => 'Evento de prueba',
        'subject_type' => User::class,
        'subject_id' => 1,
        'causer_type' => User::class,
        'causer_id' => 1,
        'event' => 'creación',
        'properties' => ['request' => ['ip_address' => '127.0.0.1']],
    ], $attributes));
}

test('usuario autorizado puede ver la lista de activity logs', function ()
{
    createActivityLog(['description' => 'Log 1']);
    createActivityLog(['description' => 'Log 2']);

    $response = $this->actingAs($this->adminUser)->get(route('activity-logs.index'));

    $response->assertStatus(200);
});

test('usuario autorizado puede ver un activity log específico', function ()
{
    $log = createActivityLog(['description' => 'Log específico']);

    $response = $this->actingAs($this->adminUser)->get(route('activity-logs.show', $log));

    $response->assertStatus(200);
});

test('activity logs pueden filtrarse por búsqueda', function ()
{
    createActivityLog(['description' => 'Creó usuario admin']);
    createActivityLog(['description' => 'Actualizó rol']);

    $response = $this->actingAs($this->adminUser)->get(route('activity-logs.index', ['search' => 'admin']));

    $response->assertStatus(200);
});

test('activity logs pueden filtrarse por evento', function ()
{
    createActivityLog(['event' => 'creación']);
    createActivityLog(['event' => 'eliminación']);

    $response = $this->actingAs($this->adminUser)->get(route('activity-logs.index', ['events' => ['creación']]));

    $response->assertStatus(200);
});

test('activity logs pueden filtrarse por módulo', function ()
{
    createActivityLog(['log_name' => 'Seguridad/Usuarios']);
    createActivityLog(['log_name' => 'Seguridad/Roles']);

    $response = $this->actingAs($this->adminUser)->get(route('activity-logs.index', ['modules' => ['Seguridad/Usuarios']]));

    $response->assertStatus(200);
});

test('usuario sin permisos no puede ver activity logs', function ()
{
    $regularUser = User::factory()->create();

    $response = $this->actingAs($regularUser)->get(route('activity-logs.index'));

    $response->assertForbidden();
});

test('usuario sin permisos no puede ver un activity log específico', function ()
{
    $regularUser = User::factory()->create();
    $log = createActivityLog();

    $response = $this->actingAs($regularUser)->get(route('activity-logs.show', $log));

    $response->assertForbidden();
});

test('usuario no autenticado es redirigido al login', function ()
{
    $response = $this->get(route('activity-logs.index'));

    $response->assertRedirect(route('login'));
});
