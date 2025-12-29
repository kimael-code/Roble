<?php

namespace App\InertiaProps\Security;

use App\Models\Security\Permission;

/**
 * InertiaProps para la vista Edit de Permissions.
 */
class PermissionEditProps
{
    public function toArray(Permission $permission): array
    {
        return [
            'permission' => $permission,
        ];
    }
}
