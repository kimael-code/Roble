<?php

use App\Models\Monitoring\ActivityLog;
use App\Models\User;
use App\Support\ActivityLogger;
use Database\Seeders\Auth\RolesAndPermissionsSeeder;
use Spatie\Activitylog\Models\Activity;

uses(Tests\TestCase::class);
uses(Illuminate\Foundation\Testing\RefreshDatabase::class);

beforeEach(function ()
{
    // Seed permissions and roles required by UserObserver
    $this->seed(RolesAndPermissionsSeeder::class);

    // Crear un usuario de prueba
    $this->user = User::factory()->create();
    $this->actingAs($this->user);
});

test('ActivityLogger can be invoked to log activity', function ()
{
    $logger = new ActivityLogger();
    $subject = User::factory()->create();

    $activity = $logger(
        ActivityLog::LOG_NAMES['users'],
        $subject,
        ActivityLog::EVENT_NAMES['created'],
        'test message',
        ['custom_property' => 'value']
    );

    expect($activity)->toBeInstanceOf(Activity::class)
        ->and($activity->log_name)->toBe(ActivityLog::LOG_NAMES['users'])
        ->and($activity->event)->toBe(ActivityLog::EVENT_NAMES['created'])
        ->and($activity->description)->toBe('test message')
        ->and($activity->properties)->toHaveKey('custom_property')
        ->and($activity->properties['custom_property'])->toBe('value')
        ->and($activity->properties)->toHaveKey('request')
        ->and($activity->properties)->toHaveKey('causer');
});

test('ActivityLogger::logCreated logs creation with attributes', function ()
{
    $logger = new ActivityLogger();
    $subject = User::factory()->create(['name' => 'Test User']);

    $activity = $logger->logCreated(
        ActivityLog::LOG_NAMES['users'],
        $subject,
        'created test user'
    );

    expect($activity)->toBeInstanceOf(Activity::class)
        ->and($activity->event)->toBe(ActivityLog::EVENT_NAMES['created'])
        ->and($activity->description)->toBe('created test user')
        ->and($activity->properties)->toHaveKey('attributes')
        ->and($activity->properties['attributes'])->toHaveKey('name')
        ->and($activity->properties['attributes']['name'])->toBe('Test User');
});

test('ActivityLogger::logUpdated logs update with changes and old values', function ()
{
    $logger = new ActivityLogger();
    $subject = User::factory()->create(['name' => 'Old Name']);

    $subject->name = 'New Name';
    $subject->save();

    $activity = $logger->logUpdated(
        ActivityLog::LOG_NAMES['users'],
        $subject,
        'updated test user'
    );

    expect($activity)->toBeInstanceOf(Activity::class)
        ->and($activity->event)->toBe(ActivityLog::EVENT_NAMES['updated'])
        ->and($activity->description)->toBe('updated test user')
        ->and($activity->properties)->toHaveKey('attributes')
        ->and($activity->properties)->toHaveKey('old');
});

test('ActivityLogger::logDeleted logs deletion', function ()
{
    $logger = new ActivityLogger();
    $subject = User::factory()->create();

    $activity = $logger->logDeleted(
        ActivityLog::LOG_NAMES['users'],
        $subject,
        'deleted test user'
    );

    expect($activity)->toBeInstanceOf(Activity::class)
        ->and($activity->event)->toBe(ActivityLog::EVENT_NAMES['deleted'])
        ->and($activity->description)->toBe('deleted test user');
});

test('ActivityLogger::logAuthorized logs authorization action', function ()
{
    $logger = new ActivityLogger();
    $subject = User::factory()->create();

    $activity = $logger->logAuthorized(
        ActivityLog::LOG_NAMES['users'],
        $subject,
        'assigned role to user',
        ['role' => 'admin']
    );

    expect($activity)->toBeInstanceOf(Activity::class)
        ->and($activity->event)->toBe(ActivityLog::EVENT_NAMES['authorized'])
        ->and($activity->description)->toBe('assigned role to user')
        ->and($activity->properties)->toHaveKey('role')
        ->and($activity->properties['role'])->toBe('admin');
});

test('ActivityLogger::logDisabled logs disable action', function ()
{
    $logger = new ActivityLogger();
    $subject = User::factory()->create();

    $activity = $logger->logDisabled(
        ActivityLog::LOG_NAMES['users'],
        $subject,
        'disabled user'
    );

    expect($activity)->toBeInstanceOf(Activity::class)
        ->and($activity->event)->toBe(ActivityLog::EVENT_NAMES['disabled'])
        ->and($activity->description)->toBe('disabled user');
});

test('ActivityLogger::logEnabled logs enable action', function ()
{
    $logger = new ActivityLogger();
    $subject = User::factory()->create();

    $activity = $logger->logEnabled(
        ActivityLog::LOG_NAMES['users'],
        $subject,
        'enabled user'
    );

    expect($activity)->toBeInstanceOf(Activity::class)
        ->and($activity->event)->toBe(ActivityLog::EVENT_NAMES['enabled'])
        ->and($activity->description)->toBe('enabled user');
});

test('ActivityLogger includes request metadata in all logs', function ()
{
    $logger = new ActivityLogger();
    $subject = User::factory()->create();

    $activity = $logger->logCreated(
        ActivityLog::LOG_NAMES['users'],
        $subject,
        'test'
    );

    expect($activity->properties)->toHaveKey('request')
        ->and($activity->properties['request'])->toHaveKeys([
                'ip_address',
                'user_agent',
                'user_agent_lang',
                'referer',
                'http_method',
                'request_url',
            ]);
});

test('ActivityLogger includes causer information in all logs', function ()
{
    $logger = new ActivityLogger();
    $subject = User::factory()->create();

    $activity = $logger->logCreated(
        ActivityLog::LOG_NAMES['users'],
        $subject,
        'test'
    );

    expect($activity->properties)->toHaveKey('causer')
        ->and($activity->causer_id)->toBe($this->user->id);
});
