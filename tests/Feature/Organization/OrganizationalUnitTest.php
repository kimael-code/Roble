<?php

use App\Models\Organization\Organization;
use App\Models\Organization\OrganizationalUnit;
use App\Models\Security\Permission;
use App\Models\Security\Role;
use App\Models\User;
use Illuminate\Support\Facades\Notification;
use Spatie\Activitylog\Facades\Activity;

/**
 * Tests de integración para gestión de unidades administrativas.
 *
 * Estos tests verifican:
 * - CRUD de unidades administrativas
 * - Creación de unidades hijas (jerárquicas)
 * - Activación/desactivación de unidades
 * - Restricciones de eliminación
 */

beforeEach(function ()
{
    // Desactivar notificaciones y logging de actividad
    Notification::fake();
    Activity::disableLogging();

    // Desactivar observers de modelos para evitar errores en tests
    User::unsetEventDispatcher();
    OrganizationalUnit::unsetEventDispatcher();
    Organization::unsetEventDispatcher();
    Role::unsetEventDispatcher();
    Permission::unsetEventDispatcher();

    // Crear permisos base
    Permission::create(['name' => 'read any organizational unit', 'description' => 'leer cualquier ua', 'guard_name' => 'web']);
    Permission::create(['name' => 'read organizational unit', 'description' => 'leer ua', 'guard_name' => 'web']);
    Permission::create(['name' => 'create new organizational units', 'description' => 'crear nuevas uas', 'guard_name' => 'web']);
    Permission::create(['name' => 'update organizational units', 'description' => 'actualizar uas', 'guard_name' => 'web']);
    Permission::create(['name' => 'delete organizational units', 'description' => 'eliminar uas', 'guard_name' => 'web']);

    // Resetear caché de permisos de Spatie DESPUÉS de crearlos
    app()[\Spatie\Permission\PermissionRegistrar::class]->forgetCachedPermissions();

    // Crear rol de administrador
    $this->adminRole = Role::create(['name' => 'Administrador de UAs', 'description' => 'admin de uas', 'guard_name' => 'web']);
    $this->adminRole->givePermissionTo([
        'read any organizational unit', 'read organizational unit',
        'create new organizational units', 'update organizational units', 'delete organizational units'
    ]);

    // Crear usuario administrador
    $this->adminUser = User::factory()->create(['is_active' => true]);
    $this->adminUser->assignRole($this->adminRole);

    // Crear organización activa
    $this->organization = Organization::factory()->create(['disabled_at' => null]);
});

test('admin puede ver la lista de unidades administrativas', function ()
{
    OrganizationalUnit::factory()->for($this->organization)->create(['name' => 'UA 1']);
    OrganizationalUnit::factory()->for($this->organization)->create(['name' => 'UA 2']);

    $response = $this->actingAs($this->adminUser)->get(route('organizational-units.index'));

    $response->assertStatus(200);
});

test('admin puede crear una nueva unidad administrativa', function ()
{
    $response = $this->actingAs($this->adminUser)->post(route('organizational-units.store'), [
        'code' => 'DEV001',
        'name' => 'Desarrollo',
        'acronym' => 'DEV',
        'floor' => '3',
        'organization_id' => $this->organization->id,
        'organizational_unit_id' => null,
    ]);

    $response->assertRedirect(route('organizational-units.index'));
    $this->assertDatabaseHas('organizational_units', [
        'name' => 'Desarrollo',
        'code' => 'DEV001',
    ]);
});

test('admin puede crear una unidad administrativa hija', function ()
{
    $parentOU = OrganizationalUnit::factory()->for($this->organization)->create([
        'name' => 'Gerencia General',
        'organizational_unit_id' => null,
    ]);

    $response = $this->actingAs($this->adminUser)->post(route('organizational-units.store'), [
        'code' => 'DEV002',
        'name' => 'Subgerencia de Desarrollo',
        'acronym' => 'SUBDEV',
        'floor' => '2',
        'organization_id' => $this->organization->id,
        'organizational_unit_id' => $parentOU->id,
    ]);

    $response->assertRedirect(route('organizational-units.index'));
    $this->assertDatabaseHas('organizational_units', [
        'name' => 'Subgerencia de Desarrollo',
        'organizational_unit_id' => $parentOU->id,
    ]);
});

