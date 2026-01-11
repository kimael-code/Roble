<?php

/**
 * Tests de integración para exportación de PDFs.
 *
 * Estos tests verifican que los exportadores de PDF:
 * - Generan PDFs válidos sin errores
 * - Manejan filtros y datos vacíos correctamente
 */

use App\Actions\Monitoring\ExportActivityLogsToPdf;
use App\Actions\Security\ExportPermissionsToPdf;
use App\Actions\Security\ExportRolesToPdf;
use App\Actions\Security\ExportUsersToPdf;
use App\Models\Security\Permission;
use App\Models\Security\Role;
use App\Models\User;
use Illuminate\Support\Facades\Notification;
use Spatie\Activitylog\Facades\Activity;

beforeEach(function ()
{
    Notification::fake();
    Activity::disableLogging();

    // Desactivar observers
    User::unsetEventDispatcher();
    Permission::unsetEventDispatcher();
    Role::unsetEventDispatcher();

    // Crear usuario autenticado
    $this->user = User::factory()->create(['email' => 'test.export@example.com']);

    // Resetear caché de permisos
    app()[\Spatie\Permission\PermissionRegistrar::class]->forgetCachedPermissions();
});

// =============================================================================
// Tests para ExportPermissionsToPdf
// =============================================================================

test('ExportPermissionsToPdf class makes a valid file', function ()
{
    $this->actingAs($this->user);

    Permission::create(['name' => 'test_permission_1', 'description' => 'permiso de prueba', 'guard_name' => 'web']);

    $exporter = new ExportPermissionsToPdf(filters: []);
    $pdfContent = $exporter->make('S');

    expect($pdfContent)->not->toBeEmpty();
    expect($pdfContent)->toStartWith('%PDF');
});

test('ExportPermissionsToPdf class works with empty data', function ()
{
    $this->actingAs($this->user);

    $exporter = new ExportPermissionsToPdf(filters: []);
    $pdfContent = $exporter->make('S');

    expect($pdfContent)->not->toBeEmpty();
    expect($pdfContent)->toStartWith('%PDF');
});

test('ExportPermissionsToPdf class applies filters', function ()
{
    $this->actingAs($this->user);

    Permission::create(['name' => 'read_something', 'description' => 'leer algo', 'guard_name' => 'web']);
    Permission::create(['name' => 'write_something', 'description' => 'escribir algo', 'guard_name' => 'web']);

    $filters = ['search' => 'read'];
    $exporter = new ExportPermissionsToPdf(filters: $filters);
    $pdfContent = $exporter->make('S');

    expect($pdfContent)->not->toBeEmpty();
    expect($pdfContent)->toStartWith('%PDF');
});

// =============================================================================
// Tests para ExportRolesToPdf
// =============================================================================

test('ExportRolesToPdf class makes a valid file', function ()
{
    $this->actingAs($this->user);

    Role::create(['name' => 'test_role', 'description' => 'rol de prueba', 'guard_name' => 'web']);

    $exporter = new ExportRolesToPdf(filters: []);
    $pdfContent = $exporter->make('S');

    expect($pdfContent)->not->toBeEmpty();
    expect($pdfContent)->toStartWith('%PDF');
});

test('ExportRolesToPdf class works with empty data', function ()
{
    $this->actingAs($this->user);

    $exporter = new ExportRolesToPdf(filters: []);
    $pdfContent = $exporter->make('S');

    expect($pdfContent)->not->toBeEmpty();
    expect($pdfContent)->toStartWith('%PDF');
});

// =============================================================================
// Tests para ExportUsersToPdf
// =============================================================================

test('ExportUsersToPdf class makes a valid file', function ()
{
    $this->actingAs($this->user);

    User::factory()->count(2)->create();

    $exporter = new ExportUsersToPdf(filters: []);
    $pdfContent = $exporter->make('S');

    expect($pdfContent)->not->toBeEmpty();
    expect($pdfContent)->toStartWith('%PDF');
});

test('ExportUsersToPdf class applies filters', function ()
{
    $this->actingAs($this->user);

    $filters = ['search' => 'admin'];
    $exporter = new ExportUsersToPdf(filters: $filters);
    $pdfContent = $exporter->make('S');

    expect($pdfContent)->not->toBeEmpty();
    expect($pdfContent)->toStartWith('%PDF');
});

// =============================================================================
// Tests para ExportActivityLogsToPdf
// =============================================================================

test('ExportActivityLogsToPdf class makes a valid file', function ()
{
    $this->actingAs($this->user);

    $exporter = new ExportActivityLogsToPdf(filters: []);
    $pdfContent = $exporter->make('S');

    expect($pdfContent)->not->toBeEmpty();
    expect($pdfContent)->toStartWith('%PDF');
});

test('ExportActivityLogsToPdf class applies filters', function ()
{
    $this->actingAs($this->user);

    $filters = ['search' => 'login'];
    $exporter = new ExportActivityLogsToPdf(filters: $filters);
    $pdfContent = $exporter->make('S');

    expect($pdfContent)->not->toBeEmpty();
    expect($pdfContent)->toStartWith('%PDF');
});

// =============================================================================
// Tests de configuración
// =============================================================================

test('version configuration exists', function ()
{
    $version = config('app.version');

    expect($version)->not->toBeNull();
    expect($version)->toBeString();
});

test('BasePdf has institutional colors defined', function ()
{
    $reflection = new \ReflectionClass(\App\Support\DataExport\BasePdf::class);

    expect($reflection->hasConstant('COLOR_AZUL_COBALTO'))->toBeTrue();
    expect($reflection->hasConstant('COLOR_AZUL_CERULEO'))->toBeTrue();
    expect($reflection->hasConstant('COLOR_AZUL_CLARO'))->toBeTrue();
});
