<?php

namespace App\Http\Controllers\Exporters;

use App\Actions\Security\ExportRolesToPdf;
use App\Exports\RolesExport;
use App\Http\Controllers\Controller;
use App\Models\Security\Role;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Maatwebsite\Excel\Facades\Excel;
use Symfony\Component\HttpFoundation\BinaryFileResponse;

class RoleExporterController extends Controller
{
    public function indexToPdf(Request $request): string
    {
        if ($request->user()->cannot('export roles'))
        {
            abort(403);
        }

        return (new ExportRolesToPdf(filters: $request->all()))->make();
    }

    public function indexToExcel(Request $request): BinaryFileResponse
    {
        if ($request->user()->cannot('export roles'))
        {
            abort(403);
        }

        $filename = 'ROLES_' . now()->format('Y-m-d_His') . '.xlsx';

        return Excel::download(new RolesExport($request->all()), $filename);
    }

    public function indexToJson(Request $request): JsonResponse
    {
        if ($request->user()->cannot('export roles'))
        {
            abort(403);
        }

        $roles = Role::filter($request->all())
            ->with('permissions:id,name,description')
            ->get();

        return response()->json([
            'data' => $roles,
            'generated_at' => now()->isoFormat('L LTS'),
            'generated_by' => $request->user()->email,
            'filters' => $request->all(),
        ]);
    }
}
