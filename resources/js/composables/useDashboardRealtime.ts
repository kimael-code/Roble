import { useEchoPublic } from '@laravel/echo-vue';
import { ref } from 'vue';

interface DashboardStats {
  users: {
    total: number;
    series: number[];
    labels: string[];
  };
  roles: {
    count: number;
    series: number[];
    labels: string[];
  };
}

interface ActiveUser {
  user: {
    id: number;
    name: string;
    email: string;
    avatar?: string;
    created_at_human?: string;
  };
  ip_address: string;
  last_active: string;
}

interface LogFile {
  logName: string;
  sizeHuman: string;
  sizeRaw: number;
}

interface LogFilesData {
  logs: LogFile[];
  totalSize: number;
  totalSizeHuman: string;
}

/**
 * Composable para suscribirse a eventos de Reverb del dashboard.
 * Actualiza los datos del dashboard en tiempo real usando el hook moderno useEchoPublic.
 */
export function useDashboardRealtime(initialData: {
  users: DashboardStats['users'];
  roles: DashboardStats['roles'];
  activeUsers: ActiveUser[];
  logFiles: LogFilesData;
}) {
  const usersStats = ref(initialData.users);
  const rolesStats = ref(initialData.roles);
  const activeUsers = ref(initialData.activeUsers);
  const logFiles = ref(initialData.logFiles);

  // Solo ejecutar en el cliente, no en SSR
  if (!import.meta.env.SSR) {
    // Escuchar evento de actualización de estadísticas
    useEchoPublic(
      'dashboard.sysadmin',
      '.stats.updated',
      (data: { stats: DashboardStats }) => {
        if (import.meta.env.DEV) {
          console.log('📊 Stats updated:', data);
        }
        if (data.stats.users) {
          usersStats.value = data.stats.users;
        }
        if (data.stats.roles) {
          rolesStats.value = data.stats.roles;
        }
      },
    );

    // Escuchar evento de actualización de usuarios activos
    useEchoPublic(
      'dashboard.sysadmin',
      '.active-users.updated',
      (data: { activeUsers: ActiveUser[] }) => {
        if (import.meta.env.DEV) {
          console.log('👥 Active users updated:', data);
        }
        activeUsers.value = data.activeUsers;
      },
    );

    // Escuchar evento de actualización de archivos de logs
    useEchoPublic(
      'dashboard.sysadmin',
      '.log-files.updated',
      (data: { logFiles: LogFilesData }) => {
        if (import.meta.env.DEV) {
          console.log('📁 Log files updated:', data);
        }
        logFiles.value = data.logFiles;
      },
    );
  }

  return {
    usersStats,
    rolesStats,
    activeUsers,
    logFiles,
  };
}
