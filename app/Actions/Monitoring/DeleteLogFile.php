<?php

namespace App\Actions\Monitoring;

use App\Models\Monitoring\ActivityLog;
use App\Models\User;
use App\Support\Logs\Logfile;
use Illuminate\Support\Facades\Storage;

class DeleteLogFile
{
    public function __invoke(string $file): void
    {
        $logfile = new Logfile();

        $fileBeingDeleted = '';

        if (array_key_exists($file, $logfile->relativePaths()))
        {
            $fileBeingDeleted = $logfile->relativePaths()[$file];
        }

        Storage::disk('logs')->delete($fileBeingDeleted);

        $user = auth()->user();
        activity(ActivityLog::LOG_NAMES['logs'])
            ->event(ActivityLog::EVENT_NAMES['deleted'])
            ->causedBy($user)
            ->withProperties([
                'request' => [
                    'ip_address' => request()->ip(),
                    'user_agent' => request()->header('user-agent'),
                    'user_agent_lang' => request()->header('accept-language'),
                    'referer' => request()->header('referer'),
                    'http_method' => request()->method(),
                    'request_url' => request()->fullUrl(),
                ],
                'causer', User::with('person')->find($user->id)->toArray()
            ])
            ->log("eliminó el archivo [$file]");

        session()->flash('message', [
            'content' => "archivo $file",
            'title' => '¡ELIMINADO!',
            'type' => 'danger',
        ]);
    }
}
