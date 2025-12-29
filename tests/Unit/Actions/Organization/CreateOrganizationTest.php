<?php

use App\Actions\Organization\CreateOrganization;
use App\Models\Organization\Organization;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;

uses(Tests\TestCase::class);
uses(Illuminate\Foundation\Testing\RefreshDatabase::class);

test('CreateOrganization creates a new organization', function ()
{
    Storage::fake('public');

    $logo = UploadedFile::fake()->image('logo.png');

    $inputs = [
        'rif' => 'J-12345678-9',
        'name' => 'Test Organization',
        'acronym' => 'TO',
        'address' => '123 Test St',
        'logo_path' => $logo,
    ];

    $createOrganization = app(CreateOrganization::class);
    $organization = $createOrganization($inputs);

    expect($organization)->toBeInstanceOf(Organization::class)
        ->and($organization->rif)->toBe('J-12345678-9')
        ->and($organization->name)->toBe('Test Organization')
        ->and($organization->acronym)->toBe('TO')
        ->and($organization->logo_path)->not->toBeNull();

    Storage::disk('public')->assertExists($organization->logo_path);
});

test('CreateOrganization disables previous organizations', function ()
{
    Storage::fake('public');

    $oldOrganization = Organization::factory()->create(['disabled_at' => null]);

    $logo = UploadedFile::fake()->image('logo.png');

    $inputs = [
        'rif' => 'J-98765432-1',
        'name' => 'New Organization',
        'acronym' => 'NO',
        'address' => '456 New St',
        'logo_path' => $logo,
    ];

    $createOrganization = app(CreateOrganization::class);
    $createOrganization($inputs);

    $oldOrganization->refresh();

    expect($oldOrganization->disabled_at)->not->toBeNull();
});

test('CreateOrganization cleans up logo on failure', function ()
{
    Storage::fake('public');

    $logo = UploadedFile::fake()->image('logo.png');

    $inputs = [
        'rif' => null, // This will cause a validation error
        'name' => 'Test Organization',
        'acronym' => 'TO',
        'address' => '123 Test St',
        'logo_path' => $logo,
    ];

    $createOrganization = app(CreateOrganization::class);

    try
    {
        $createOrganization($inputs);
    }
    catch (\Throwable $e)
    {
        // Expected to fail
    }

    // Verify no orphaned files
    $files = Storage::disk('public')->files('logos');
    expect($files)->toBeEmpty();
});
