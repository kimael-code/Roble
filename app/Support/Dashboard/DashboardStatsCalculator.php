<?php

namespace App\Support\Dashboard;

use App\Models\Security\Role;
use App\Models\User;
use App\Support\Logs\Logfile;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;

/**
 * Calculador de estadísticas para el dashboard.
 * Centraliza la lógica de cálculo de estadísticas siguiendo el principio de responsabilidad única.
 */
class DashboardStatsCalculator
{
    /**
     * Obtiene las estadísticas de usuarios.
     * Calcula usuarios activos, inactivos, deshabilitados y eliminados.
     */
    public function getUsersStats(): array
    {
        $total = User::withTrashed()->count();

        // Usuarios eliminados (soft deleted)
        $deleted = User::onlyTrashed()->count();

        // Usuarios deshabilitados (disabled_at no null, excluyendo eliminados)
        $disabled = User::whereNotNull('disabled_at')->count();

        // Usuarios activos (is_active = true, no deshabilitados, no eliminados)
        $active = User::where('is_active', true)
            ->whereNull('disabled_at')
            ->count();

        // Usuarios inactivos (is_active = false, no deshabilitados, no eliminados)
        $inactive = User::where('is_active', false)
            ->whereNull('disabled_at')
            ->count();

        return [
            'total' => $total,
            'series' => [$active, $inactive, $disabled, $deleted],
            'labels' => ['Activos', 'Inactivos', 'Deshabilitados', 'Eliminados'],
        ];
    }

    /**
     * Obtiene las estadísticas de roles.
     */
    public function getRolesStats(): array
    {
        $usersCountByRole = $this->getUsersCountHavingRole();

        return [
            'count' => Role::count(),
            'series' => array_values($usersCountByRole),
            'labels' => array_keys($usersCountByRole),
        ];
    }

    /**
     * Obtiene la lista de usuarios activos.
     */
    public function getActiveUsers(): array
    {
        return collect(
            DB::table('sessions')
                ->selectRaw('distinct on (user_id) user_id, ip_address, user_agent, last_activity')
                ->whereNotNull('user_id')
                ->leftJoin('users', 'sessions.user_id', '=', 'users.id')
                ->orderBy('user_id')
                ->orderBy('last_activity', 'desc')
                ->get()
        )->map(function ($session)
        {
            $user = User::find($session->user_id);

            return [
                'user' => $user ? [
                    'id' => $user->id,
                    'name' => $user->name,
                    'email' => $user->email,
                    'avatar' => $user->avatar,
                    'created_at_human' => $user->created_at_human,
                ] : null,
                'ip_address' => $session->ip_address,
                'last_active' => Carbon::createFromTimestamp($session->last_activity)->diffForHumans(),
            ];
        })->filter(fn($item) => $item['user'] !== null)->values()->toArray();
    }

    /**
     * Obtiene los tamaños de los archivos de logs.
     */
    public function getLogFilesStats(): array
    {
        $logfile = new Logfile();
        $logSizes = $logfile->logSizes();

        // Calcular el tamaño total
        $totalSize = 0;
        $formattedLogs = [];

        foreach ($logSizes as $log)
        {
            if (isset($log[0]) && is_array($log[0]))
            {
                $totalSize += $log[0]['sizeRaw'] ?? 0;
                $formattedLogs[] = $log[0];
            }
        }

        return [
            'logs' => $formattedLogs,
            'totalSize' => $totalSize,
            'totalSizeHuman' => \Illuminate\Support\Number::fileSize($totalSize, precision: 2),
        ];
    }

    /**
     * Obtiene todas las estadísticas del dashboard.
     */
    public function getAllStats(): array
    {
        return [
            'users' => $this->getUsersStats(),
            'roles' => $this->getRolesStats(),
            'activeUsers' => $this->getActiveUsers(),
            'logFiles' => $this->getLogFilesStats(),
        ];
    }

    /**
     * Cuenta cuántos usuarios tienen cada rol.
     */
    private function getUsersCountHavingRole(): array
    {
        $result = [];

        foreach (Role::all() as $role)
        {
            $result[$role->name] = User::with('roles')
                ->get()
                ->filter(fn($user) => $user->roles->where('name', $role->name)->toArray())
                ->count();
        }

        return $result;
    }
}
