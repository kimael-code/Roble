<?php

use App\Models\Security\Permission;
use App\Models\Security\Role;
use App\Models\User;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Notification;
use Spatie\Activitylog\Facades\Activity;

/**
 * Tests de integración para modo mantenimiento.
 *
 * Estos tests verifican:
 * - Visualización del estado del modo mantenimiento
 * - Activación/desactivación del modo
 * - Control de acceso
 */

/**
 * Helper para crear usuario administrador con permisos de mantenimiento.
 */
function createMaintenanceAdmin(): User
{
    // Desactivar observers para evitar errores en tests
    User::unsetEventDispatcher();

    // Crear permiso para modo mantenimiento si no existe
    $permission = Permission::firstOrCreate(
        ['name' => 'manage maintenance mode', 'guard_name' => 'web'],
        ['description' => 'gestionar modo mantenimiento']
    );

    // Resetear caché de permisos de Spatie DESPUÉS de crearlos
    app()[\Spatie\Permission\PermissionRegistrar::class]->forgetCachedPermissions();

    // Crear rol de administrador
    $adminRole = Role::firstOrCreate(
        ['name' => 'Administrador de Sistema', 'guard_name' => 'web'],
        ['description' => 'admin de sistema']
    );
    $adminRole->givePermissionTo(['manage maintenance mode']);

    // Crear usuario administrador
    $adminUser = User::factory()->create(['is_active' => true]);
    $adminUser->assignRole($adminRole);

    return $adminUser;
}

beforeEach(function ()
{
    // Desactivar notificaciones y logging de actividad
    Notification::fake();
    Activity::disableLogging();

    // Asegurar que el modo mantenimiento esté desactivado
    if (app()->isDownForMaintenance())
    {
        Artisan::call('up');
    }
});

afterEach(function ()
{
    // Asegurar que el modo mantenimiento esté desactivado después de cada test
    if (app()->isDownForMaintenance())
    {
        Artisan::call('up');
    }
});

test('usuario autorizado puede ver el estado del modo mantenimiento', function ()
{
    $adminUser = createMaintenanceAdmin();

    $response = $this->actingAs($adminUser)->get(route('maintenance.index'));

    $response->assertStatus(200);
});

test('usuario autorizado puede activar el modo mantenimiento', function ()
{
    $adminUser = createMaintenanceAdmin();

    $response = $this->actingAs($adminUser)->post(route('maintenance.toggle'));

    $response->assertRedirect();
});

test('usuario sin permisos no puede ver el modo mantenimiento', function ()
{
    User::unsetEventDispatcher();
    $user = User::factory()->create(['is_active' => true]);

    $response = $this->actingAs($user)->get(route('maintenance.index'));

    $response->assertForbidden();
});

test('usuario sin permisos no puede gestionar el modo mantenimiento', function ()
{
    User::unsetEventDispatcher();
    $user = User::factory()->create(['is_active' => true]);

    $response = $this->actingAs($user)->post(route('maintenance.toggle'));

    $response->assertForbidden();
});

test('usuario no autenticado es redirigido al login', function ()
{
    $response = $this->get(route('maintenance.index'));

    $response->assertRedirect(route('login'));
});
