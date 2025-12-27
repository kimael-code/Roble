<?php

namespace Tests\Feature\Auth;

use App\Dto\EmployeeDto;
use App\Models\Person;
use App\Models\User;
use App\Repositories\EmployeeRepository;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Hash;
use Mockery\MockInterface;
use Tests\TestCase;

class RegistrationTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        // Crear Superusuario para habilitar rutas de autenticación
        seedSuperuser();
    }

    public function test_registration_screen_can_be_rendered()
    {
        $response = $this->get(route('register'));

        $response->assertStatus(200);
    }

    public function test_new_active_employees_can_register_with_valid_cedula()
    {
        Event::fake();
        // Mock del EmployeeDto
        $employeeDto = new EmployeeDto(
            company_code: '001',
            nationality: 'V',
            id_card: '12345678',
            rif: 'V12345678-9',
            names: 'Juan',
            surnames: 'Pérez',
            staff_type_code: '0000001',
            org_unit_code: 'HR001',
            position: 'Desarrollador',
            email: 'juan.perez@empresa.com',
            phone_ext: '123',
            staff_type_name: 'Empleado',
            org_unit_name: 'Recursos Humanos'
        );

        // Mock específico para este test
        $mock = $this->mock(EmployeeRepository::class, function (MockInterface $mock) use ($employeeDto)
        {
            $mock->shouldReceive('find')
                ->with('12345678')
                ->twice() // Se llama dos veces: una en la validación y otra en la creación
                ->andReturn($employeeDto);
        });

        $this->app->instance(EmployeeRepository::class, $mock);

        $response = $this->post(route('register.store'), [
            'id_card' => '12345678',
            'password' => 'Password123!',
            'password_confirmation' => 'Password123!',
        ]);

        // Verificar que se creó el usuario
        $this->assertDatabaseHas('users', [
            'email' => 'juan.perez@empresa.com',
            'name' => 'juan.perez',
        ]);

        // Verificar que el usuario registrado via Fortify es interno (no externo)
        $user = User::where('email', 'juan.perez@empresa.com')->first();
        $this->assertFalse($user->is_external, 'Usuario registrado via Fortify debe ser interno (is_external=false)');

        // Verificar que el usuario registrado via Fortify está activo
        $this->assertTrue($user->is_active, 'Usuario registrado via Fortify debe estar activo (is_active=true)');

        // Verificar que se creó la persona asociada
        $this->assertDatabaseHas('people', [
            'id_card' => '12345678',
            'names' => 'Juan',
            'surnames' => 'Pérez',
        ]);

        // Verificar autenticación
        $this->assertAuthenticated();
        $response->assertRedirect(route('dashboard'));
    }

    public function test_registration_fails_when_employee_has_no_institutional_email()
    {
        Event::fake();
        $employeeDto = new EmployeeDto(
            company_code: '001',
            nationality: 'V',
            id_card: '87654321',
            rif: 'V87654321-9',
            names: 'María',
            surnames: 'González',
            staff_type_code: '0000001',
            org_unit_code: 'HR001',
            position: 'Analista',
            email: null,
            phone_ext: '456',
            staff_type_name: 'Empleado',
            org_unit_name: 'Recursos Humanos'
        );

        $mock = $this->mock(EmployeeRepository::class, function (MockInterface $mock) use ($employeeDto)
        {
            $mock->shouldReceive('find')
                ->with('87654321')
                ->once()
                ->andReturn($employeeDto);
        });

        $this->app->instance(EmployeeRepository::class, $mock);

        $response = $this->post(route('register.store'), [
            'id_card' => '87654321',
            'password' => 'Password123!',
            'password_confirmation' => 'Password123!',
        ]);

        $this->assertGuest();
        $response->assertInvalid([
            'id_card' => 'Correo institucional no definido. Comuníquese con Recursos Humanos.',
        ]);
    }

    public function test_registration_fails_when_cedula_does_not_correspond_to_active_employee()
    {
        Event::fake();
        $mock = $this->mock(EmployeeRepository::class, function (MockInterface $mock)
        {
            $mock->shouldReceive('find')
                ->with('99999999')
                ->once()
                ->andReturn(null);
        });

        $this->app->instance(EmployeeRepository::class, $mock);

        $response = $this->post(route('register.store'), [
            'id_card' => '99999999',
            'password' => 'Password123!',
            'password_confirmation' => 'Password123!',
        ]);

        $this->assertGuest();
        $response->assertInvalid([
            'id_card' => 'El N° de CI no corresponde a un empleado activo.',
        ]);
    }

    public function test_registration_fails_when_cedula_is_already_registered()
    {
        Event::fake();
        // Mock de un empleado válido que será usado por la validación ActiveEmployee
        $employeeDto = new EmployeeDto(
            company_code: '001',
            nationality: 'V',
            id_card: '11223344',
            rif: 'V11223344-9',
            names: 'Existing',
            surnames: 'User',
            staff_type_code: '0000001',
            org_unit_code: 'IT001',
            position: 'Developer',
            email: 'existing.user@empresa.com',
            phone_ext: '123',
            staff_type_name: 'Empleado',
            org_unit_name: 'Tecnología'
        );

        $mock = $this->mock(EmployeeRepository::class, function (MockInterface $mock) use ($employeeDto)
        {
            $mock->shouldReceive('find')
                ->with('11223344')
                ->once()
                ->andReturn($employeeDto);
        });

        $this->app->instance(EmployeeRepository::class, $mock);

        // Crear un usuario existente directamente
        $user = User::create([
            'name' => 'existinguser' . uniqid(),
            'email' => 'existing' . uniqid() . '@example.com',
            'password' => Hash::make('password123'),
        ]);

        // Crear la persona asociada correctamente
        $person = new Person([
            'id_card' => '11223344',
            'names' => 'Existing',
            'surnames' => 'User',
            'phones' => ['ext' => '123'],
            'position' => 'Developer',
            'staff_type' => 'Empleado',
        ]);
        $person->user()->associate($user);
        $person->save();

        $response = $this->post(route('register.store'), [
            'id_card' => '11223344',
            'password' => 'Password123!',
            'password_confirmation' => 'Password123!',
        ]);

        $this->assertGuest();
        $response->assertInvalid([
            'id_card' => 'El campo Número de CI ya existe.',
        ]);
    }

    public function test_registration_fails_with_invalid_password()
    {
        Event::fake();
        $employeeDto = new EmployeeDto(
            company_code: '001',
            nationality: 'V',
            id_card: '55556666',
            rif: 'V55556666-9',
            names: 'Carlos',
            surnames: 'Rodríguez',
            staff_type_code: '0000001',
            org_unit_code: 'IT001',
            position: 'Desarrollador',
            email: 'carlos.rodriguez@empresa.com',
            phone_ext: '789',
            staff_type_name: 'Empleado',
            org_unit_name: 'Tecnología'
        );

        $mock = $this->mock(EmployeeRepository::class, function (MockInterface $mock) use ($employeeDto)
        {
            $mock->shouldReceive('find')
                ->with('55556666')
                ->once()
                ->andReturn($employeeDto);
        });

        $this->app->instance(EmployeeRepository::class, $mock);

        $response = $this->post(route('register.store'), [
            'id_card' => '55556666',
            'password' => '123',
            'password_confirmation' => '123',
        ]);

        $this->assertGuest();
        $response->assertInvalid('password');
    }

    public function test_registration_fails_with_non_numeric_cedula()
    {
        Event::fake();
        $response = $this->post(route('register.store'), [
            'id_card' => 'abc123',
            'password' => 'Password123!',
            'password_confirmation' => 'Password123!',
        ]);

        $this->assertGuest();
        $response->assertInvalid('id_card');
    }

    public function test_registration_fails_with_cedula_too_long()
    {
        Event::fake();
        $response = $this->post(route('register.store'), [
            'id_card' => '123456789',
            'password' => 'Password123!',
            'password_confirmation' => 'Password123!',
        ]);

        $this->assertGuest();
        $response->assertInvalid('id_card');
    }

    public function test_registration_fails_with_password_mismatch()
    {
        Event::fake();
        $employeeDto = new EmployeeDto(
            company_code: '001',
            nationality: 'V',
            id_card: '11112222',
            rif: 'V11112222-9',
            names: 'Test',
            surnames: 'User',
            staff_type_code: '0000001',
            org_unit_code: 'IT001',
            position: 'Tester',
            email: 'test.user@empresa.com',
            phone_ext: '111',
            staff_type_name: 'Empleado',
            org_unit_name: 'Tecnología'
        );

        $mock = $this->mock(EmployeeRepository::class, function (MockInterface $mock) use ($employeeDto)
        {
            $mock->shouldReceive('find')
                ->with('11112222')
                ->once()
                ->andReturn($employeeDto);
        });

        $this->app->instance(EmployeeRepository::class, $mock);

        $response = $this->post(route('register.store'), [
            'id_card' => '11112222',
            'password' => 'Password123!',
            'password_confirmation' => 'DifferentPassword123!',
        ]);

        $this->assertGuest();
        $response->assertInvalid('password');
    }
}