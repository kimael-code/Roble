<?php

namespace App\Http\Controllers\Organization;

use App\Actions\Organization\CreateOrganizationalUnit;
use App\Actions\Organization\UpdateOrganizationalUnit;
use App\Http\Controllers\Controller;
use App\Http\Props\Organization\OrganizationalUnitProps;
use App\Http\Requests\Organization\StoreOrganizationalUnitRequest;
use App\Http\Requests\Organization\UpdateOrganizationalUnitRequest;
use App\Actions\Organization\BatchDeleteOrganizationalUnit;
use App\Models\Organization\OrganizationalUnit;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Gate;
use Inertia\Inertia;

class OrganizationalUnitController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        Gate::authorize('viewAny', OrganizationalUnit::class);

        return Inertia::render('organization/organizational-units/Index', OrganizationalUnitProps::index());
    }

    /**
     * Show the form for creating a new resource.
     */
    public function create()
    {
        Gate::authorize('create', OrganizationalUnit::class);

        return Inertia::render('organization/organizational-units/Create', OrganizationalUnitProps::create());
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(StoreOrganizationalUnitRequest $request)
    {
        CreateOrganizationalUnit::handle($request->validated());

        return redirect(route('organizational-units.index'));
    }

    /**
     * Display the specified resource.
     */
    public function show(OrganizationalUnit $organizationalUnit)
    {
        Gate::authorize('view', $organizationalUnit);

        return Inertia::render('organization/organizational-units/Show', OrganizationalUnitProps::show($organizationalUnit));
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(OrganizationalUnit $organizationalUnit)
    {
        Gate::authorize('update', $organizationalUnit);

        return Inertia::render('organization/organizational-units/Edit', OrganizationalUnitProps::edit($organizationalUnit));
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(UpdateOrganizationalUnitRequest $request, OrganizationalUnit $organizationalUnit)
    {
        UpdateOrganizationalUnit::handle($request->validated(), $organizationalUnit);

        return redirect(route('organizational-units.index'));
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(OrganizationalUnit $organizationalUnit)
    {
        Gate::authorize('delete', $organizationalUnit);

        $organizationalUnit->delete();

        return redirect(route('organizational-units.index'));
    }

    public function batchDestroy(): RedirectResponse
    {
        $result = BatchDeleteOrganizationalUnit::execute(request()->all());

        return redirect(route('organizational-units.index'))->with('message', $result);
    }
}
