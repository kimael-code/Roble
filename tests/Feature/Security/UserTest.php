<?php

use App\Models\Organization\Organization;
use App\Models\Organization\OrganizationalUnit;
use App\Models\Security\Permission;
use App\Models\Security\Role;
use App\Models\User;
use Illuminate\Support\Facades\Notification;
use Spatie\Activitylog\Facades\Activity;

/**
 * Integration tests para gestión de usuarios.
 *
 * Estos tests verifican la funcionalidad de gestión de usuarios:
 * - Creación de usuarios internos (por admin)
 * - Creación de usuarios externos (por admin)
 * - Actualización, habilitación/deshabilitación
 * - Eliminación suave y forzada
 * - Reset de contraseña, activación manual, reenvío de activación
 */

beforeEach(function ()
{
    // Disable notifications and activity logging
    Notification::fake();
    Activity::disableLogging();

    // Desactivar observadores de modelos para evitar errores en tests
    User::unsetEventDispatcher();
    Organization::unsetEventDispatcher();
    OrganizationalUnit::unsetEventDispatcher();

    // Crear permisos base para gestión de usuarios
    Permission::create(['name' => 'read any user', 'description' => 'leer cualquier usuario', 'guard_name' => 'web']);
    Permission::create(['name' => 'read user', 'description' => 'leer usuario', 'guard_name' => 'web']);
    Permission::create(['name' => 'create new users', 'description' => 'crear nuevos usuarios', 'guard_name' => 'web']);
    Permission::create(['name' => 'update users', 'description' => 'actualizar usuarios', 'guard_name' => 'web']);
    Permission::create(['name' => 'delete users', 'description' => 'eliminar usuarios', 'guard_name' => 'web']);
    Permission::create(['name' => 'force delete users', 'description' => 'eliminar usuarios permanentemente', 'guard_name' => 'web']);
    Permission::create(['name' => 'restore users', 'description' => 'restaurar usuarios', 'guard_name' => 'web']);
    Permission::create(['name' => 'enable users', 'description' => 'activar usuarios', 'guard_name' => 'web']);
    Permission::create(['name' => 'disable users', 'description' => 'desactivar usuarios', 'guard_name' => 'web']);
    Permission::create(['name' => 'reset user passwords', 'description' => 'resetear contraseñas', 'guard_name' => 'web']);

    // Reset Spatie permission cache AFTER creating them
    app()[\Spatie\Permission\PermissionRegistrar::class]->forgetCachedPermissions();

    // Create admin role con todos los permissions
    $this->adminRole = Role::create(['name' => 'Administrador', 'description' => 'administrator test', 'guard_name' => 'web']);
    $this->adminRole->givePermissionTo([
        'read any user', 'read user', 'create new users', 'update users',
        'delete users', 'force delete users', 'restore users',
        'enable users', 'disable users', 'reset user passwords'
    ]);

    // Create admin user
    $this->adminUser = User::factory()->create(['is_active' => true]);
    $this->adminUser->assignRole($this->adminRole);

    // Crear organization y organizational unit para tests
    $this->organization = Organization::factory()->create(['disabled_at' => null]);
    $this->ou = OrganizationalUnit::factory()
        ->for($this->organization)
        ->create(['organizational_unit_id' => null, 'name' => 'Desarrollo']);
});

// =====================================================
// CREACIÓN DE USUARIOS INTERNOS (por administrador)
// =====================================================

test('admin can create internal user with corporate email', function ()
{
    $response = $this->actingAs($this->adminUser)->post(route('users.store'), [
        'name' => 'usuario.interno',
        'email' => 'interno@empresa.com',
        'is_external' => false,
        'id_card' => '12345678',
        'names' => 'Juan',
        'surnames' => 'Pérez',
        'position' => 'Desarrollador',
        'staff_type' => 'Contratado',
        'ou_names' => ['Desarrollo'],
        'roles' => ['Administrador'],
        'permissions' => [],
    ]);

    $this->assertDatabaseHas('users', [
        'email' => 'interno@empresa.com',
        'is_external' => false,
    ]);
    $response->assertRedirect(route('users.index'));
});

