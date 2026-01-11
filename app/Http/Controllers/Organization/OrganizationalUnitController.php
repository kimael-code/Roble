<?php

namespace App\Http\Controllers\Organization;

use App\Actions\Organization\CreateOrganizationalUnit;
use App\Actions\Organization\UpdateOrganizationalUnit;
use App\Http\Controllers\Controller;
use App\InertiaProps\Organization\OrganizationalUnitIndexProps;
use App\InertiaProps\Organization\OrganizationalUnitShowProps;
use App\Http\Requests\Organization\StoreOrganizationalUnitRequest;
use App\Http\Requests\Organization\UpdateOrganizationalUnitRequest;
use App\Models\Organization\OrganizationalUnit;
use Illuminate\Support\Facades\Gate;
use Inertia\Inertia;

class OrganizationalUnitController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index(OrganizationalUnitIndexProps $props)
    {
        Gate::authorize('viewAny', OrganizationalUnit::class);

        return Inertia::render('organization/organizational-units/Index', $props->toArray());
    }

    /**
     * Show the form for creating a new resource.
     */
    public function create()
    {
        Gate::authorize('create', OrganizationalUnit::class);

        return Inertia::render('organization/organizational-units/Create');
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(
        StoreOrganizationalUnitRequest $request,
        CreateOrganizationalUnit $createOrganizationalUnit
    ) {
        $createOrganizationalUnit($request->validated());

        return redirect(route('organizational-units.index'));
    }

    /**
     * Display the specified resource.
     */
    public function show(OrganizationalUnit $organizationalUnit, OrganizationalUnitShowProps $props)
    {
        Gate::authorize('view', $organizationalUnit);

        return Inertia::render('organization/organizational-units/Show', $props->toArray($organizationalUnit));
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(OrganizationalUnit $organizationalUnit)
    {
        Gate::authorize('update', $organizationalUnit);

        return Inertia::render('organization/organizational-units/Edit', [
            'organizationalUnits' => $organizationalUnit->organization->activeOrganizationalUnits,
            'organizationalUnit' => $organizationalUnit->load(['organization']),
        ]);
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(
        UpdateOrganizationalUnitRequest $request,
        OrganizationalUnit $organizationalUnit,
        UpdateOrganizationalUnit $updateOrganizationalUnit
    ) {
        $updateOrganizationalUnit($request->validated(), $organizationalUnit);

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
}
