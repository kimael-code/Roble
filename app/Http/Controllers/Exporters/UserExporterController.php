<?php

namespace App\Http\Controllers\Exporters;

use App\Actions\Security\ExportUsersToPdf;
use App\Exports\UsersExport;
use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Maatwebsite\Excel\Facades\Excel;
use Symfony\Component\HttpFoundation\BinaryFileResponse;

class UserExporterController extends Controller
{
    public function indexToPdf(Request $request): string
    {
        if ($request->user()->cannot('export users'))
        {
            abort(403);
        }

        return (new ExportUsersToPdf(filters: $request->all()))->make();
    }

    public function indexToExcel(Request $request): BinaryFileResponse
    {
        if ($request->user()->cannot('export users'))
        {
            abort(403);
        }

        $filename = 'USUARIOS_' . now()->format('Y-m-d_His') . '.xlsx';

        return Excel::download(new UsersExport($request->all()), $filename);
    }

    public function indexToJson(Request $request): JsonResponse
    {
        if ($request->user()->cannot('export users'))
        {
            abort(403);
        }

        $users = User::filter($request->all())
            ->with('roles:id,name')
            ->get();

        return response()->json([
            'data' => $users,
            'generated_at' => now()->isoFormat('L LTS'),
            'generated_by' => $request->user()->email,
            'filters' => $request->all(),
        ]);
    }
}
