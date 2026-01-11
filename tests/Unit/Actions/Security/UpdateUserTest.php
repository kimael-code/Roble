<?php

use App\Actions\Security\UpdateUser;
use App\Models\Monitoring\ActivityLog;
use App\Models\Security\Permission;
use App\Models\Security\Role;
use App\Models\User;

uses(Tests\TestCase::class);
uses(Illuminate\Foundation\Testing\RefreshDatabase::class);

beforeEach(function ()
{
    $this->user = User::factory()->create();
    $this->actingAs($this->user);
});

test('UpdateUser updates user basic data', function ()
{
    $user = User::factory()->create([
        'name' => 'Old Name',
        'email' => 'old@example.com',
    ]);

    $inputs = [
        'name' => 'New Name',
        'email' => 'new@example.com',
        'is_external' => false,
        'roles' => [],
        'permissions' => [],
        'id_card' => null,
        'names' => null,
        'surnames' => null,
        'position' => null,
        'staff_type' => null,
        'ou_names' => [],
    ];

    $updateUser = app(UpdateUser::class);
    $updatedUser = $updateUser($user, $inputs);

    expect($updatedUser->name)->toBe('New Name')
        ->and($updatedUser->email)->toBe('new@example.com');
});

test('UpdateUser assigns new roles', function ()
{
    $user = User::factory()->create();
    $role = Role::create(['name' => 'Test Role', 'guard_name' => 'web', 'description' => 'Test role description']);

    $inputs = [
        'name' => $user->name,
        'email' => $user->email,
        'is_external' => false,
        'roles' => [$role->name],
        'permissions' => [],
        'id_card' => null,
        'names' => null,
        'surnames' => null,
        'position' => null,
        'staff_type' => null,
        'ou_names' => [],
    ];

    $updateUser = app(UpdateUser::class);
    $updatedUser = $updateUser($user, $inputs);

    expect($updatedUser->hasRole($role->name))->toBeTrue();
});

test('UpdateUser removes old roles', function ()
{
    $oldRole = Role::create(['name' => 'Old Role', 'guard_name' => 'web', 'description' => 'Old role']);
    $newRole = Role::create(['name' => 'New Role', 'guard_name' => 'web', 'description' => 'New role']);
    $user = User::factory()->create();
    $user->assignRole($oldRole);

    $inputs = [
        'name' => $user->name,
        'email' => $user->email,
        'is_external' => false,
        'roles' => [$newRole->name],
        'permissions' => [],
        'id_card' => null,
        'names' => null,
        'surnames' => null,
        'position' => null,
        'staff_type' => null,
        'ou_names' => [],
    ];

    $updateUser = app(UpdateUser::class);
    $updatedUser = $updateUser($user, $inputs);

    expect($updatedUser->hasRole($oldRole->name))->toBeFalse()
        ->and($updatedUser->hasRole($newRole->name))->toBeTrue();
});

test('UpdateUser logs activity when changed', function ()
{
    $user = User::factory()->create(['name' => 'Old Name']);

    $inputs = [
        'name' => 'New Name',
        'email' => $user->email,
        'is_external' => false,
        'roles' => [],
        'permissions' => [],
        'id_card' => null,
        'names' => null,
        'surnames' => null,
        'position' => null,
        'staff_type' => null,
        'ou_names' => [],
    ];

    $updateUser = app(UpdateUser::class);
    $updateUser($user, $inputs);

    $this->assertDatabaseHas('activity_log', [
        'log_name' => ActivityLog::LOG_NAMES['users'],
        'subject_type' => User::class,
        'subject_id' => $user->id,
        'event' => ActivityLog::EVENT_NAMES['updated'],
    ]);
});
