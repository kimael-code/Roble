<?php

use App\Models\Organization\Organization;
use App\Models\Organization\OrganizationalUnit;
use App\Models\Security\Permission;
use App\Models\Security\Role;
use App\Models\User;
use Illuminate\Support\Facades\Notification;
use Spatie\Activitylog\Facades\Activity;

/**
 * Tests de integración para gestión de roles.
 *
 * Estos tests verifican la funcionalidad de gestión de roles:
 * - CRUD de roles
 * - Asignación y desasignación de permisos
 * - Restricciones del rol Superusuario
 * - Validaciones de eliminación
 */

beforeEach(function ()
{
    // Desactivar notificaciones y logging de actividad
    Notification::fake();
    Activity::disableLogging();

    // Desactivar observers para evitar errores en tests
    User::unsetEventDispatcher();
    Role::unsetEventDispatcher();
    Permission::unsetEventDispatcher();

    // Crear permisos base para gestión de roles
    Permission::create(['name' => 'read any role', 'description' => 'leer cualquier rol', 'guard_name' => 'web']);
    Permission::create(['name' => 'read role', 'description' => 'leer rol', 'guard_name' => 'web']);
    Permission::create(['name' => 'create new roles', 'description' => 'crear nuevos roles', 'guard_name' => 'web']);
    Permission::create(['name' => 'update roles', 'description' => 'actualizar roles', 'guard_name' => 'web']);
    Permission::create(['name' => 'delete roles', 'description' => 'eliminar roles', 'guard_name' => 'web']);

    // Resetear caché de permisos de Spatie DESPUÉS de crearlos
    app()[\Spatie\Permission\PermissionRegistrar::class]->forgetCachedPermissions();

    // Crear rol de administrador con todos los permisos
    $this->adminRole = Role::create(['name' => 'Administrador de Prueba', 'description' => 'administrador para tests', 'guard_name' => 'web']);
    $this->adminRole->givePermissionTo(['read any role', 'read role', 'create new roles', 'update roles', 'delete roles']);

    // Crear usuario administrador
    $this->adminUser = User::factory()->create();
    $this->adminUser->assignRole($this->adminRole);
});

test('un administrador puede ver la lista de roles', function ()
{
    Role::create(['name' => 'Rol de Prueba 1', 'description' => 'descripción prueba 1', 'guard_name' => 'web']);
    Role::create(['name' => 'Rol de Prueba 2', 'description' => 'descripción prueba 2', 'guard_name' => 'web']);

    $response = $this->actingAs($this->adminUser)->get(route('roles.index'));

    $response->assertStatus(200);
});

test('un administrador puede crear un nuevo rol', function ()
{
    $permission = Permission::create(['name' => 'test permission', 'description' => 'permiso de prueba', 'guard_name' => 'web']);

    $response = $this->actingAs($this->adminUser)->post(route('roles.store'), [
        'name' => 'Nuevo Rol de Prueba',
        'description' => 'descripción del nuevo rol',
        'guard_name' => 'web',
        'permissions' => [$permission->description],
    ]);

    $response->assertRedirect(route('roles.index'));
    $this->assertDatabaseHas('roles', [
        'name' => 'Nuevo Rol de Prueba',
        'description' => 'descripción del nuevo rol',
    ]);
});

test('un administrador puede actualizar un rol existente', function ()
{
    $role = Role::create(['name' => 'Rol Original', 'description' => 'descripción original', 'guard_name' => 'web']);

    $response = $this->actingAs($this->adminUser)->put(route('roles.update', $role), [
        'name' => 'Rol Actualizado',
        'description' => 'descripción actualizada',
        'guard_name' => 'web',
        'permissions' => [],
    ]);

    $response->assertRedirect(route('roles.index'));
    $this->assertDatabaseHas('roles', [
        'id' => $role->id,
        'name' => 'Rol Actualizado',
        'description' => 'descripción actualizada',
    ]);
});

test('un administrador puede eliminar un rol sin asociaciones', function ()
{
    $role = Role::create(['name' => 'Rol a Eliminar', 'description' => 'será eliminado', 'guard_name' => 'web']);

    $response = $this->actingAs($this->adminUser)->delete(route('roles.destroy', $role));

    $response->assertRedirect(route('roles.index'));
    $this->assertDatabaseMissing('roles', ['id' => $role->id]);
});

test('no se puede eliminar un rol que tiene usuarios asignados', function ()
{
    $role = Role::create(['name' => 'Rol con Usuarios', 'description' => 'tiene usuarios', 'guard_name' => 'web']);

    // Crear usuario y asignar el rol
    $user = User::factory()->create();
    $user->assignRole($role);

    $response = $this->actingAs($this->adminUser)->delete(route('roles.destroy', $role));

    $response->assertForbidden();
    $this->assertDatabaseHas('roles', ['id' => $role->id]);
});

