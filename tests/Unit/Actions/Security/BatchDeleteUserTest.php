<?php

use App\Actions\Security\BatchDeleteUser;
use App\Models\Security\Role;
use App\Models\User;

uses(Tests\TestCase::class);
uses(Illuminate\Foundation\Testing\RefreshDatabase::class);

beforeEach(function ()
{
    $this->user = User::factory()->create();
    $this->actingAs($this->user);
});

test('BatchDeleteUser deletes selected users', function ()
{
    $user1 = User::factory()->create();
    $user2 = User::factory()->create();
    $user3 = User::factory()->create();

    $ids = [
        $user1->id => true,
        $user2->id => true,
        $user3->id => false, // Not selected
    ];

    $batchDeleteUser = app(BatchDeleteUser::class);
    $result = $batchDeleteUser($ids);

    expect($result)->toBeArray()
        ->and($result['type'])->toBe('success')
        ->and(User::withTrashed()->find($user1->id)->trashed())->toBeTrue()
        ->and(User::withTrashed()->find($user2->id)->trashed())->toBeTrue()
        ->and(User::find($user3->id))->not->toBeNull();
});

test('BatchDeleteUser prevents deleting own account', function ()
{
    $ids = [
        $this->user->id => true,
    ];

    $batchDeleteUser = app(BatchDeleteUser::class);
    $result = $batchDeleteUser($ids);

    expect($result['type'])->toBe('warning')
        ->and(User::find($this->user->id))->not->toBeNull();
});

test('BatchDeleteUser prevents deleting active superuser', function ()
{
    $superuser = User::factory()->create(['disabled_at' => null]);
    $superuser->assignRole(Role::create(['name' => 'Superusuario', 'guard_name' => 'web', 'description' => 'Superuser role']));

    $ids = [
        $superuser->id => true,
    ];

    $batchDeleteUser = app(BatchDeleteUser::class);
    $result = $batchDeleteUser($ids);

    expect($result['type'])->toBe('warning')
        ->and(User::find($superuser->id))->not->toBeNull();
});