test('admin can create internal user without personal data', function ()
{
    $response = $this->actingAs($this->adminUser)->post(route('users.store'), [
        'name' => 'usuario.sinpersona',
        'email' => 'sinpersona@empresa.com',
        'is_external' => false,
        'id_card' => null,
        'names' => null,
        'surnames' => null,
        'position' => null,
        'staff_type' => null,
        'ou_names' => [],
        'roles' => ['Administrador'],
        'permissions' => [],
    ]);

    $this->assertDatabaseHas('users', [
        'email' => 'sinpersona@empresa.com',
        'is_external' => false,
    ]);
    $response->assertRedirect(route('users.index'));
});

test('admin can create internal user with personal email at their discretion', function ()
{
    $response = $this->actingAs($this->adminUser)->post(route('users.store'), [
        'name' => 'usuario.personal',
        'email' => 'personal@gmail.com',
        'is_external' => false,
        'id_card' => '87654321',
        'names' => 'María',
        'surnames' => 'García',
        'position' => 'Analista',
        'staff_type' => 'Fijo',
        'ou_names' => ['Desarrollo'],
        'roles' => ['Administrador'],
        'permissions' => [],
    ]);

    $this->assertDatabaseHas('users', [
        'email' => 'personal@gmail.com',
        'is_external' => false,
    ]);
    $response->assertRedirect(route('users.index'));
});

// =====================================================
// CREACIÓN DE USUARIOS EXTERNOS (por administrador)
// =====================================================

test('admin can create external user with personal data required', function ()
{
    $response = $this->actingAs($this->adminUser)->post(route('users.store'), [
        'name' => 'usuario.externo',
        'email' => 'externo@externo.com',
        'is_external' => true,
        'id_card' => '11223344',
        'names' => 'Carlos',
        'surnames' => 'Externo',
        'position' => 'Consultor',
        'staff_type' => 'Externo',
        'ou_names' => null, // Usuarios externos no se asocian con UAs
        'roles' => [],
        'permissions' => [],
    ]);

    $this->assertDatabaseHas('users', [
        'email' => 'externo@externo.com',
        'is_external' => true,
    ]);
    $this->assertDatabaseHas('people', [
        'id_card' => '11223344',
        'names' => 'Carlos',
        'surnames' => 'Externo',
    ]);
    $response->assertRedirect(route('users.index'));
});

test('external user requires personal data', function ()
{
    $response = $this->actingAs($this->adminUser)->post(route('users.store'), [
        'name' => 'usuario.externo.invalido',
        'email' => 'externo.invalido@externo.com',
        'is_external' => true,
        'id_card' => null, // Falta dato obligatorio
        'names' => null,   // Falta dato obligatorio
        'surnames' => null, // Falta dato obligatorio
        'position' => null,
        'staff_type' => null,
        'ou_names' => null,
        'roles' => [],
        'permissions' => [],
    ]);

    $response->assertInvalid(['id_card', 'names', 'surnames']);
});

test('external user can have optional phones and emails', function ()
{
    $response = $this->actingAs($this->adminUser)->post(route('users.store'), [
        'name' => 'usuario.externo.completo',
        'email' => 'externo.completo@externo.com',
        'is_external' => true,
        'id_card' => '55667788',
        'names' => 'Pedro',
        'surnames' => 'Externo Completo',
        'position' => 'Asesor',
        'staff_type' => 'Externo',
        'phones' => ['móvil' => '04141234567'],
        'emails' => ['personal' => 'pedro@personal.com'],
        'ou_names' => null,
        'roles' => [],
        'permissions' => [],
    ]);

    $this->assertDatabaseHas('users', [
        'email' => 'externo.completo@externo.com',
        'is_external' => true,
    ]);
    $response->assertRedirect(route('users.index'));
});

// =====================================================
// ACTUALIZACIÓN DE USUARIOS
// =====================================================

