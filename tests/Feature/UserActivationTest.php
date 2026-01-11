<?php

use App\Models\User;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\URL;

/**
 * Tests de integración para activación de usuarios.
 *
 * Estos tests verifican:
 * - Acceso a la página de activación con URL firmada válida
 * - Rechazo con firma inválida o expirada
 * - Proceso de activación completo
 */

test('usuario puede acceder a página de activación con URL firmada válida', function ()
{
    Event::fake();

    $user = User::factory()->create(['is_active' => false]);

    $activationUrl = URL::signedRoute('user.activate', ['user' => $user->id]);

    $response = $this->get($activationUrl);

    $response->assertStatus(200);
});

test('usuario no puede acceder a página de activación con firma inválida', function ()
{
    $user = User::factory()->create(['is_active' => false]);

    $response = $this->get(route('user.activate', ['user' => $user->id]) . '?signature=invalid');

    $response->assertStatus(403);
});

test('usuario puede completar el proceso de activación', function ()
{
    Event::fake();

    $user = User::factory()->create(['is_active' => false]);

    $activationUrl = URL::signedRoute('user.activate.update', ['user' => $user->id]);

    $response = $this->post($activationUrl, [
        'password' => 'NewPassword123!',
        'password_confirmation' => 'NewPassword123!',
    ]);

    $user->refresh();
    expect($user->is_active)->toBeTrue();
    $response->assertRedirect();
});

test('activación falla si las contraseñas no coinciden', function ()
{
    Event::fake();

    $user = User::factory()->create(['is_active' => false]);

    $activationUrl = URL::signedRoute('user.activate.update', ['user' => $user->id]);

    $response = $this->post($activationUrl, [
        'password' => 'NewPassword123!',
        'password_confirmation' => 'DifferentPassword123!',
    ]);

    $response->assertInvalid(['password']);
    $user->refresh();
    expect($user->is_active)->toBeFalse();
});

test('activación falla con contraseña débil', function ()
{
    Event::fake();

    $user = User::factory()->create(['is_active' => false]);

    $activationUrl = URL::signedRoute('user.activate.update', ['user' => $user->id]);

    $response = $this->post($activationUrl, [
        'password' => '123',
        'password_confirmation' => '123',
    ]);

    $response->assertInvalid(['password']);
    $user->refresh();
    expect($user->is_active)->toBeFalse();
});
