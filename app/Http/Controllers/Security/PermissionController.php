<?php

namespace App\Http\Controllers\Security;

use App\Actions\Security\CreatePermission;
use App\Actions\Security\UpdatePermission;
use App\Http\Controllers\Controller;
use App\InertiaProps\Security\PermissionIndexProps;
use App\InertiaProps\Security\PermissionShowProps;
use App\InertiaProps\Security\PermissionEditProps;
use App\Http\Requests\Security\StorePermissionRequest;
use App\Http\Requests\Security\UpdatePermissionRequest;
use App\Models\Security\Permission;
use Gate;
use Inertia\Inertia;

class PermissionController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index(PermissionIndexProps $props)
    {
        Gate::authorize('viewAny', Permission::class);

        return Inertia::render('security/permissions/Index', $props->toArray());
    }

    /**
     * Show the form for creating a new resource.
     */
    public function create()
    {
        Gate::authorize('create', Permission::class);

        return Inertia::render('security/permissions/Create');
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(StorePermissionRequest $request, CreatePermission $createPermission)
    {
        $createPermission($request->validated());

        return redirect(route('permissions.index'));
    }

    /**
     * Display the specified resource.
     */
    public function show(Permission $permission, PermissionShowProps $props)
    {
        Gate::authorize('view', $permission);

        return Inertia::render('security/permissions/Show', $props->toArray($permission));
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(Permission $permission, PermissionEditProps $props)
    {
        Gate::authorize('update', $permission);

        return Inertia::render('security/permissions/Edit', $props->toArray($permission));
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(UpdatePermissionRequest $request, Permission $permission, UpdatePermission $updatePermission)
    {
        $updatePermission($permission, $request->validated());

        return redirect(route('permissions.index'));
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(Permission $permission)
    {
        Gate::authorize('delete', $permission);

        $permission->delete();

        return redirect(route('permissions.index'));
    }
}