test('admin can update an existing user', function ()
{
    $user = User::factory()->create(['name' => 'usuario.original', 'email' => 'original@test.com']);

    $response = $this->actingAs($this->adminUser)->put(route('users.update', $user), [
        'name' => 'usuario.actualizado',
        'email' => 'actualizado@test.com',
        'is_external' => false,
        'id_card' => null,
        'names' => null,
        'surnames' => null,
        'position' => null,
        'staff_type' => null,
        'ou_names' => [],
        'roles' => [],
        'permissions' => [],
    ]);

    $this->assertDatabaseHas('users', [
        'id' => $user->id,
        'name' => 'usuario.actualizado',
        'email' => 'actualizado@test.com',
    ]);
    $response->assertRedirect(route('users.index'));
});

// =====================================================
// HABILITACIÓN/DESHABILITACIÓN
// =====================================================

test('admin can disable a user', function ()
{
    $user = User::factory()->create(['disabled_at' => null, 'is_active' => true]);

    $response = $this->actingAs($this->adminUser)->put(route('users.disable', $user));

    $user->refresh();
    expect($user->disabled_at)->not->toBeNull();
    $response->assertRedirect();
});

test('admin can enable a disabled user', function ()
{
    $user = User::factory()->create(['disabled_at' => now(), 'is_active' => true]);

    $response = $this->actingAs($this->adminUser)->put(route('users.enable', $user));

    $user->refresh();
    expect($user->disabled_at)->toBeNull();
    $response->assertRedirect();
});

// =====================================================
// ELIMINACIÓN
// =====================================================

test('admin can delete a user (soft delete)', function ()
{
    $user = User::factory()->create();

    $response = $this->actingAs($this->adminUser)->delete(route('users.destroy', $user));

    $this->assertSoftDeleted('users', ['id' => $user->id]);
    $response->assertRedirect(route('users.index'));
});

test('admin can permanently delete a user', function ()
{
    $user = User::factory()->create(['deleted_at' => now()]);

    $response = $this->actingAs($this->adminUser)->delete(route('users.force-destroy', $user));

    $this->assertDatabaseMissing('users', ['id' => $user->id]);
    $response->assertRedirect(route('users.index'));
});

test('admin can restore a deleted user', function ()
{
    $user = User::factory()->create(['deleted_at' => now()]);

    $response = $this->actingAs($this->adminUser)->put(route('users.restore', $user));

    $user->refresh();
    expect($user->deleted_at)->toBeNull();
    $response->assertRedirect();
});

// =====================================================
// ACCIONES ESPECIALES
// =====================================================

test('admin can reset password of an active user', function ()
{
    $user = User::factory()->create(['is_active' => true, 'disabled_at' => null, 'deleted_at' => null]);

    $response = $this->actingAs($this->adminUser)->post(route('users.reset-password', $user));

    $response->assertRedirect();
});

test('admin can resend activation email to an inactive user', function ()
{
    $user = User::factory()->create(['is_active' => false, 'disabled_at' => null, 'deleted_at' => null]);

    $response = $this->actingAs($this->adminUser)->post(route('users.resend-activation', $user));

    $response->assertRedirect();
});

test('admin can manually activate an inactive user', function ()
{
    $user = User::factory()->create(['is_active' => false, 'disabled_at' => null, 'deleted_at' => null]);

    $response = $this->actingAs($this->adminUser)->post(route('users.manually-activate', $user));

    $response->assertRedirect();
});

// =====================================================
// CONTROL DE ACCESO
// =====================================================

test('user without permissions cannot create users', function ()
{
    $regularUser = User::factory()->create();

    $response = $this->actingAs($regularUser)->post(route('users.store'), [
        'name' => 'no.autorizado',
        'email' => 'noautorizado@test.com',
        'is_external' => false,
    ]);

    $response->assertForbidden();
});

test('user without permissions cannot view list of users', function ()
{
    $regularUser = User::factory()->create();

    $response = $this->actingAs($regularUser)->get(route('users.index'));

    $response->assertForbidden();
});

test('user cannot delete themselves', function ()
{
    $response = $this->actingAs($this->adminUser)->delete(route('users.destroy', $this->adminUser));

    $response->assertForbidden();
});

test('user cannot deactivate themselves', function ()
{
    $response = $this->actingAs($this->adminUser)->put(route('users.disable', $this->adminUser));

    $response->assertForbidden();
});