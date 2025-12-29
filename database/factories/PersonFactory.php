<?php

namespace Database\Factories;

use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Person>
 */
class PersonFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'user_id' => User::factory(),
            'id_card' => fake()->nationalId(),
            'names' => fake()->name(),
            'surnames' => fake()->lastName(),
            'phones' => null,
            'emails' => null,
            'position' => fake()->jobTitle(),
            'staff_type' => $this->faker->randomElement(['Empleado', 'Obrero', 'Contratado']),
        ];
    }
}
