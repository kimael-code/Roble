<?php

use App\Models\Security\Permission;
use App\Models\Security\Role;
use App\Models\User;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Facades\Storage;
use Spatie\Activitylog\Facades\Activity;

/**
 * Tests de integración para archivos de log del sistema.
 *
 * Estos tests verifican:
 * - Visualización de archivos de log
 * - Exportación de logs
 * - Eliminación de logs
 * - Control de acceso
 */

beforeEach(function ()
{
    // Desactivar notificaciones y logging de actividad
    Notification::fake();
    Activity::disableLogging();
    // Note: No usar Storage::fake('logs') porque Logfile usa storage_path() y Storage::disk() mezclados

    // Desactivar observers para evitar errores en tests
    User::unsetEventDispatcher();

    // Crear permisos base
    Permission::create(['name' => 'read any system log', 'description' => 'leer cualquier log del sistema', 'guard_name' => 'web']);
    Permission::create(['name' => 'export system logs', 'description' => 'exportar logs del sistema', 'guard_name' => 'web']);
    Permission::create(['name' => 'delete system logs', 'description' => 'eliminar logs del sistema', 'guard_name' => 'web']);

    // Resetear caché de permisos de Spatie DESPUÉS de crearlos
    app()[\Spatie\Permission\PermissionRegistrar::class]->forgetCachedPermissions();

    // Crear rol de administrador
    $this->adminRole = Role::create(['name' => 'Administrador de Logs', 'description' => 'admin de logs', 'guard_name' => 'web']);
    $this->adminRole->givePermissionTo(['read any system log', 'export system logs', 'delete system logs']);

    // Crear usuario administrador
    $this->adminUser = User::factory()->create(['is_active' => true]);
    $this->adminUser->assignRole($this->adminRole);
});

test('usuario autorizado puede ver la lista de archivos de log', function ()
{
    $response = $this->actingAs($this->adminUser)->get(route('log-files.index'));

    $response->assertStatus(200);
});

test('usuario sin permiso de lectura no puede ver logs', function ()
{
    $regularUser = User::factory()->create();

    $response = $this->actingAs($regularUser)->get(route('log-files.index'));

    $response->assertForbidden();
});

test('usuario no autenticado es redirigido al login', function ()
{
    $response = $this->get(route('log-files.index'));

    $response->assertRedirect(route('login'));
});
