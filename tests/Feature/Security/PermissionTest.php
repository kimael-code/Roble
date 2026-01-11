<?php

use App\Models\Security\Permission;
use App\Models\Security\Role;
use App\Models\User;
use Illuminate\Support\Facades\Notification;
use Spatie\Activitylog\Facades\Activity;

/**
 * Integration tests para gestión de permisos.
 *
 * Estos tests verifican la funcionalidad de gestión de permisos:
 * - Visualización de permisos
 * - Actualización de permisos
 * - Eliminación con restricciones (no se elimina si está asociado a roles/usuarios)
 * - Control de acceso
 */

beforeEach(function ()
{
    // Desactivar notificaciones y logging de actividad
    Notification::fake();
    Activity::disableLogging();

    // Desactivar observadores para evitar errores en tests
    User::unsetEventDispatcher();
    Permission::unsetEventDispatcher();
    Role::unsetEventDispatcher();

    // Crear permisos base para gestión de permisos
    Permission::create(['name' => 'read any permission', 'description' => 'leer cualquier permiso', 'guard_name' => 'web']);
    Permission::create(['name' => 'read permission', 'description' => 'leer permiso', 'guard_name' => 'web']);
    Permission::create(['name' => 'create new permissions', 'description' => 'crear nuevos permisos', 'guard_name' => 'web']);
    Permission::create(['name' => 'update permissions', 'description' => 'actualizar permisos', 'guard_name' => 'web']);
    Permission::create(['name' => 'delete permissions', 'description' => 'eliminar permisos', 'guard_name' => 'web']);

    // Reset Spatie permission cache AFTER creating them
    app()[\Spatie\Permission\PermissionRegistrar::class]->forgetCachedPermissions();

    // Crear rol de administrador con todos los permisos de gestión
    $this->adminRole = Role::create(['name' => 'Administrador de Permisos', 'description' => 'administrador para tests de permisos', 'guard_name' => 'web']);
    $this->adminRole->givePermissionTo(['read any permission', 'read permission', 'create new permissions', 'update permissions', 'delete permissions']);

    // Crear usuario administrador
    $this->adminUser = User::factory()->create();
    $this->adminUser->assignRole($this->adminRole);
});

test('an administrator can view the list of permissions', function ()
{
    Permission::create(['name' => 'test permission 1', 'description' => 'permiso de prueba 1', 'guard_name' => 'web']);
    Permission::create(['name' => 'test permission 2', 'description' => 'permiso de prueba 2', 'guard_name' => 'web']);

    $response = $this->actingAs($this->adminUser)->get(route('permissions.index'));

    $response->assertStatus(200);
});

test('an administrator can view a specific permission', function ()
{
    $permission = Permission::create(['name' => 'specific permission', 'description' => 'permiso específico', 'guard_name' => 'web']);

    // Resetear caché para que el permiso recién creado sea visible
    app()[\Spatie\Permission\PermissionRegistrar::class]->forgetCachedPermissions();

    $response = $this->actingAs($this->adminUser)->get(route('permissions.show', $permission));

    $response->assertStatus(200);
});

test('an administrator can update the description of a permission', function ()
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

test('an administrator can delete a permission without associations', function ()
{
    $permission = Permission::create(['name' => 'deletable permission', 'description' => 'será eliminado', 'guard_name' => 'web']);

    $response = $this->actingAs($this->adminUser)->delete(route('permissions.destroy', $permission));

    $response->assertRedirect(route('permissions.index'));
    $this->assertDatabaseMissing('permissions', ['id' => $permission->id]);
});

test('a permission assigned to a role cannot be deleted', function ()
{
    $permission = Permission::create(['name' => 'role permission', 'description' => 'permiso de rol', 'guard_name' => 'web']);
    $role = Role::create(['name' => 'Rol con Permiso', 'description' => 'tiene permiso', 'guard_name' => 'web']);
    $role->givePermissionTo($permission);

    $response = $this->actingAs($this->adminUser)->delete(route('permissions.destroy', $permission));

    $response->assertForbidden();
    $this->assertDatabaseHas('permissions', ['id' => $permission->id]);
});

test('a permission directly assigned to a user cannot be deleted', function ()
{
    $permission = Permission::create(['name' => 'user permission', 'description' => 'permiso de usuario', 'guard_name' => 'web']);
    $user = User::factory()->create();
    $user->givePermissionTo($permission);

    $response = $this->actingAs($this->adminUser)->delete(route('permissions.destroy', $permission));

    $response->assertForbidden();
    $this->assertDatabaseHas('permissions', ['id' => $permission->id]);
});

test('a user without permissions cannot view the list of permissions', function ()
{
    $regularUser = User::factory()->create();

    $response = $this->actingAs($regularUser)->get(route('permissions.index'));

    $response->assertForbidden();
});

test('a user without permissions cannot update permissions', function ()
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

test('a user without permissions cannot delete permissions', function ()
{
    $regularUser = User::factory()->create();
    $permission = Permission::create(['name' => 'test permission', 'description' => 'prueba', 'guard_name' => 'web']);

    $response = $this->actingAs($regularUser)->delete(route('permissions.destroy', $permission));

    $response->assertForbidden();
    $this->assertDatabaseHas('permissions', ['id' => $permission->id]);
});

test('an administrator can create a new permission', function ()
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
