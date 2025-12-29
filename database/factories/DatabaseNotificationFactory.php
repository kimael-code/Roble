<?php

namespace Database\Factories;

use App\Models\User;
use Illuminate\Notifications\DatabaseNotification;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/**
 * Factory para DatabaseNotification.
 *
 * @extends Factory<DatabaseNotification>
 */
class DatabaseNotificationFactory extends Factory
{
    /**
     * El nombre del modelo que corresponde a esta factory.
     */
    protected $model = DatabaseNotification::class;

    /**
     * Define el estado por defecto del modelo.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'id' => Str::uuid()->toString(),
            'type' => 'App\\Notifications\\ActionHandledOnModel',
            'notifiable_type' => User::class,
            'notifiable_id' => User::factory(),
            'data' => [
                'causer' => $this->faker->name(),
                'message' => $this->faker->sentence(),
                'url' => '/dashboard',
                'timestamp' => now()->toIso8601String(),
            ],
            'read_at' => null,
            'created_at' => now(),
            'updated_at' => now(),
        ];
    }

    /**
     * Indica que la notificación ya fue leída.
     */
    public function read(): static
    {
        return $this->state(fn(array $attributes) => [
            'read_at' => now(),
        ]);
    }

    /**
     * Indica una notificación antigua (más de 90 días).
     */
    public function old(int $days = 100): static
    {
        return $this->state(fn(array $attributes) => [
            'created_at' => now()->subDays($days),
            'updated_at' => now()->subDays($days),
        ]);
    }
}
