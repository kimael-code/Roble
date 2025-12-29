<?php

namespace App\Actions\Security;

use App\Models\Monitoring\ActivityLog;
use App\Models\Security\Permission;
use App\Models\Security\Role;
use App\Support\ActivityLogger;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Facades\DB;

class UpdateRole
{
    private bool $permissionsChanged = false;

    public function __construct(
        private ActivityLogger $logger
    ) {
    }

    public function __invoke(Role $role, array $inputs): Role
    {
        DB::transaction(function () use ($inputs, $role)
        {
            $role->name = $inputs['name'];
            $role->guard_name = $inputs['guard_name'];
            $role->description = $inputs['description'];
            $role->save();

            $assignedPermissions = Permission::whereIn('description', $inputs['permissions'])->get();

            $this->revokePermissions($role, $assignedPermissions);
            $this->givePermissions($role, $assignedPermissions);
        });

        if ($this->permissionsChanged)
        {
            session()->flash('message', [
                'content' => "{$role->name} ({$role->description})",
                'title' => '¡GUARDADO!',
                'type' => 'success',
            ]);
        }

        return $role;
    }

    private function revokePermissions(Role $role, Collection $assignedPermissions): void
    {
        foreach ($role->permissions as $permission)
        {
            if ($assignedPermissions->doesntContain($permission))
            {
                $role->revokePermissionTo($permission);

                $this->logger->logAuthorized(
                    ActivityLog::LOG_NAMES['roles'],
                    $role,
                    "revocó permiso [{$permission->description}] a rol [:subject.name]",
                    [
                        'permiso_revocado' => $permission,
                        'al_rol' => $role,
                    ]
                );

                $this->permissionsChanged = true;
            }
        }
    }

    private function givePermissions(Role $role, Collection $assignedPermissions): void
    {
        foreach ($assignedPermissions as $assignedPermission)
        {
            if ($role->permissions->doesntContain($assignedPermission))
            {
                $role->givePermissionTo($assignedPermission);

                $this->logger->logAuthorized(
                    ActivityLog::LOG_NAMES['roles'],
                    $role,
                    "otorgó permiso [{$assignedPermission->description}] a rol [:subject.name]",
                    [
                        'permiso_otorgado' => $assignedPermission,
                        'al_rol' => $role,
                    ]
                );

                $this->permissionsChanged = true;
            }
        }
    }
}
