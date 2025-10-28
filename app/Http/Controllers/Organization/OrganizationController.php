<?php

namespace App\Http\Controllers\Organization;

use App\Actions\Organization\BatchDeleteOrganization;
use App\Actions\Organization\CreateOrganization;
use App\Actions\Organization\DeleteOrganization;
use App\Actions\Organization\UpdateOrganization;
use App\Http\Controllers\Controller;
use App\Http\Props\Organization\OrganizationProps;
use App\Http\Requests\Organization\StoreOrganizationRequest;
use App\Http\Requests\Organization\UpdateOrganizationRequest;
use App\Models\Organization\Organization;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Gate;
use Inertia\Inertia;

class OrganizationController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        Gate::authorize('viewAny', Organization::class);

        return Inertia::render('organization/organizations/Index', OrganizationProps::index());
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
    public function store(StoreOrganizationRequest $request)
    {
        CreateOrganization::handle($request->validated());

        return redirect(route('organizations.index'));
    }

    /**
     * Display the specified resource.
     */
    public function show(Organization $organization)
    {
        Gate::authorize('view', $organization);

        return Inertia::render('organization/organizations/Show', OrganizationProps::show($organization));
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(Organization $organization)
    {
        Gate::authorize('update', $organization);

        return Inertia::render('organization/organizations/Edit', OrganizationProps::edit($organization));
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(UpdateOrganizationRequest $request, Organization $organization)
    {
        UpdateOrganization::handle($request->validated(), $organization);

        return redirect(route('organizations.index'));
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(Organization $organization)
    {
        Gate::authorize('delete', $organization);

        DeleteOrganization::handle($organization);

        return redirect(route('organizations.index'));
    }

    public function batchDestroy(): RedirectResponse
    {
        $result = BatchDeleteOrganization::execute(request()->all());

        return redirect(route('organizations.index'))->with('message', $result);
    }
}
