<script setup lang="ts">
import AppLayout from '@/layouts/AppLayout.vue';
import { DashboardDataSysadmin, type BreadcrumbItem } from '@/types';
import { dashboard } from '@/routes';
import { Head } from '@inertiajs/vue3';
import SysAdminDashboard from './dashboards/SysAdminDashboard.vue';

defineProps<{
  can: { [index: string]: boolean };
  sysadminData?: DashboardDataSysadmin;
}>();

const breadcrumbs: BreadcrumbItem[] = [
  {
    title: 'Tablero',
    href: dashboard().url,
  },
];
</script>

<template>
  <Head title="Tablero" />

  <AppLayout :breadcrumbs="breadcrumbs">
    <div class="flex h-full flex-1 flex-col gap-4 rounded-xl p-4">
      <SysAdminDashboard v-if="can?.dashboardSysadmin" :data="sysadminData" />
    </div>
  </AppLayout>
</template>
