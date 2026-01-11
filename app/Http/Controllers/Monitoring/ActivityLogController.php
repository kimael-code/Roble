<?php

namespace App\Http\Controllers\Monitoring;

use App\Http\Controllers\Controller;
use App\InertiaProps\Monitoring\ActivityLogIndexProps;
use App\InertiaProps\Monitoring\ActivityLogShowProps;
use App\Models\Monitoring\ActivityLog;
use Illuminate\Support\Facades\Gate;
use Inertia\Inertia;

class ActivityLogController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index(ActivityLogIndexProps $props)
    {
        Gate::authorize('viewAny', ActivityLog::class);

        return Inertia::render('monitoring/activity-logs/Index', $props->toArray());
    }

    /**
     * Display the specified resource.
     */
    public function show(ActivityLog $activityLog, ActivityLogShowProps $props)
    {
        Gate::authorize('view', $activityLog);

        return Inertia::render('monitoring/activity-logs/Show', $props->toArray($activityLog));
    }
}
