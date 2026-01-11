<?php

use App\Models\Security\Permission;
use App\Models\Security\Role;
use App\Models\User;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Notification;
use Spatie\Activitylog\Facades\Activity;

/**
 * Tests de integración para el modo mantenimiento.
 *
 * Estos tests verifican:
 * - Visualización del estado del modo mantenimiento
 * - Activación/desactivación del modo mantenimiento
 * - Control de acceso
 */

/**
 * Helper para crear un usuario administrador con permisos de mantenimiento.
 */
function createMaintenanceAdmin(): User
{
    // Desactivar observadores para evitar errores en tests
    User::unsetEventDispatcher();

    // Crear permiso de modo mantenimiento si no existe
    $permission = Permission::firstOrCreate(
        ['name' => 'manage maintenance mode', 'guard_name' => 'web'],
        ['description' => 'gestionar modo mantenimiento']
    );

    // Reset Spatie permission cache AFTER creating them
    app()[\Spatie\Permission\PermissionRegistrar::class]->forgetCachedPermissions();

    // Crear rol de administrador
    $adminRole = Role::firstOrCreate(
        ['name' => 'Administrador del Sistema', 'guard_name' => 'web'],
        ['description' => 'admin sistema']
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

    // Asegurar que el modo mantenimiento está desactivado
    if (app()->isDownForMaintenance())
    {
        Artisan::call('up');
    }
});

afterEach(function ()
{
    // Asegurar que el modo mantenimiento está desactivado después de cada test
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
