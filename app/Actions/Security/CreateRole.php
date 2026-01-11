<?php

namespace App\Actions\Security;

use App\Models\Monitoring\ActivityLog;
use App\Models\Security\Permission;
use App\Models\Security\Role;
use App\Support\ActivityLogger;
use Illuminate\Support\Facades\DB;

class CreateRole
{
    public function __construct(
        private ActivityLogger $logger
    ) {
    }

    public function __invoke(array $inputs): Role
    {
        return DB::transaction(function () use ($inputs)
        {
            $role = Role::create([
                'name' => $inputs['name'],
                'guard_name' => $inputs['guard_name'],
                'description' => $inputs['description'],
            ]);

            $this->assignPermissions($role, $inputs['permissions'], $inputs['guard_name']);

            return $role;
        });
    }

    private function assignPermissions(Role $role, array $permissionDescriptions, string $guardName): void
    {
        foreach ($permissionDescriptions as $description)
        {
            $permission = Permission::where('description', $description)
                ->where('guard_name', $guardName)
                ->first();

            $role->givePermissionTo($permission);

            $this->logger->logAuthorized(
                ActivityLog::LOG_NAMES['roles'],
                $role,
                "otorgó permiso [{$permission->description}] a rol [:subject.name]",
                [
                    'permiso_asignado' => $permission,
                    'al_rol' => $role,
                ]
            );
        }
    }
}
