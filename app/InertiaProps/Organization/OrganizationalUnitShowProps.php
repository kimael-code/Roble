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
 * InertiaProps para la vista Show de OrganizationalUnits.
 */
class OrganizationalUnitShowProps
{
    public function __construct(
        private PermissionChecker $permissions,
        private PaginationBuilder $pagination
    ) {
    }

    public function toArray(OrganizationalUnit $ou): array
    {
        return [
            'can' => Arr::except($this->permissions->checkResource('organizational units'), 'read'),
            'filters' => request()->all(['search']),
            'organizationalUnit' => $ou->load('organization', 'organizationalUnit'),
            'organizationalUnits' => fn() => $this->getOrganizationalUnits($ou),
            'logs' => fn() => $this->getLogs($ou),
        ];
    }

    private function getOrganizationalUnits(OrganizationalUnit $ou): OrganizationalUnitCollection
    {
        return new OrganizationalUnitCollection(
            $this->pagination->paginate(
                $ou->organizationalUnits()
                    ->filter(request()->only(['search', 'name']))
                    ->latest(),
                pageName: 'page_o',
                perPageName: 'per_page_o'
            )
        );
    }

    private function getLogs(OrganizationalUnit $ou): ActivityLogCollection
    {
        return new ActivityLogCollection(
            $this->pagination->paginate(
                ActivityLog::filter(request()->only(['search']))
                    ->whereHasMorph(
                        'subject',
                        OrganizationalUnit::class,
                        fn(Builder $query) => $query->where('id', $ou->id)
                    )
                    ->latest(),
                pageName: 'page_l',
                perPageName: 'per_page_l'
            )
        );
    }
}

