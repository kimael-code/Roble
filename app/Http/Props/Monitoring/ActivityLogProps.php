<?php

namespace App\Http\Props\Monitoring;

use App\Http\Resources\Monitoring\ActivityLogCollection;
use App\Models\Monitoring\ActivityLog;
use App\Models\User;
use App\Support\UserAgent;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Request;
use Inertia\Inertia;

class ActivityLogProps
{
    private static function getPermissions(): array
    {
        return [
            'create' => false,
            'read' => Auth::user()->can('read activity trace'),
            'update' => false,
            'delete' => false,
            'export' => Auth::user()->can('export activity traces'),
        ];
    }

    public static function index(): array
    {
        $filtersOnly = Request::only([
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
        ]);
        $filtersAll = Request::all([
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
        ]);

        $perPage = Request::input('per_page', 10);

        return [
            'can' => self::getPermissions(),
            'filters' => $filtersAll,
            'users' => Inertia::optional(fn() => User::select(['id', 'name'])->get()),
            'events' => Inertia::optional(fn() => array_values(ActivityLog::EVENT_NAMES)),
            'logNames' => Inertia::optional(fn() => array_values(ActivityLog::LOG_NAMES)),
            'logs' => new ActivityLogCollection(
                ActivityLog::filter($filtersOnly)
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
                    ->paginate($perPage)
                    ->withQueryString()
            ),
        ];
    }

    public static function show(ActivityLog $activityLog): array
    {
        return [
            'can' => self::getPermissions(),
            'log' => $activityLog->load(['causer', 'subject']),
            'userAgent' => [
                'details' => UserAgent::details($activityLog->properties->get('request')['user_agent']),
                'locale' => UserAgent::locale($activityLog->properties->get('request')['user_agent_lang'])
            ],
        ];
    }
}
