<?php

namespace App\Http\Controllers\Monitoring;

use App\Actions\Monitoring\DeleteLogFile;
use App\Actions\Monitoring\ExportLogFile;
use App\Http\Controllers\Controller;
use App\InertiaProps\Monitoring\LogFileIndexProps;
use Inertia\Inertia;

class LogFileController extends Controller
{
    public function index(LogFileIndexProps $props)
    {
        return Inertia::render('monitoring/log-files/Index', $props->toArray());
    }

    public function export(string $file, ExportLogFile $exportLogFile)
    {
        return $exportLogFile($file);
    }

    public function delete(string $file, DeleteLogFile $deleteLogFile)
    {
        $deleteLogFile($file);

        return redirect()->route('log-files.index');
    }
}
