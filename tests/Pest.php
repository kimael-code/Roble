<?php

/*
|--------------------------------------------------------------------------
| Test Case
|--------------------------------------------------------------------------
|
| The closure you provide to your test functions is always bound to a specific PHPUnit test
| case class. By default, that class is "PHPUnit\Framework\TestCase". Of course, you may
| need to change it using the "pest()" function to bind a different classes or traits.
|
*/

pest()->extend(Tests\TestCase::class)
    ->use(Illuminate\Foundation\Testing\RefreshDatabase::class)
    ->in('Feature');

// Seed permissions without observers for Unit/Actions tests
uses()->beforeEach(function ()
{
    // Disable all model events/observers
    \Illuminate\Support\Facades\Event::fake();

    // Seed permissions
    $this->seed(\Database\Seeders\TestPermissionsSeeder::class);

    // Re-enable events for the actual test
    \Illuminate\Support\Facades\Event::clearResolvedInstances();
})->in('Unit/Actions');

/*
|--------------------------------------------------------------------------
| Expectations
|--------------------------------------------------------------------------
|
| When you're writing tests, you often need to check that values meet certain conditions. The
| "expect()" function gives you access to a set of "expectations" methods that you can use
| to assert different things. Of course, you may extend the Expectation API at any time.
|
*/

expect()->extend('toBeOne', function ()
{
    return $this->toBe(1);
});

/*
|--------------------------------------------------------------------------
| Functions
|--------------------------------------------------------------------------
|
| While Pest is very powerful out-of-the-box, you may have some testing code specific to your
| project that you don't want to repeat in every file. Here you can also expose helpers as
| global functions to help you to reduce the number of lines of code in your test files.
|
*/

/**
 * Crea un usuario Superusuario para tests que requieren el sistema "listo".
 * 
 * Usar en tests de autenticación que esperan que las rutas estén habilitadas.
 */
function seedSuperuser(): \App\Models\User
{
    // Crear rol sin disparar observers
    \App\Models\Security\Role::withoutEvents(function ()
    {
        \App\Models\Security\Role::firstOrCreate(
            ['name' => 'Superusuario', 'guard_name' => 'web'],
            ['description' => 'Superusuario para tests']
        );
    });

    // Crear usuario sin disparar observers
    $user = \App\Models\User::factory()->create();
    $user->assignRole('Superusuario');

    return $user;
}
