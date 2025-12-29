<?php

namespace App\Http\Controllers\Security;

use App\Actions\Security\CreateRole;
use App\Actions\Security\UpdateRole;
use App\Http\Controllers\Controller;
use App\InertiaProps\Security\RoleIndexProps;
use App\InertiaProps\Security\RoleShowProps;
use App\InertiaProps\Security\RoleCreateProps;
use App\InertiaProps\Security\RoleEditProps;
use App\Http\Requests\Security\StoreRoleRequest;
use App\Http\Requests\Security\UpdateRoleRequest;
use App\Models\Security\Role;
use Gate;
use Inertia\Inertia;

class RoleController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index(RoleIndexProps $props)
    {
        Gate::authorize('viewAny', Role::class);

        return Inertia::render('security/roles/Index', $props->toArray());
    }

    /**
     * Show the form for creating a new resource.
     */
    public function create(RoleCreateProps $props)
    {
        Gate::authorize('create', Role::class);

        return Inertia::render('security/roles/Create', $props->toArray());
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(StoreRoleRequest $request, CreateRole $createRole)
    {
        $createRole($request->validated());

        return redirect(route('roles.index'));
    }

    /**
     * Display the specified resource.
     */
    public function show(Role $role, RoleShowProps $props)
    {
        Gate::authorize('view', $role);

        return Inertia::render('security/roles/Show', $props->toArray($role));
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(Role $role, RoleEditProps $props)
    {
        Gate::authorize('update', $role);

        return Inertia::render('security/roles/Edit', $props->toArray($role));
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(UpdateRoleRequest $request, Role $role, UpdateRole $updateRole)
    {
        $updateRole($role, $request->validated());

        return redirect(route('roles.index'));
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(Role $role)
    {
        Gate::authorize('delete', $role);

        $role->delete();

        return redirect(route('roles.index'));
    }
}
