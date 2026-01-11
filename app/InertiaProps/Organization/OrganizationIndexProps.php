<?php

namespace App\InertiaProps\Organization;

use App\Http\Resources\Organization\OrganizationCollection;
use App\Models\Organization\Organization;
use App\Support\PaginationBuilder;
use App\Support\PermissionChecker;

/**
 * InertiaProps para la vista Index de Organizations.
 */
class OrganizationIndexProps
{
    public function __construct(
        private PermissionChecker $permissions,
        private PaginationBuilder $pagination
    ) {
    }

    public function toArray(): array
    {
        $allowedFilters = ['search', 'sort_by'];

        return [
            'can' => $this->permissions->checkResource('organizations'),
            'filters' => request()->all($allowedFilters),
            'organizations' => fn() => $this->getOrganizations($allowedFilters),
        ];
    }

    private function getOrganizations(array $allowedFilters): OrganizationCollection
    {
        return new OrganizationCollection(
            $this->pagination->paginate(
                Organization::filter(request()->only($allowedFilters))
            )
        );
    }
}
