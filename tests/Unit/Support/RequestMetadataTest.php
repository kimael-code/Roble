<?php

use App\Models\User;
use App\Support\RequestMetadata;
use Illuminate\Http\Request;

test('RequestMetadata::capture returns complete request metadata', function ()
{
    $request = Request::create(
        '/test-url',
        'POST',
        [],
        [],
        [],
        [
            'REMOTE_ADDR' => '192.168.1.1',
            'HTTP_USER_AGENT' => 'Mozilla/5.0',
            'HTTP_ACCEPT_LANGUAGE' => 'es-ES',
            'HTTP_REFERER' => 'https://example.com',
        ]
    );

    $metadata = RequestMetadata::capture($request);

    expect($metadata)->toBeArray()
        ->and($metadata)->toHaveKeys([
                'ip_address',
                'user_agent',
                'user_agent_lang',
                'referer',
                'http_method',
                'request_url',
            ])
        ->and($metadata['ip_address'])->toBe('192.168.1.1')
        ->and($metadata['user_agent'])->toBe('Mozilla/5.0')
        ->and($metadata['user_agent_lang'])->toBe('es-ES')
        ->and($metadata['referer'])->toBe('https://example.com')
        ->and($metadata['http_method'])->toBe('POST');
});

test('RequestMetadata::capture works without explicit request parameter', function ()
{
    // Simula una request global
    app()->instance('request', Request::create('/test', 'GET'));

    $metadata = RequestMetadata::capture();

    expect($metadata)->toBeArray()
        ->and($metadata['http_method'])->toBe('GET');
});

test('RequestMetadata::ip returns IP address', function ()
{
    $request = Request::create('/test', 'GET', [], [], [], [
        'REMOTE_ADDR' => '10.0.0.1',
    ]);

    $ip = RequestMetadata::ip($request);

    expect($ip)->toBe('10.0.0.1');
});

test('RequestMetadata::userAgent returns user agent', function ()
{
    $request = Request::create('/test', 'GET', [], [], [], [
        'HTTP_USER_AGENT' => 'TestAgent/1.0',
    ]);

    $userAgent = RequestMetadata::userAgent($request);

    expect($userAgent)->toBe('TestAgent/1.0');
});

test('RequestMetadata::userAgent returns user agent or Symfony default', function ()
{
    $request = Request::create('/test', 'GET');

    $userAgent = RequestMetadata::userAgent($request);

    // Symfony sets a default user agent if none is provided
    expect($userAgent)->toBeString();
});
