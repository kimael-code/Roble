<?php

use App\Contracts\EmployeeRepository as EmployeeContract;
use App\Dto\EmployeeDto;
use App\Models\Organization\Organization;
use App\Models\Organization\OrganizationalUnit;
use App\Models\Security\Role;
use App\Models\User;
use Illuminate\Support\Facades\Notification;
use Spatie\Activitylog\Facades\Activity;

/**
 * Tests de integración para el instalador de Superusuario.
 *
 * Estos tests verifican:
 * - Acceso al instalador cuando no existe superusuario
 * - Bloqueo cuando ya existe superusuario
 * - Creación del primer superusuario
 */

beforeEach(function ()
{
    // Desactivar observers y notificaciones para evitar efectos secundarios
    Notification::fake();
    Activity::disableLogging();
    User::unsetEventDispatcher();
    Organization::unsetEventDispatcher();
    OrganizationalUnit::unsetEventDispatcher();
});

test('instalador es accesible cuando no existe superusuario', function ()
{
    $response = $this->get(route('su-installer.index'));

    $response->assertStatus(200);
});

test('instalador redirige al asistente cuando no existe superusuario', function ()
{
    $response = $this->get(route('su-installer.wizard'));

    $response->assertStatus(200);
});

test('instalador está bloqueado cuando existe superusuario', function ()
{
    // Crear rol Superusuario manualmente (sin seeder para evitar trigger PostgreSQL)
    $superuserRole = Role::create([
        'name' => 'Superusuario',
        'description' => 'superusuario del sistema',
        'guard_name' => 'web',
    ]);

    // Resetear caché de permisos
    app()[\Spatie\Permission\PermissionRegistrar::class]->forgetCachedPermissions();

    // Crear usuario activo con el rol Superusuario
    $user = User::factory()->create(['is_active' => true]);
    $user->assignRole($superuserRole);

    $response = $this->get(route('su-installer.index'));

    // El middleware hace abort(403) cuando hay superusuarios activos
    $response->assertForbidden();
});

test('puede crear el primer superusuario via instalador', function ()
{
    // Crear rol Superusuario manualmente (sin seeder para evitar trigger PostgreSQL)
    Role::create([
        'name' => 'Superusuario',
        'description' => 'superusuario del sistema',
        'guard_name' => 'web',
    ]);

    // Resetear caché de permisos
    app()[\Spatie\Permission\PermissionRegistrar::class]->forgetCachedPermissions();

    // Crear la organización y unidad administrativa necesarias
    $organization = Organization::factory()->create();
    $ou = OrganizationalUnit::factory()->for($organization)->create([
        'code' => 'TEST-001',
    ]);

    // Crear mock del empleado que retornará el EmployeeRepository
    $mockEmployee = new EmployeeDto(
        company_code: '001',
        nationality: 'V',
        id_card: '12345678',
        rif: 'V-12345678-0',
        names: 'Juan Carlos',
        surnames: 'Pérez García',
        staff_type_code: '0000001',
        org_unit_code: 'TEST-001',
        position: 'Gerente',
        email: 'admin@sistema.com',
        phone_ext: '1234',
        staff_type_name: 'Empleado',
        org_unit_name: 'Unidad de Prueba',
    );

    // Mockear el EmployeeRepository via el contrato
    // Este mock será usado tanto por StoreSuperuserRequest como por CreateNewSuperuser
    $this->mock(EmployeeContract::class, function ($mock) use ($mockEmployee)
    {
        $mock->shouldReceive('find')
            ->with('12345678')
            ->andReturn($mockEmployee);
    });

    $response = $this->post(route('su-installer.store'), [
        'id_card' => '12345678',
        'name' => 'super.admin',
        'email' => 'admin@sistema.com',
        'password' => 'SuperPassword123!',
        'password_confirmation' => 'SuperPassword123!',
    ]);

    // Verificar que el usuario fue creado
    $this->assertDatabaseHas('users', [
        'name' => 'super.admin',
        'email' => 'admin@sistema.com',
    ]);

    // Verificar que tiene el rol Superusuario
    $user = User::where('email', 'admin@sistema.com')->first();
    expect($user)->not->toBeNull();
    expect($user->hasRole('Superusuario'))->toBeTrue();
    expect($user->is_active)->toBeTrue();
    expect($user->email_verified_at)->not->toBeNull();
});
