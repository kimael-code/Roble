<?php

use App\Models\Security\Permission;
use App\Models\Security\Role;
use App\Models\User;
use Illuminate\Support\Facades\Notification;
use Spatie\Activitylog\Facades\Activity;

/**
 * Tests de integración para gestión de permisos.
 *
 * Estos tests verifican la funcionalidad de gestión de permisos:
 * - Visualización de permisos
 * - Actualización de permisos
 * - Eliminación con restricciones (no eliminar si está asociado a roles/usuarios)
 * - Control de acceso
 */

beforeEach(function ()
{
    // Desactivar notificaciones y logging de actividad
    Notification::fake();
    Activity::disableLogging();

    // Desactivar observers para evitar errores en tests
    User::unsetEventDispatcher();
    Permission::unsetEventDispatcher();
    Role::unsetEventDispatcher();

    // Crear permisos base para gestión de permisos
    Permission::create(['name' => 'read any permission', 'description' => 'leer cualquier permiso', 'guard_name' => 'web']);
    Permission::create(['name' => 'read permission', 'description' => 'leer permiso', 'guard_name' => 'web']);
    Permission::create(['name' => 'create new permissions', 'description' => 'crear nuevos permisos', 'guard_name' => 'web']);
    Permission::create(['name' => 'update permissions', 'description' => 'actualizar permisos', 'guard_name' => 'web']);
    Permission::create(['name' => 'delete permissions', 'description' => 'eliminar permisos', 'guard_name' => 'web']);

    // Resetear caché de permisos de Spatie DESPUÉS de crearlos
    app()[\Spatie\Permission\PermissionRegistrar::class]->forgetCachedPermissions();

    // Crear rol de administrador con todos los permisos de gestión
    $this->adminRole = Role::create(['name' => 'Administrador de Permisos', 'description' => 'administrador para tests de permisos', 'guard_name' => 'web']);
    $this->adminRole->givePermissionTo(['read any permission', 'read permission', 'create new permissions', 'update permissions', 'delete permissions']);

    // Crear usuario administrador
    $this->adminUser = User::factory()->create();
    $this->adminUser->assignRole($this->adminRole);
});

test('un administrador puede ver la lista de permisos', function ()
{
    Permission::create(['name' => 'test permission 1', 'description' => 'permiso de prueba 1', 'guard_name' => 'web']);
    Permission::create(['name' => 'test permission 2', 'description' => 'permiso de prueba 2', 'guard_name' => 'web']);

    $response = $this->actingAs($this->adminUser)->get(route('permissions.index'));

    $response->assertStatus(200);
});

test('un administrador puede ver un permiso específico', function ()
{
    $permission = Permission::create(['name' => 'specific permission', 'description' => 'permiso específico', 'guard_name' => 'web']);

    // Resetear caché para que el permiso recién creado sea visible
    app()[\Spatie\Permission\PermissionRegistrar::class]->forgetCachedPermissions();

    $response = $this->actingAs($this->adminUser)->get(route('permissions.show', $permission));

    $response->assertStatus(200);
});

test('un administrador puede actualizar la descripción de un permiso', function ()
{
    $permission = Permission::create(['name' => 'updatable permission', 'description' => 'descripción original', 'guard_name' => 'web']);

    $response = $this->actingAs($this->adminUser)->put(route('permissions.update', $permission), [
        'name' => $permission->name,
        'description' => 'descripción actualizada',
        'guard_name' => 'web',
    ]);

    $response->assertRedirect(route('permissions.index'));
    $this->assertDatabaseHas('permissions', [
        'id' => $permission->id,
        'description' => 'descripción actualizada',
    ]);
});

test('un administrador puede eliminar un permiso sin asociaciones', function ()
{
    $permission = Permission::create(['name' => 'deletable permission', 'description' => 'será eliminado', 'guard_name' => 'web']);

    $response = $this->actingAs($this->adminUser)->delete(route('permissions.destroy', $permission));

    $response->assertRedirect(route('permissions.index'));
    $this->assertDatabaseMissing('permissions', ['id' => $permission->id]);
});

test('no se puede eliminar un permiso asignado a un rol', function ()
{
    $permission = Permission::create(['name' => 'role permission', 'description' => 'permiso de rol', 'guard_name' => 'web']);
    $role = Role::create(['name' => 'Rol con Permiso', 'description' => 'tiene permiso', 'guard_name' => 'web']);
    $role->givePermissionTo($permission);

    $response = $this->actingAs($this->adminUser)->delete(route('permissions.destroy', $permission));

    $response->assertForbidden();
    $this->assertDatabaseHas('permissions', ['id' => $permission->id]);
});

test('no se puede eliminar un permiso asignado directamente a un usuario', function ()
{
    $permission = Permission::create(['name' => 'user permission', 'description' => 'permiso de usuario', 'guard_name' => 'web']);
    $user = User::factory()->create();
    $user->givePermissionTo($permission);

    $response = $this->actingAs($this->adminUser)->delete(route('permissions.destroy', $permission));

    $response->assertForbidden();
    $this->assertDatabaseHas('permissions', ['id' => $permission->id]);
});

test('un usuario sin permisos no puede ver la lista de permisos', function ()
{
    $regularUser = User::factory()->create();

    $response = $this->actingAs($regularUser)->get(route('permissions.index'));

    $response->assertForbidden();
});

test('un usuario sin permisos no puede actualizar permisos', function ()
{
    $regularUser = User::factory()->create();
    $permission = Permission::create(['name' => 'test permission', 'description' => 'prueba', 'guard_name' => 'web']);

    $response = $this->actingAs($regularUser)->put(route('permissions.update', $permission), [
        'name' => $permission->name,
        'description' => 'modificado sin autorización',
        'guard_name' => 'web',
    ]);

    $response->assertForbidden();
});

test('un usuario sin permisos no puede eliminar permisos', function ()
{
    $regularUser = User::factory()->create();
    $permission = Permission::create(['name' => 'test permission', 'description' => 'prueba', 'guard_name' => 'web']);

    $response = $this->actingAs($regularUser)->delete(route('permissions.destroy', $permission));

    $response->assertForbidden();
    $this->assertDatabaseHas('permissions', ['id' => $permission->id]);
});

test('un administrador puede crear un nuevo permiso', function ()
{
    $response = $this->actingAs($this->adminUser)->post(route('permissions.store'), [
        'name' => 'brand new permission',
        'description' => 'nuevo permiso creado',
        'guard_name' => 'web',
    ]);

    $response->assertRedirect(route('permissions.index'));
    $this->assertDatabaseHas('permissions', [
        'name' => 'brand new permission',
        'description' => 'nuevo permiso creado',
    ]);
});
