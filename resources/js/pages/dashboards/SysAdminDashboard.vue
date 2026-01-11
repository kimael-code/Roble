<script setup lang="ts">
import { useDashboardRealtime } from '@/composables/useDashboardRealtime';
import ActiveUsersChart from './partials/ActiveUsersChart.vue';
import LogFilesChart from './partials/LogFilesChart.vue';
import RolesChart from './partials/RolesChart.vue';
import UsersChart from './partials/UsersChart.vue';

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

interface DashboardDataSysadmin {
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
  activeUsers: ActiveUser[];
  logFiles: LogFilesData;
}

const props = defineProps<{
  data?: DashboardDataSysadmin;
}>();

// Suscribirse a actualizaciones en tiempo real
const { usersStats, rolesStats, activeUsers, logFiles } = useDashboardRealtime({
  users: props.data!.users,
  roles: props.data!.roles,
  activeUsers: props.data!.activeUsers,
  logFiles: props.data!.logFiles,
});
</script>

<template>
  <div class="grid auto-rows-min gap-4 md:grid-cols-2">
    <UsersChart
      :data="usersStats.series"
      :labels="usersStats.labels"
      :total="usersStats.total"
    />
    <RolesChart :data="rolesStats.series" :labels="rolesStats.labels" />
  </div>
  <div class="mt-4 grid auto-rows-min gap-4 md:grid-cols-2">
    <ActiveUsersChart :active-users="activeUsers" />
    <LogFilesChart :log-files="logFiles" />
  </div>
</template>