test('admin puede actualizar una unidad administrativa', function ()
{
    $ou = OrganizationalUnit::factory()->for($this->organization)->create(['name' => 'Nombre Original']);

    $response = $this->actingAs($this->adminUser)->put(route('organizational-units.update', $ou), [
        'code' => $ou->code,
        'name' => 'Nombre Actualizado',
        'acronym' => $ou->acronym,
        'floor' => $ou->floor,
        'organization_id' => $this->organization->id,
        'organizational_unit_id' => null,
        'disabled' => false,
    ]);

    $response->assertRedirect(route('organizational-units.index'));
    $this->assertDatabaseHas('organizational_units', [
        'id' => $ou->id,
        'name' => 'Nombre Actualizado',
    ]);
});

test('admin puede desactivar una unidad administrativa', function ()
{
    $ou = OrganizationalUnit::factory()->for($this->organization)->create(['disabled_at' => null]);

    $response = $this->actingAs($this->adminUser)->put(route('organizational-units.update', $ou), [
        'code' => $ou->code,
        'name' => $ou->name,
        'acronym' => $ou->acronym,
        'floor' => $ou->floor,
        'organization_id' => $this->organization->id,
        'organizational_unit_id' => null,
        'disabled' => true,
    ]);

    $response->assertRedirect(route('organizational-units.index'));
    $ou->refresh();
    expect($ou->disabled_at)->not->toBeNull();
});

test('admin puede activar una unidad administrativa desactivada', function ()
{
    $ou = OrganizationalUnit::factory()->for($this->organization)->create(['disabled_at' => now()]);

    $response = $this->actingAs($this->adminUser)->put(route('organizational-units.update', $ou), [
        'code' => $ou->code,
        'name' => $ou->name,
        'acronym' => $ou->acronym,
        'floor' => $ou->floor,
        'organization_id' => $this->organization->id,
        'organizational_unit_id' => null,
        'disabled' => false,
    ]);

    $response->assertRedirect(route('organizational-units.index'));
    $ou->refresh();
    expect($ou->disabled_at)->toBeNull();
});

test('admin puede eliminar una unidad administrativa sin usuarios', function ()
{
    $ou = OrganizationalUnit::factory()->for($this->organization)->create();

    $response = $this->actingAs($this->adminUser)->delete(route('organizational-units.destroy', $ou));

    $response->assertRedirect(route('organizational-units.index'));
    $this->assertDatabaseMissing('organizational_units', ['id' => $ou->id]);
});

test('no se puede eliminar una unidad administrativa con usuarios asociados', function ()
{
    $ou = OrganizationalUnit::factory()->for($this->organization)->create();
    $user = User::factory()->create();
    $user->organizationalUnits()->attach($ou);

    $response = $this->actingAs($this->adminUser)->delete(route('organizational-units.destroy', $ou));

    $response->assertForbidden();
    $this->assertDatabaseHas('organizational_units', ['id' => $ou->id]);
});

test('usuario sin permisos no puede ver unidades administrativas', function ()
{
    $regularUser = User::factory()->create();

    $response = $this->actingAs($regularUser)->get(route('organizational-units.index'));

    $response->assertForbidden();
});

test('usuario sin permisos no puede crear unidades administrativas', function ()
{
    $regularUser = User::factory()->create();

    $response = $this->actingAs($regularUser)->post(route('organizational-units.store'), [
        'code' => 'NOAUTH',
        'name' => 'No Autorizado',
        'organization_id' => $this->organization->id,
    ]);

    $response->assertForbidden();
});
