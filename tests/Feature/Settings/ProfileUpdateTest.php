<?php

use App\Models\User;

test('profile page is displayed', function ()
{
    $user = User::factory()->create();

    $response = $this
        ->actingAs($user)
        ->get(route('profile.edit'));

    $response->assertOk();
});

test('profile information can not be updated', function ()
{
    $user = User::factory()->create();

    $response = $this
        ->actingAs($user)
        ->patch(route('profile.update'), [
            'name' => 'test.user',
            'email' => 'test@example.com',
        ]);

    $response
        ->assertForbidden();
});

test('user can not delete their account', function ()
{
    User::withoutEvents(function ()
    {
        $user = User::factory()->create();

        $response = $this
            ->actingAs($user)
            ->delete(route('profile.destroy'), [
                'password' => 'password',
            ]);

        $response->assertForbidden();
    });
});