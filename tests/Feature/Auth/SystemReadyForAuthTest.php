<?php

/**
 * Tests para verificar que las rutas de autenticación están bloqueadas
 * cuando el sistema no está listo (no existe Superusuario).
 * 
 * Enfoque TDD: Estos tests deben FALLAR inicialmente.
 */

use App\Models\Security\Role;
use App\Models\User;

describe('Auth routes when system is not ready', function ()
{
    it('blocks login when superuser role does not exist', function ()
    {
        // La base de datos está vacía (RefreshDatabase), no existe el rol Superusuario
        expect(Role::where('name', 'Superusuario')->exists())->toBeFalse();

        $response = $this->get(route('login'));

        $response->assertStatus(403);
    });

    it('blocks login when superuser role exists but no user has it', function ()
    {
        // Crear el rol Superusuario sin asignarlo a ningún usuario
        // Usamos withoutEvents para evitar que el observer intente notificar
        Role::withoutEvents(function ()
        {
            Role::create([
                'name' => 'Superusuario',
                'guard_name' => 'web',
                'description' => 'Superusuario de prueba',
            ]);
        });

        expect(Role::where('name', 'Superusuario')->exists())->toBeTrue();
        expect(User::role('Superusuario')->exists())->toBeFalse();

        $response = $this->get(route('login'));

        $response->assertStatus(403);
    });

    it('blocks register when superuser role does not exist', function ()
    {
        expect(Role::where('name', 'Superusuario')->exists())->toBeFalse();

        $response = $this->get(route('register'));

        $response->assertStatus(403);
    });

    it('blocks password reset request when superuser role does not exist', function ()
    {
        expect(Role::where('name', 'Superusuario')->exists())->toBeFalse();

        $response = $this->get(route('password.request'));

        $response->assertStatus(403);
    });
});
