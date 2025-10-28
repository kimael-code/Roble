<?php

namespace App\Http\Controllers\Security;

use App\Actions\Security\CreatePermission;
use App\Actions\Security\UpdatePermission;
use App\Http\Controllers\Controller;
use App\Http\Props\Security\PermissionProps;
use App\Http\Requests\Security\StorePermissionRequest;
use App\Http\Requests\Security\UpdatePermissionRequest;
use App\Actions\Security\BatchDeletePermission;
use App\Models\Security\Permission;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Gate;
use Inertia\Inertia;

class PermissionController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        Gate::authorize('viewAny', Permission::class);

        return Inertia::render('security/permissions/Index', PermissionProps::index());
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
    public function store(StorePermissionRequest $request)
    {
        CreatePermission::handle($request->validated());

        return redirect()->route('permissions.index');
    }

    /**
     * Display the specified resource.
     */
    public function show(Permission $permission)
    {
        Gate::authorize('view', $permission);

        return Inertia::render('security/permissions/Show', PermissionProps::show($permission));
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(Permission $permission)
    {
        Gate::authorize('update', $permission);

        return Inertia::render('security/permissions/Edit', PermissionProps::edit($permission));
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(UpdatePermissionRequest $request, Permission $permission)
    {
        UpdatePermission::handle($request->validated(), $permission);

        return redirect()->route('permissions.index');
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(Permission $permission)
    {
        Gate::authorize('delete', $permission);

        $permission->delete();

        return redirect()->route('permissions.index');
    }

    public function batchDestroy(): RedirectResponse
    {
        $result = BatchDeletePermission::execute(request()->all());

        return redirect(route('permissions.index'))->with('message', $result);
    }
}
