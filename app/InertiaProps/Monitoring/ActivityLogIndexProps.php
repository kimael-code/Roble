<?php

namespace App\InertiaProps\Monitoring;

use App\Http\Resources\Monitoring\ActivityLogCollection;
use App\Models\Monitoring\ActivityLog;
use App\Models\User;
use App\Support\PaginationBuilder;
use App\Support\PermissionChecker;
use Inertia\Inertia;

/**
 * InertiaProps para la vista Index de ActivityLogs.
 */
class ActivityLogIndexProps
{
    public function __construct(
        private PermissionChecker $permissions,
        private PaginationBuilder $pagination
    ) {
    }

    public function toArray(): array
    {
        $allowedFilters = [
            'search',
            'sort_by',
            'date',
            'date_range',
            'ips',
            'users',
            'events',
            'modules',
            'time',
            'time_from',
            'time_until',
        ];

        $can = $this->getPermissions();
        // Solo permitir exportar la colección completa, no registros individuales
        $can['export_record'] = false;

        return [
            'can' => $can,
            'filters' => request()->all($allowedFilters),
            'users' => Inertia::optional(fn() => $this->getUsersForFilter()),
            'events' => Inertia::optional(fn() => array_values(ActivityLog::EVENT_NAMES)),
            'logNames' => Inertia::optional(fn() => array_values(ActivityLog::LOG_NAMES)),
            'logs' => $this->getLogs($allowedFilters),
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

    private function getLogs(array $allowedFilters): ActivityLogCollection
    {
        return new ActivityLogCollection(
            $this->pagination->paginate(
                ActivityLog::filter(request()->only($allowedFilters))
                    ->select([
                        'activity_log.id',
                        'activity_log.log_name',
                        'activity_log.description',
                        'activity_log.subject_id',
                        'activity_log.causer_type',
                        'activity_log.causer_id',
                        'activity_log.properties',
                        'activity_log.created_at',
                        'activity_log.updated_at',
                        'activity_log.event',
                    ])
                    ->with('causer')
            )
        );
    }

    private function getUsersForFilter(): array
    {
        return User::select(['id', 'name'])->get()->toArray();
    }
}
