<?php

namespace App\Http\Controllers\Monitoring;

use App\Http\Controllers\Controller;
use App\Http\Requests\Monitoring\ToggleMaintenanceModeRequest;
use Illuminate\Support\Facades\Artisan;
use Inertia\Inertia;

class MaintenanceController extends Controller
{
    public function index()
    {
        return Inertia::render('monitoring/maintenance-mode/Index', [
            'status' => app()->isDownForMaintenance(),
        ]);
    }

    public function toggle(ToggleMaintenanceModeRequest $request)
    {
        if (app()->isDownForMaintenance())
        {
            Artisan::call('up');

            return back()->with('message', [
                'content' => 'Modo mantenimiento desactivado',
                'title' => '¡PROCESADO!',
                'type' => 'success',
            ]);
        }

        Artisan::call('down', [
            '--secret' => $request->safe()->secret ?: null,
        ]);

        return back()->with('message', [
            'content' => 'Modo mantenimiento activado',
            'title' => '¡PROCESADO!',
            'type' => 'success',
        ]);
    }
}
