<?php

namespace App\Http\Controllers\Exporters;

use App\Actions\Security\ExportPermissionsToPdf;
use App\Exports\PermissionsExport;
use App\Http\Controllers\Controller;
use App\Models\Security\Permission;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Maatwebsite\Excel\Facades\Excel;
use Symfony\Component\HttpFoundation\BinaryFileResponse;

class PermissionExporterController extends Controller
{
    public function indexToPdf(Request $request): string
    {
        if ($request->user()->cannot('export permissions'))
        {
            abort(403);
        }

        return (new ExportPermissionsToPdf(filters: $request->all()))->make();
    }

    public function indexToExcel(Request $request): BinaryFileResponse
    {
        if ($request->user()->cannot('export permissions'))
        {
            abort(403);
        }

        $filename = 'PERMISOS_' . now()->format('Y-m-d_His') . '.xlsx';

        return Excel::download(new PermissionsExport($request->all()), $filename);
    }

    public function indexToJson(Request $request): JsonResponse
    {
        if ($request->user()->cannot('export permissions'))
        {
            abort(403);
        }

        $permissions = Permission::filter($request->all())
            ->with(['roles:id,name', 'users:id,email'])
            ->get();

        return response()->json([
            'data' => $permissions,
            'generated_at' => now()->isoFormat('L LTS'),
            'generated_by' => $request->user()->email,
            'filters' => $request->all(),
        ]);
    }
}
