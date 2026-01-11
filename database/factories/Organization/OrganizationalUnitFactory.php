<?php

namespace Database\Factories\Organization;

use App\Models\Organization\Organization;
use App\Models\Organization\OrganizationalUnit;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Organization\OrganizationalUnit>
 */
class OrganizationalUnitFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'organization_id' => Organization::factory(),
            'organizational_unit_id' => null, // No parent by default to avoid recursion
            'code' => fake()->shuffleString('1234567890'),
            'name' => fake('en_US')->catchPhrase(),
            'acronym' => fake()->randomAscii(),
            'floor' => fake()->buildingNumber(),
        ];
    }

    /**
     * Indica que la unidad administrativa está inactiva.
     * @return OrganizationalUnitFactory
     */
    public function disabled(): Factory
    {
        return $this->state(fn(array $attributes) => ['disabled_at' => now()]);
    }
}
