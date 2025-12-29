<?php

namespace App\InertiaProps\Monitoring;

use App\Support\Logs\Logfile;
use App\Support\PermissionChecker;

/**
 * InertiaProps para la vista Index de LogFiles.
 */
class LogFileIndexProps
{
    public function __construct(
        private PermissionChecker $permissions
    ) {
    }

    public function toArray(): array
    {
        $logfile = new Logfile();
        $logFiles = array_keys($logfile->relativePaths());
        $logs = $logfile->logs($logFiles[0] ?? null);

        return [
            'can' => $this->getPermissions(),
            'filters' => request()->only(['search']),
            'logFiles' => $logFiles,
            'logs' => $logs,
        ];
    }

    private function getPermissions(): array
    {
        $exportPermission = $this->permissions->can('export system logs');

        return [
            'create' => $this->permissions->can('create system logs'),
            'read' => $this->permissions->can('read any system log'),
            'update' => $this->permissions->can('update system logs'),
            'delete' => $this->permissions->can('delete system logs'),
            'export_collection' => $exportPermission,
            'export_record' => $exportPermission,
        ];
    }
}
