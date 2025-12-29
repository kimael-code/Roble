<?php

namespace App\InertiaProps\Organization;

use App\Http\Resources\Monitoring\ActivityLogCollection;
use App\Http\Resources\Organization\OrganizationalUnitCollection;
use App\Models\Monitoring\ActivityLog;
use App\Models\Organization\Organization;
use App\Support\PaginationBuilder;
use App\Support\PermissionChecker;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Arr;

/**
 * InertiaProps para la vista Show de Organizations.
 */
class OrganizationShowProps
{
    public function __construct(
        private PermissionChecker $permissions,
        private PaginationBuilder $pagination
    ) {
    }

    public function toArray(Organization $organization): array
    {
        $search = request()->only(['search', 'name']);

        return [
            'can' => Arr::except($this->permissions->checkResource('organizations'), 'read'),
            'filters' => request()->all(['search', 'name']),
            'organization' => $organization,
            'ous' => fn() => $this->getOrganizationalUnits($organization, $search),
            'logs' => fn() => $this->getLogs($organization, $search),
        ];
    }

    private function getOrganizationalUnits(Organization $organization, array $search): OrganizationalUnitCollection
    {
        return new OrganizationalUnitCollection(
            $this->pagination->paginate(
                $organization->organizationalUnits()->filter($search)->latest(),
                pageName: 'page_o',
                perPageName: 'per_page_o'
            )
        );
    }

    private function getLogs(Organization $organization, array $search): ActivityLogCollection
    {
        return new ActivityLogCollection(
            $this->pagination->paginate(
                ActivityLog::filter(request()->only(['search']))
                    ->whereHasMorph(
                        'subject',
                        Organization::class,
                        fn(Builder $query) => $query->where('id', $organization->id)
                    )
                    ->latest(),
                pageName: 'page_l',
                perPageName: 'per_page_l'
            )
        );
    }
}

