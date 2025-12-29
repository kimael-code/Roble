<?php

namespace App\InertiaProps;

use App\Events\ActiveUsersUpdated;
use App\Events\DashboardStatsUpdated;
use App\Events\LogFilesUpdated;
use App\Models\User;
use App\Support\Dashboard\DashboardStatsCalculator;
use App\Support\PermissionChecker;

/**
 * InertiaProps para el Dashboard.
 */
class DashboardProps
{
    public function __construct(
        private PermissionChecker $permissions,
        private DashboardStatsCalculator $statsCalculator
    ) {
    }

    public function toArray(User $user): array
    {
        $props = [
            'can' => [
                'dashboardSysadmin' => $user->can('read sysadmin dashboard'),
            ],
        ];

        if ($user->can('read sysadmin dashboard'))
        {
            $props['sysadminData'] = $this->getSysadminData();
        }

        return $props;
    }

    private function getSysadminData(): array
    {
        $usersStats = $this->statsCalculator->getUsersStats();
        $rolesStats = $this->statsCalculator->getRolesStats();
        $activeUsers = $this->statsCalculator->getActiveUsers();
        $logFiles = $this->statsCalculator->getLogFilesStats();

        // Emitir eventos para actualización en tiempo real
        broadcast(new DashboardStatsUpdated([
            'users' => $usersStats,
            'roles' => $rolesStats,
        ]));

        broadcast(new ActiveUsersUpdated($activeUsers));
        broadcast(new LogFilesUpdated($logFiles));

        return [
            'users' => $usersStats,
            'roles' => $rolesStats,
            'activeUsers' => $activeUsers,
            'logFiles' => $logFiles,
        ];
    }
}
