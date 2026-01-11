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

test('user can access the activation page with a valid signed URL', function ()
{
    Event::fake();

    $user = User::factory()->create(['is_active' => false]);

    $activationUrl = URL::signedRoute('user.activate', ['user' => $user->id]);

    $response = $this->get($activationUrl);

    $response->assertStatus(200);
});

test('user cannot access the activation page with an invalid signature', function ()
{
    $user = User::factory()->create(['is_active' => false]);

    $response = $this->get(route('user.activate', ['user' => $user->id]) . '?signature=invalid');

    $response->assertStatus(403);
});

test('user can complete the activation process', function ()
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

test('activation fails if passwords do not match', function ()
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

test('activation fails with a weak password', function ()
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
