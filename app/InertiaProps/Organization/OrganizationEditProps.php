<?php

namespace App\InertiaProps\Organization;

use App\Models\Organization\Organization;

/**
 * InertiaProps para la vista Edit de Organizations.
 */
class OrganizationEditProps
{
    public function toArray(Organization $organization): array
    {
        return [
            'organization' => $organization,
        ];
    }
}
