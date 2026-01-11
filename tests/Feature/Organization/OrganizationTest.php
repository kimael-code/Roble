<?php

use App\Models\Organization\Organization;
use App\Models\Organization\OrganizationalUnit;
use App\Models\Security\Permission;
use App\Models\Security\Role;
use App\Models\User;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Facades\Storage;
use Spatie\Activitylog\Facades\Activity;

/**
 * Tests de integración para gestión de entes/organizaciones.
 *
 * Reglas de negocio:
 * - Solo un ente puede estar activo a la vez
 * - Al crear nuevo ente, los anteriores se desactivan automáticamente
 * - Se permite activación/desactivación manual flexible
 * - El logo se sube y limpia correctamente
 */

beforeEach(function ()
{
    // Desactivar notificaciones y logging de actividad
    Notification::fake();
    Activity::disableLogging();
    Storage::fake('public');

    // Desactivar observers de modelos para evitar errores en tests
    User::unsetEventDispatcher();
    Organization::unsetEventDispatcher();
    OrganizationalUnit::unsetEventDispatcher();
    Role::unsetEventDispatcher();
    Permission::unsetEventDispatcher();

    // Crear permisos base para gestión de organizaciones
    Permission::create(['name' => 'read any organization', 'description' => 'leer cualquier ente', 'guard_name' => 'web']);
    Permission::create(['name' => 'read organization', 'description' => 'leer ente', 'guard_name' => 'web']);
    Permission::create(['name' => 'create new organizations', 'description' => 'crear nuevos entes', 'guard_name' => 'web']);
    Permission::create(['name' => 'update organizations', 'description' => 'actualizar entes', 'guard_name' => 'web']);
    Permission::create(['name' => 'delete organizations', 'description' => 'eliminar entes', 'guard_name' => 'web']);

    // Resetear caché de permisos de Spatie DESPUÉS de crearlos
    app()[\Spatie\Permission\PermissionRegistrar::class]->forgetCachedPermissions();

    // Crear rol de administrador con todos los permisos
    $this->adminRole = Role::create(['name' => 'Administrador de Entes', 'description' => 'admin de entes', 'guard_name' => 'web']);
    $this->adminRole->givePermissionTo([
        'read any organization', 'read organization', 'create new organizations',
        'update organizations', 'delete organizations'
    ]);

    // Crear usuario administrador
    $this->adminUser = User::factory()->create(['is_active' => true]);
    $this->adminUser->assignRole($this->adminRole);
});

test('admin can view the list of organizations', function ()
{
    Organization::factory()->create(['name' => 'Organización 1']);
    Organization::factory()->create(['name' => 'Organización 2']);

    $response = $this->actingAs($this->adminUser)->get(route('organizations.index'));

    $response->assertStatus(200);
});

test('admin can create a new organization', function ()
{
    $logo = UploadedFile::fake()->image('logo.png', 200, 200);

    $response = $this->actingAs($this->adminUser)->post(route('organizations.store'), [
        'rif' => 'J-12345678-9',
        'name' => 'Nueva Organización',
        'acronym' => 'NO',
        'address' => 'Dirección de prueba',
        'logo_path' => $logo,
    ]);

    $response->assertRedirect(route('organizations.index'));
    $this->assertDatabaseHas('organizations', [
        'rif' => 'J-12345678-9',
        'name' => 'Nueva Organización',
    ]);
});

test('creating a new organization automatically deactivates previous ones', function ()
{
    $oldOrg = Organization::factory()->create(['disabled_at' => null]);
    $logo = UploadedFile::fake()->image('logo.png', 200, 200);

    $this->actingAs($this->adminUser)->post(route('organizations.store'), [
        'rif' => 'J-98765432-1',
        'name' => 'Organización Nueva',
        'acronym' => 'ON',
        'address' => 'Dirección nueva',
        'logo_path' => $logo,
    ]);

    $oldOrg->refresh();
    expect($oldOrg->disabled_at)->not->toBeNull();
});

