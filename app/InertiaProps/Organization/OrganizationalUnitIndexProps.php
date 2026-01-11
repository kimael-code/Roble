<?php

namespace App\InertiaProps\Organization;

use App\Http\Resources\Monitoring\ActivityLogCollection;
use App\Http\Resources\Organization\OrganizationalUnitCollection;
use App\Models\Monitoring\ActivityLog;
use App\Models\Organization\OrganizationalUnit;
use App\Support\PaginationBuilder;
use App\Support\PermissionChecker;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Arr;

/**
 * InertiaProps para la vista Index de OrganizationalUnits.
 */
class OrganizationalUnitIndexProps
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
            'can' => $this->permissions->checkResource('organizational units'),
            'filters' => request()->all($allowedFilters),
            'organizationalUnits' => fn() => $this->getOrganizationalUnits($allowedFilters),
        ];
    }

    private function getOrganizationalUnits(array $allowedFilters): OrganizationalUnitCollection
    {
        return new OrganizationalUnitCollection(
            $this->pagination->paginate(
                OrganizationalUnit::filter(request()->only($allowedFilters))
            )
        );
    }
}
