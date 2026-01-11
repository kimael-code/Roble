<?php

namespace App\InertiaProps\Monitoring;

use App\Models\Monitoring\ActivityLog;
use App\Support\PermissionChecker;
use App\Support\UserAgent;

/**
 * InertiaProps para la vista Show de ActivityLogs.
 */
class ActivityLogShowProps
{
    public function __construct(
        private PermissionChecker $permissions
    ) {
    }

    public function toArray(ActivityLog $activityLog): array
    {
        return [
            'can' => $this->getPermissions(),
            'log' => $activityLog->load(['causer', 'subject']),
            'userAgent' => $this->getUserAgentDetails($activityLog),
        ];
    }

    private function getPermissions(): array
    {
        $exportPermission = $this->permissions->can('export activity traces');

        return [
            'create' => false,
            'read' => $this->permissions->can('read activity trace'),
            'update' => false,
            'delete' => false,
            'export_collection' => $exportPermission,
            'export_record' => $exportPermission,
        ];
    }

    private function getUserAgentDetails(ActivityLog $activityLog): array
    {
        $request = $activityLog->properties->get('request');

        return [
            'details' => UserAgent::details($request['user_agent'] ?? ''),
            'locale' => UserAgent::locale($request['user_agent_lang'] ?? ''),
        ];
    }
}
