<?php

namespace App\Actions\Security;

use App\Models\Monitoring\ActivityLog;
use App\Models\Security\Permission;
use App\Support\ActivityLogger;

class CreatePermission
{
    public function __construct(
        private ActivityLogger $logger
    ) {
    }

    public function __invoke(array $inputs): Permission
    {
        $permission = Permission::create($inputs);

        $this->logger->logCreated(
            ActivityLog::LOG_NAMES['permissions'],
            $permission,
            "creó permiso [:subject.name] [:subject.description]"
        );

        return $permission;
    }
}
