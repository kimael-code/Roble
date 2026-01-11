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

test('export permissions pdf genera archivo válido', function ()
{
    $this->actingAs($this->user);

    Permission::create(['name' => 'test_permission_1', 'description' => 'permiso de prueba', 'guard_name' => 'web']);

    $exporter = new ExportPermissionsToPdf(filters: []);
    $pdfContent = $exporter->make('S');

    expect($pdfContent)->not->toBeEmpty();
    expect($pdfContent)->toStartWith('%PDF');
});

test('export permissions pdf funciona con datos vacíos', function ()
{
    $this->actingAs($this->user);

    $exporter = new ExportPermissionsToPdf(filters: []);
    $pdfContent = $exporter->make('S');

    expect($pdfContent)->not->toBeEmpty();
    expect($pdfContent)->toStartWith('%PDF');
});

test('export permissions pdf aplica filtros', function ()
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

test('export roles pdf genera archivo válido', function ()
{
    $this->actingAs($this->user);

    Role::create(['name' => 'test_role', 'description' => 'rol de prueba', 'guard_name' => 'web']);

    $exporter = new ExportRolesToPdf(filters: []);
    $pdfContent = $exporter->make('S');

    expect($pdfContent)->not->toBeEmpty();
    expect($pdfContent)->toStartWith('%PDF');
});

test('export roles pdf funciona con datos vacíos', function ()
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

test('export users pdf genera archivo válido', function ()
{
    $this->actingAs($this->user);

    User::factory()->count(2)->create();

    $exporter = new ExportUsersToPdf(filters: []);
    $pdfContent = $exporter->make('S');

    expect($pdfContent)->not->toBeEmpty();
    expect($pdfContent)->toStartWith('%PDF');
});

test('export users pdf funciona con filtros', function ()
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

test('export activity logs pdf genera archivo válido', function ()
{
    $this->actingAs($this->user);

    $exporter = new ExportActivityLogsToPdf(filters: []);
    $pdfContent = $exporter->make('S');

    expect($pdfContent)->not->toBeEmpty();
    expect($pdfContent)->toStartWith('%PDF');
});

test('export activity logs pdf funciona con filtros', function ()
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

test('configuración de versión existe', function ()
{
    $version = config('app.version');

    expect($version)->not->toBeNull();
    expect($version)->toBeString();
});

test('BasePdf tiene colores institucionales definidos', function ()
{
    $reflection = new \ReflectionClass(\App\Support\DataExport\BasePdf::class);

    expect($reflection->hasConstant('COLOR_AZUL_COBALTO'))->toBeTrue();
    expect($reflection->hasConstant('COLOR_AZUL_CERULEO'))->toBeTrue();
    expect($reflection->hasConstant('COLOR_AZUL_CLARO'))->toBeTrue();
});