test('admin can update an organization', function ()
{
    $org = Organization::factory()->create(['name' => 'Nombre Original']);

    $response = $this->actingAs($this->adminUser)->put(route('organizations.update', $org), [
        'rif' => $org->rif,
        'name' => 'Nombre Actualizado',
        'acronym' => $org->acronym,
        'address' => $org->address,
        'disabled' => false,
    ]);

    $response->assertRedirect(route('organizations.index'));
    $this->assertDatabaseHas('organizations', [
        'id' => $org->id,
        'name' => 'Nombre Actualizado',
    ]);
});

test('admin can deactivate an organization manually', function ()
{
    // Crear dos organizations para evitar bloqueo lógico
    $org1 = Organization::factory()->create(['disabled_at' => null]);
    $org2 = Organization::factory()->create(['disabled_at' => null]);

    $response = $this->actingAs($this->adminUser)->put(route('organizations.update', $org1), [
        'rif' => $org1->rif,
        'name' => $org1->name,
        'acronym' => $org1->acronym,
        'address' => $org1->address,
        'disabled' => true,
    ]);

    $response->assertRedirect(route('organizations.index'));
    $org1->refresh();
    expect($org1->disabled_at)->not->toBeNull();
});

test('admin can activate an organization that was deactivated', function ()
{
    $org = Organization::factory()->create(['disabled_at' => now()]);

    $response = $this->actingAs($this->adminUser)->put(route('organizations.update', $org), [
        'rif' => $org->rif,
        'name' => $org->name,
        'acronym' => $org->acronym,
        'address' => $org->address,
        'disabled' => false,
    ]);

    $response->assertRedirect(route('organizations.index'));
    $org->refresh();
    expect($org->disabled_at)->toBeNull();
});

test('the logo is uploaded correctly when creating an organization', function ()
{
    $logo = UploadedFile::fake()->image('logo.png', 200, 200);

    $this->actingAs($this->adminUser)->post(route('organizations.store'), [
        'rif' => 'J-11223344-5',
        'name' => 'Org con Logo',
        'acronym' => 'OCL',
        'address' => 'Dirección',
        'logo_path' => $logo,
    ]);

    $org = Organization::where('rif', 'J-11223344-5')->first();
    Storage::disk('public')->assertExists($org->logo_path);
});

test('user without permissions cannot manage organizations', function ()
{
    $regularUser = User::factory()->create();

    $response = $this->actingAs($regularUser)->get(route('organizations.index'));
    $response->assertForbidden();
});

test('user without permissions cannot create organizations', function ()
{
    $regularUser = User::factory()->create();
    $logo = UploadedFile::fake()->image('logo.png', 200, 200);

    $response = $this->actingAs($regularUser)->post(route('organizations.store'), [
        'rif' => 'J-55667788-9',
        'name' => 'Org No Autorizada',
        'logo_path' => $logo,
    ]);

    $response->assertForbidden();
});

test('cannot delete the only active entity', function ()
{
    $org = Organization::factory()->create(['disabled_at' => null]);

    $response = $this->actingAs($this->adminUser)->delete(route('organizations.destroy', $org));

    $response->assertForbidden();
    $this->assertDatabaseHas('organizations', ['id' => $org->id]);
});

test('cannot delete an entity with organizational units', function ()
{
    $org = Organization::factory()->create(['disabled_at' => null]);
    $org2 = Organization::factory()->create(['disabled_at' => null]); // Otro activo para evitar bloqueo
    OrganizationalUnit::factory()->for($org)->create();

    $response = $this->actingAs($this->adminUser)->delete(route('organizations.destroy', $org));

    $response->assertForbidden();
    $this->assertDatabaseHas('organizations', ['id' => $org->id]);
});

test('can delete an entity with no administrative units if another remains active', function ()
{
    $org1 = Organization::factory()->create(['disabled_at' => null]);
    $org2 = Organization::factory()->create(['disabled_at' => null]); // Otro activo

    $response = $this->actingAs($this->adminUser)->delete(route('organizations.destroy', $org1));

    $response->assertRedirect(route('organizations.index'));
    $this->assertDatabaseMissing('organizations', ['id' => $org1->id]);
});
