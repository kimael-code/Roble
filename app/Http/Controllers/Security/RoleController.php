<?php

namespace App\Http\Controllers\Security;

use App\Actions\Security\CreateRole;
use App\Actions\Security\UpdateRole;
use App\Http\Controllers\Controller;
use App\Http\Props\Security\RoleProps;
use App\Http\Requests\Security\StoreRoleRequest;
use App\Http\Requests\Security\UpdateRoleRequest;
use App\Actions\Security\BatchDeleteRole;
use App\Models\Security\Role;
use Illuminate\Http\RedirectResponse;
use Gate;
use Inertia\Inertia;

class RoleController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        Gate::authorize('viewAny', Role::class);

        return Inertia::render('security/roles/Index', RoleProps::index());
    }

    /**
     * Show the form for creating a new resource.
     */
    public function create()
    {
        Gate::authorize('create', Role::class);

        return Inertia::render('security/roles/Create', RoleProps::create());
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(StoreRoleRequest $request)
    {
        CreateRole::handle($request->validated());

        return redirect(route('roles.index'));
    }

    /**
     * Display the specified resource.
     */
    public function show(Role $role)
    {
        Gate::authorize('view', $role);

        return Inertia::render('security/roles/Show', RoleProps::show($role));
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(Role $role)
    {
        Gate::authorize('update', $role);

        return Inertia::render('security/roles/Edit', RoleProps::edit($role));
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(UpdateRoleRequest $request, Role $role)
    {
        UpdateRole::handle($role, $request->validated());

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

    public function batchDestroy(): RedirectResponse
    {
        $result = BatchDeleteRole::execute(request()->all());

        return redirect(route('roles.index'))->with('message', $result);
    }
}
