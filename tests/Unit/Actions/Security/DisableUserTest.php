<?php

use App\Actions\Security\DisableUser;
use App\Models\Monitoring\ActivityLog;
use App\Models\Security\Permission;
use App\Models\User;

uses(Tests\TestCase::class);
uses(Illuminate\Foundation\Testing\RefreshDatabase::class);

beforeEach(function ()
{
    $this->user = User::factory()->create();
    $this->actingAs($this->user);
});

test('DisableUser disables a user', function ()
{
    $user = User::factory()->create(['disabled_at' => null]);

    $disableUser = app(DisableUser::class);
    $disableUser($user);

    $user->refresh();

    expect($user->disabled_at)->not->toBeNull();
});

test('DisableUser logs activity', function ()
{
    $user = User::factory()->create(['disabled_at' => null]);

    $disableUser = app(DisableUser::class);
    $disableUser($user);

    $this->assertDatabaseHas('activity_log', [
        'log_name' => ActivityLog::LOG_NAMES['users'],
        'subject_type' => User::class,
        'subject_id' => $user->id,
        'event' => ActivityLog::EVENT_NAMES['disabled'],
    ]);
});

test('DisableUser sets flash message', function ()
{
    $user = User::factory()->create(['disabled_at' => null, 'name' => 'Test User']);

    $disableUser = app(DisableUser::class);
    $disableUser($user);

    expect(session('message'))->toBeArray()
        ->and(session('message')['type'])->toBe('warning')
        ->and(session('message')['content'])->toContain('Test User');
});
