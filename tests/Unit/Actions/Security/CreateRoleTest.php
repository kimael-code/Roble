<?php

use App\Actions\Security\CreateRole;
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

test('CreateRole creates a role with permissions', function ()
{
    $permission1 = Permission::create(['name' => 'test permission 1', 'guard_name' => 'web', 'description' => 'Test permission 1']);
    $permission2 = Permission::create(['name' => 'test permission 2', 'guard_name' => 'web', 'description' => 'Test permission 2']);

    $inputs = [
        'name' => 'Test Role',
        'guard_name' => 'web',
        'description' => 'Test role description',
        'permissions' => [$permission1->description, $permission2->description],
    ];

    $createRole = app(CreateRole::class);
    $role = $createRole($inputs);

    expect($role)->toBeInstanceOf(Role::class)
        ->and($role->name)->toBe('Test Role')
        ->and($role->description)->toBe('Test role description')
        ->and($role->hasPermissionTo($permission1))->toBeTrue()
        ->and($role->hasPermissionTo($permission2))->toBeTrue();
});

test('CreateRole logs activity for each permission', function ()
{
    $permission = Permission::create(['name' => 'test permission', 'guard_name' => 'web', 'description' => 'Test permission']);

    $inputs = [
        'name' => 'Test Role',
        'guard_name' => 'web',
        'description' => 'Test role',
        'permissions' => [$permission->description],
    ];

    $createRole = app(CreateRole::class);
    $role = $createRole($inputs);

    $this->assertDatabaseHas('activity_log', [
        'log_name' => ActivityLog::LOG_NAMES['roles'],
        'subject_type' => Role::class,
        'subject_id' => $role->id,
        'event' => ActivityLog::EVENT_NAMES['authorized'],
    ]);
});
