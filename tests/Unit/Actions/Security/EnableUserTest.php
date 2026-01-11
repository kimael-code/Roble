<?php

use App\Actions\Security\EnableUser;
use App\Models\Monitoring\ActivityLog;
use App\Models\User;

uses(Tests\TestCase::class);
uses(Illuminate\Foundation\Testing\RefreshDatabase::class);

beforeEach(function ()
{
    $this->user = User::factory()->create();
    $this->actingAs($this->user);
});

test('EnableUser enables a disabled user', function ()
{
    $user = User::factory()->create(['disabled_at' => now()]);

    $enableUser = app(EnableUser::class);
    $enableUser($user);

    $user->refresh();

    expect($user->disabled_at)->toBeNull();
});

test('EnableUser restores soft-deleted user', function ()
{
    $user = User::factory()->create();
    $user->delete();

    $enableUser = app(EnableUser::class);
    $enableUser($user);

    $user->refresh();

    expect($user->deleted_at)->toBeNull();
});

test('EnableUser resets password', function ()
{
    $user = User::factory()->create(['disabled_at' => now()]);
    $oldPassword = $user->password;

    $enableUser = app(EnableUser::class);
    $enableUser($user);

    $user->refresh();

    expect($user->password)->not->toBe($oldPassword);
});

test('EnableUser logs activity', function ()
{
    $user = User::factory()->create(['disabled_at' => now()]);

    $enableUser = app(EnableUser::class);
    $enableUser($user);

    $this->assertDatabaseHas('activity_log', [
        'log_name' => ActivityLog::LOG_NAMES['users'],
        'subject_type' => User::class,
        'subject_id' => $user->id,
        'event' => ActivityLog::EVENT_NAMES['enabled'],
    ]);
});
