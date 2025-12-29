<?php

namespace App\Actions\Security;

use App\Models\Monitoring\ActivityLog;
use App\Models\Security\Permission;
use App\Support\ActivityLogger;

class UpdatePermission
{
    public function __construct(
        private ActivityLogger $logger
    ) {
    }

    public function __invoke(Permission $permission, array $inputs): Permission
    {
        $permission->update($inputs);

        if ($permission->wasChanged())
        {
            $this->logger->logUpdated(
                ActivityLog::LOG_NAMES['permissions'],
                $permission,
                "actualizó permiso [:subject.name] [:subject.description]"
            );
        }

        return $permission;
    }
}
