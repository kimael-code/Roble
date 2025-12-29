<?php

use App\Actions\Security\CreateUser;
use App\Models\Monitoring\ActivityLog;
use App\Models\Organization\OrganizationalUnit;
use App\Models\Person;
use App\Models\Security\Permission;
use App\Models\Security\Role;
use App\Models\User;
use App\Notifications\UserActivationMail;
use App\Support\ActivityLogger;
use Illuminate\Support\Facades\Notification;

uses(Tests\TestCase::class);
uses(Illuminate\Foundation\Testing\RefreshDatabase::class);

beforeEach(function ()
{
    $this->user = User::factory()->create();
    $this->actingAs($this->user);
});

test('CreateUser creates a user with basic data', function ()
{
    $role = Role::create(['name' => 'Test Role', 'guard_name' => 'web', 'description' => 'Test role']);
    $permission = Permission::create(['name' => 'test permission', 'guard_name' => 'web', 'description' => 'Test permission']);

    $inputs = [
        'name' => 'Test User',
        'email' => 'test@example.com',
        'is_external' => false,
        'roles' => [$role->name],
        'permissions' => [$permission->description],
        'id_card' => null,
        'names' => null,
        'surnames' => null,
        'position' => null,
        'staff_type' => null,
        'ou_names' => null,
    ];

    $createUser = app(CreateUser::class);
    $user = $createUser($inputs);

    expect($user)->toBeInstanceOf(User::class)
        ->and($user->name)->toBe('Test User')
        ->and($user->email)->toBe('test@example.com')
        ->and($user->is_external)->toBeFalse()
        ->and($user->hasRole($role->name))->toBeTrue()
        ->and($user->hasPermissionTo($permission))->toBeTrue();
});

test('CreateUser creates a user with person data', function ()
{
    $role = Role::create(['name' => 'Test Role', 'guard_name' => 'web', 'description' => 'Test role']);

    $inputs = [
        'name' => 'Test User',
        'email' => 'test@example.com',
        'is_external' => false,
        'roles' => [$role->name],
        'permissions' => [],
        'id_card' => '12345678',
        'names' => 'John',
        'surnames' => 'Doe',
        'position' => 'Developer',
        'staff_type' => 'Full-time',
        'ou_names' => null,
    ];

    $createUser = app(CreateUser::class);
    $user = $createUser($inputs);

    expect($user->person)->toBeInstanceOf(Person::class)
        ->and($user->person->id_card)->toBe('12345678')
        ->and($user->person->names)->toBe('John')
        ->and($user->person->surnames)->toBe('Doe');
});

test('CreateUser sends activation email', function ()
{
    Notification::fake();

    $role = Role::create(['name' => 'Test Role', 'guard_name' => 'web', 'description' => 'Test role']);

    $inputs = [
        'name' => 'Test User',
        'email' => 'test@example.com',
        'is_external' => false,
        'roles' => [$role->name],
        'permissions' => [],
        'id_card' => null,
        'names' => null,
        'surnames' => null,
        'position' => null,
        'staff_type' => null,
        'ou_names' => null,
    ];

    $createUser = app(CreateUser::class);
    $user = $createUser($inputs);

    Notification::assertSentTo($user, UserActivationMail::class);
});

test('CreateUser logs activity', function ()
{
    $role = Role::create(['name' => 'Test Role', 'guard_name' => 'web', 'description' => 'Test role']);

    $inputs = [
        'name' => 'Test User',
        'email' => 'test@example.com',
        'is_external' => false,
        'roles' => [$role->name],
        'permissions' => [],
        'id_card' => null,
        'names' => null,
        'surnames' => null,
        'position' => null,
        'staff_type' => null,
        'ou_names' => null,
    ];

    $createUser = app(CreateUser::class);
    $user = $createUser($inputs);

    $this->assertDatabaseHas('activity_log', [
        'log_name' => ActivityLog::LOG_NAMES['users'],
        'subject_type' => User::class,
        'subject_id' => $user->id,
        'event' => ActivityLog::EVENT_NAMES['created'],
    ]);
});
