<?php

namespace App\Http\Controllers;

use App\InertiaProps\DashboardProps;
use Inertia\Inertia;

class DashboardController extends Controller
{
    /**
     * Display the dashboard.
     */
    public function index(DashboardProps $props)
    {
        return Inertia::render('Dashboard', $props->toArray(auth()->user()));
    }
}
