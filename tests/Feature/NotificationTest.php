<?php

use App\Models\User;
use Illuminate\Notifications\DatabaseNotification;
use Illuminate\Support\Str;

/**
 * Tests para el controlador de notificaciones.
 *
 * Estos tests verifican la funcionalidad de gestión de notificaciones:
 * - Visualización de notificaciones
 * - Marcar como leídas
 * - Eliminación individual y masiva
 * - Comando de limpieza automática
 */

/**
 * Helper para crear una notificación de prueba.
 */
function createNotification(User $user, array $attributes = []): DatabaseNotification
{
    return DatabaseNotification::create(array_merge([
        'id' => Str::uuid()->toString(),
        'type' => 'App\\Notifications\\ActionHandledOnModel',
        'notifiable_type' => User::class,
        'notifiable_id' => $user->id,
        'data' => [
            'causer' => 'Usuario de prueba',
            'message' => 'Mensaje de prueba',
            'url' => '/dashboard',
            'timestamp' => now()->toIso8601String(),
        ],
        'read_at' => null,
    ], $attributes));
}

test('guests cannot access notifications', function ()
{
    $response = $this->get(route('notifications.index'));
    $response->assertRedirect(route('login'));
});

test('authenticated users can view all notifications', function ()
{
    $user = User::factory()->create();

    // Crear notificaciones manualmente
    createNotification($user);
    createNotification($user);
    createNotification($user);

    $this->actingAs($user);

    $response = $this->get(route('notifications.index'));
    $response->assertStatus(200);
});

test('user can mark a notification as read without redirect', function ()
{
    $user = User::factory()->create();
    $notification = createNotification($user, ['read_at' => null]);

    $this->actingAs($user);

    $response = $this->put(route('notifications.mark-read', $notification));

    $response->assertRedirect();
    $this->assertNotNull($notification->fresh()->read_at);
});

test('user can mark all notifications as read', function ()
{
    $user = User::factory()->create();

    // Crear múltiples notificaciones no leídas
    createNotification($user, ['read_at' => null]);
    createNotification($user, ['read_at' => null]);
    createNotification($user, ['read_at' => null]);

    $this->actingAs($user);

    $response = $this->post(route('notifications.mark-all-as-read'));

    $response->assertRedirect();

    // Verificar que todas están marcadas como leídas
    $this->assertEquals(
        0,
        $user->unreadNotifications()->count(),
    );
});

test('user can delete a notification', function ()
{
    $user = User::factory()->create();
    $notification = createNotification($user);

    $this->actingAs($user);

    $response = $this->delete(route('notifications.destroy', $notification));

    $response->assertRedirect();
    $this->assertDatabaseMissing('notifications', [
        'id' => $notification->id,
    ]);
});

test('user can delete all notifications', function ()
{
    $user = User::factory()->create();

    // Crear múltiples notificaciones
    createNotification($user);
    createNotification($user);
    createNotification($user);
    createNotification($user);
    createNotification($user);

    $this->actingAs($user);

    $response = $this->delete(route('notifications.destroy-all'));

    $response->assertRedirect();
    $this->assertEquals(0, $user->notifications()->count());
});

test('cleanup command removes old notifications', function ()
{
    $user = User::factory()->create();

    // Crear notificación antigua (100 días)
    $oldNotification = createNotification($user, [
        'created_at' => now()->subDays(100),
    ]);

    // Crear notificación reciente (10 días)
    $recentNotification = createNotification($user, [
        'created_at' => now()->subDays(10),
    ]);

    // Ejecutar comando con 90 días por defecto
    $this->artisan('notifications:cleanup')
        ->assertExitCode(0);

    // Verificar que la antigua fue eliminada
    $this->assertDatabaseMissing('notifications', [
        'id' => $oldNotification->id,
    ]);

    // Verificar que la reciente aún existe
    $this->assertDatabaseHas('notifications', [
        'id' => $recentNotification->id,
    ]);
});

test('cleanup command respects custom days parameter', function ()
{
    $user = User::factory()->create();

    // Crear notificación de 40 días
    $notification = createNotification($user, [
        'created_at' => now()->subDays(40),
    ]);

    // Ejecutar comando con 30 días
    $this->artisan('notifications:cleanup --days=30')
        ->assertExitCode(0);

    // Verificar que fue eliminada
    $this->assertDatabaseMissing('notifications', [
        'id' => $notification->id,
    ]);
});