test('no se puede eliminar un rol que tiene permisos asignados', function ()
{
    $permission = Permission::create(['name' => 'special permission', 'description' => 'permiso especial', 'guard_name' => 'web']);
    $role = Role::create(['name' => 'Rol con Permisos', 'description' => 'tiene permisos', 'guard_name' => 'web']);
    $role->givePermissionTo($permission);

    $response = $this->actingAs($this->adminUser)->delete(route('roles.destroy', $role));

    $response->assertForbidden();
    $this->assertDatabaseHas('roles', ['id' => $role->id]);
});

test('un administrador puede asignar permisos a un rol', function ()
{
    $role = Role::create(['name' => 'Rol sin Permisos', 'description' => 'sin permisos', 'guard_name' => 'web']);
    $permission = Permission::create(['name' => 'new permission', 'description' => 'nuevo permiso', 'guard_name' => 'web']);

    $response = $this->actingAs($this->adminUser)->put(route('roles.update', $role), [
        'name' => $role->name,
        'description' => $role->description,
        'guard_name' => 'web',
        'permissions' => [$permission->description],
    ]);

    $response->assertRedirect(route('roles.index'));
    expect($role->fresh()->hasPermissionTo($permission))->toBeTrue();
});

test('un administrador puede quitar permisos de un rol', function ()
{
    $permission = Permission::create(['name' => 'removable permission', 'description' => 'permiso removible', 'guard_name' => 'web']);
    $role = Role::create(['name' => 'Rol con Permiso', 'description' => 'tiene un permiso', 'guard_name' => 'web']);
    $role->givePermissionTo($permission);

    $response = $this->actingAs($this->adminUser)->put(route('roles.update', $role), [
        'name' => $role->name,
        'description' => $role->description,
        'guard_name' => 'web',
        'permissions' => [],
    ]);

    $response->assertRedirect(route('roles.index'));
    expect($role->fresh()->hasPermissionTo($permission))->toBeFalse();
});

test('un usuario sin permisos no puede ver la lista de roles', function ()
{
    $regularUser = User::factory()->create();

    $response = $this->actingAs($regularUser)->get(route('roles.index'));

    $response->assertForbidden();
});

test('un usuario sin permisos no puede crear roles', function ()
{
    $regularUser = User::factory()->create();

    $response = $this->actingAs($regularUser)->post(route('roles.store'), [
        'name' => 'Rol No Autorizado',
        'description' => 'no debería crearse',
        'guard_name' => 'web',
        'permissions' => [],
    ]);

    $response->assertForbidden();
});

test('un usuario sin permisos no puede actualizar roles', function ()
{
    $regularUser = User::factory()->create();
    $role = Role::create(['name' => 'Rol de Prueba', 'description' => 'prueba', 'guard_name' => 'web']);

    $response = $this->actingAs($regularUser)->put(route('roles.update', $role), [
        'name' => 'Rol Modificado',
        'description' => 'no debería modificarse',
        'guard_name' => 'web',
        'permissions' => [],
    ]);

    $response->assertForbidden();
});

test('un usuario sin permisos no puede eliminar roles', function ()
{
    $regularUser = User::factory()->create();
    $role = Role::create(['name' => 'Rol de Prueba', 'description' => 'prueba', 'guard_name' => 'web']);

    $response = $this->actingAs($regularUser)->delete(route('roles.destroy', $role));

    $response->assertForbidden();
    $this->assertDatabaseHas('roles', ['id' => $role->id]);
});

test('el rol Superusuario no puede ser eliminado', function ()
{
    // Crear el rol Superusuario con ID 1
    $superuserRole = Role::create(['name' => 'Superusuario', 'description' => 'superusuario del sistema', 'guard_name' => 'web']);
    // Forzar ID 1 si no lo tiene
    if ($superuserRole->id !== 1)
    {
        $superuserRole->id = 1;
        $superuserRole->save();
    }

    $response = $this->actingAs($this->adminUser)->delete(route('roles.destroy', $superuserRole));

    $response->assertForbidden();
    $this->assertDatabaseHas('roles', ['name' => 'Superusuario']);
});

test('el rol Superusuario no puede ser modificado por usuarios no superusuarios', function ()
{
    // Crear el rol Superusuario con ID 1
    $superuserRole = Role::create(['name' => 'Superusuario', 'description' => 'superusuario del sistema', 'guard_name' => 'web']);
    // Forzar ID 1
    if ($superuserRole->id !== 1)
    {
        $superuserRole->id = 1;
        $superuserRole->save();
    }

    $response = $this->actingAs($this->adminUser)->put(route('roles.update', $superuserRole), [
        'name' => 'Superusuario Modificado',
        'description' => 'no debería modificarse',
        'guard_name' => 'web',
        'permissions' => [],
    ]);

    $response->assertForbidden();
    $this->assertDatabaseHas('roles', ['name' => 'Superusuario']);
});
