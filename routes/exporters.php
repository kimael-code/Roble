<?php

use App\Http\Controllers\Exporters\ActivityLogExporterController;
use App\Http\Controllers\Exporters\PermissionExporterController;
use App\Http\Controllers\Exporters\RoleExporterController;
use App\Http\Controllers\Exporters\UserExporterController;
use Illuminate\Support\Facades\Route;

Route::middleware(['auth', 'verified', 'activated'])->group(function ()
{
    // Activity Logs (solo PDF)
    Route::get('export/activity-logs/pdf', [ActivityLogExporterController::class, 'indexToPdf'])
        ->name('export-activity-logs-pdf.index');

    // Permissions (PDF, Excel, JSON)
    Route::get('export/permissions/pdf', [PermissionExporterController::class, 'indexToPdf'])
        ->name('export-permissions-pdf.index');
    Route::get('export/permissions/excel', [PermissionExporterController::class, 'indexToExcel'])
        ->name('export-permissions-excel.index');
    Route::get('export/permissions/json', [PermissionExporterController::class, 'indexToJson'])
        ->name('export-permissions-json.index');

    // Users (PDF, Excel, JSON)
    Route::get('export/users/pdf', [UserExporterController::class, 'indexToPdf'])
        ->name('export-users-pdf.index');
    Route::get('export/users/excel', [UserExporterController::class, 'indexToExcel'])
        ->name('export-users-excel.index');
    Route::get('export/users/json', [UserExporterController::class, 'indexToJson'])
        ->name('export-users-json.index');

    // Roles (PDF, Excel, JSON)
    Route::get('export/roles/pdf', [RoleExporterController::class, 'indexToPdf'])
        ->name('export-roles-pdf.index');
    Route::get('export/roles/excel', [RoleExporterController::class, 'indexToExcel'])
        ->name('export-roles-excel.index');
    Route::get('export/roles/json', [RoleExporterController::class, 'indexToJson'])
        ->name('export-roles-json.index');
});