<?php

namespace App\Actions\Monitoring;

use App\Support\Logs\Logfile;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Symfony\Component\HttpFoundation\BinaryFileResponse;

class ExportLogFile
{
    public function __invoke(string $file): BinaryFileResponse
    {
        $logfile = new Logfile();

        $fileBeingDownloaded = '';

        if (array_key_exists($file, $logfile->relativePaths()))
        {
            $fileBeingDownloaded = $logfile->relativePaths()[$file];
        }

        $fileName = Str::replace(' ', '_', now()->toDateTimeString() . "_{$fileBeingDownloaded}");

        return response()->download(Storage::disk('logs')->path($fileBeingDownloaded), $fileName);
    }
}
