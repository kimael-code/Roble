<?php

namespace App\Http\Controllers\Organization;

use App\Actions\Organization\CreateOrganization;
use App\Actions\Organization\DeleteOrganization;
use App\Actions\Organization\UpdateOrganization;
use App\Http\Controllers\Controller;
use App\InertiaProps\Organization\OrganizationIndexProps;
use App\InertiaProps\Organization\OrganizationShowProps;
use App\InertiaProps\Organization\OrganizationEditProps;
use App\Http\Requests\Organization\StoreOrganizationRequest;
use App\Http\Requests\Organization\UpdateOrganizationRequest;
use App\Models\Organization\Organization;
use Illuminate\Support\Facades\Gate;
use Inertia\Inertia;

class OrganizationController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index(OrganizationIndexProps $props)
    {
        Gate::authorize('viewAny', Organization::class);

        return Inertia::render('organization/organizations/Index', $props->toArray());
    }

    /**
     * Show the form for creating a new resource.
     */
    public function create()
    {
        Gate::authorize('create', Organization::class);

        return Inertia::render('organization/organizations/Create');
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(StoreOrganizationRequest $request, CreateOrganization $createOrganization)
    {
        $createOrganization($request->validated());

        return redirect(route('organizations.index'));
    }

    /**
     * Display the specified resource.
     */
    public function show(Organization $organization, OrganizationShowProps $props)
    {
        Gate::authorize('view', $organization);

        return Inertia::render('organization/organizations/Show', $props->toArray($organization));
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(Organization $organization, OrganizationEditProps $props)
    {
        Gate::authorize('update', $organization);

        return Inertia::render('organization/organizations/Edit', $props->toArray($organization));
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(
        UpdateOrganizationRequest $request,
        Organization $organization,
        UpdateOrganization $updateOrganization
    ) {
        $updateOrganization($request->validated(), $organization);

        return redirect(route('organizations.index'));
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(Organization $organization, DeleteOrganization $deleteOrganization)
    {
        Gate::authorize('delete', $organization);

        $deleteOrganization($organization);

        return redirect(route('organizations.index'));
    }
}
