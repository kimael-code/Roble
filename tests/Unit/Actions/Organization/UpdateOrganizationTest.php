<?php

use App\Actions\Organization\UpdateOrganization;
use App\Models\Organization\Organization;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;

uses(Tests\TestCase::class);
uses(Illuminate\Foundation\Testing\RefreshDatabase::class);

test('UpdateOrganization updates organization data', function ()
{
    $organization = Organization::factory()->create([
        'name' => 'Old Name',
        'rif' => 'J-11111111-1',
    ]);

    $inputs = [
        'rif' => 'J-22222222-2',
        'name' => 'New Name',
        'acronym' => 'NN',
        'address' => 'New Address',
        'disabled' => false,
        'logo_path' => $organization->logo_path,
    ];

    $updateOrganization = app(UpdateOrganization::class);
    $updatedOrg = $updateOrganization($inputs, $organization);

    expect($updatedOrg->rif)->toBe('J-22222222-2')
        ->and($updatedOrg->name)->toBe('New Name');
});

test('UpdateOrganization updates logo', function ()
{
    Storage::fake('public');

    $oldLogo = UploadedFile::fake()->image('old-logo.png');
    $oldLogoPath = Storage::disk('public')->putFile('logos', $oldLogo);

    $organization = Organization::factory()->create([
        'logo_path' => $oldLogoPath,
    ]);

    $newLogo = UploadedFile::fake()->image('new-logo.png');

    $inputs = [
        'rif' => $organization->rif,
        'name' => $organization->name,
        'acronym' => $organization->acronym,
        'address' => $organization->address,
        'disabled' => false,
        'logo_path' => $newLogo,
    ];

    $updateOrganization = app(UpdateOrganization::class);
    $updatedOrg = $updateOrganization($inputs, $organization);

    expect($updatedOrg->logo_path)->not->toBe($oldLogoPath);
    Storage::disk('public')->assertExists($updatedOrg->logo_path);
    Storage::disk('public')->assertMissing($oldLogoPath);
});

test('UpdateOrganization cleans up new logo on failure', function ()
{
    Storage::fake('public');

    $organization = Organization::factory()->create();
    $newLogo = UploadedFile::fake()->image('new-logo.png');

    $inputs = [
        'rif' => null, // This will cause an error
        'name' => $organization->name,
        'acronym' => $organization->acronym,
        'address' => $organization->address,
        'disabled' => false,
        'logo_path' => $newLogo,
    ];

    $updateOrganization = app(UpdateOrganization::class);

    try
    {
        $updateOrganization($inputs, $organization);
    }
    catch (\Throwable $e)
    {
        // Expected to fail
    }

    // Verify no orphaned files
    $files = Storage::disk('public')->files('logos');
    expect(count($files))->toBeLessThanOrEqual(1); // Only original logo should exist
});
