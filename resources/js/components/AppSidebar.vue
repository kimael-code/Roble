<script setup lang="ts">
import NavFooter from '@/components/NavFooter.vue';
import NavMain from '@/components/NavMain.vue';
import NavUser from '@/components/NavUser.vue';
import NotificationsSheet from '@/components/NotificationsSheet.vue';
import {
  Sidebar,
  SidebarContent,
  SidebarFooter,
  SidebarHeader,
  SidebarMenu,
  SidebarMenuButton,
  SidebarMenuItem,
} from '@/components/ui/sidebar';
import { useRememberScroll } from '@/composables/useRememberScroll';
import { dashboard } from '@/routes';
import { type NavItem } from '@/types';
import { Link, usePage } from '@inertiajs/vue3';
import {
  Bell,
  Bug,
  Building,
  Construction,
  Gauge,
  KeySquare,
  LogsIcon,
  User,
  Users,
  Workflow,
} from 'lucide-vue-next';
import { computed, ref } from 'vue';
import AppLogo from './AppLogo.vue';
import NavCompany from './NavCompany.vue';
import NavDebug from './NavDebug.vue';
import NavSecurity from './NavSecurity.vue';

const { scrollable, handleScroll } = useRememberScroll('sidebar-scroll');
const page = usePage();

const hasMenuPermission = (permissionName: string): boolean => {
  return (
    page.props.auth?.menuPermissions?.includes(permissionName) ||
    hasSuperuserRole.value
  );
};

const hasSuperuserRole = computed(() =>
  page.props.auth?.roles?.some((r) => r === 'Superusuario'),
);

const mainNavItems: NavItem[] = [
  {
    title: 'Tablero',
    href: dashboard(),
    icon: Gauge,
    hasPermission: true,
  },
];
const companyNavItems: NavItem[] = [
  {
    title: 'Entes',
    href: '/organizations',
    icon: Building,
    hasPermission: hasMenuPermission('read any organization'),
  },
  {
    title: 'Unidades Administrativas',
    href: '/organizational-units',
    icon: Workflow,
    hasPermission: hasMenuPermission('read any organizational unit'),
  },
];
const securityNavItems: NavItem[] = [
  {
    title: 'Permisos',
    href: '/permissions',
    icon: KeySquare,
    hasPermission: hasMenuPermission('read any permission'),
  },
  {
    title: 'Roles',
    href: '/roles',
    icon: Users,
    hasPermission: hasMenuPermission('read any role'),
  },
  {
    title: 'Usuarios',
    href: '/users',
    icon: User,
    hasPermission: hasMenuPermission('read any user'),
  },
];
const debugNavItems: NavItem[] = [
  {
    title: 'Trazas',
    href: '/activity-logs',
    icon: LogsIcon,
    hasPermission: hasMenuPermission('read any activity trace'),
  },
  {
    title: 'Depuración',
    href: '/log-files',
    icon: Bug,
    hasPermission: hasMenuPermission('read any system log'),
  },
  {
    title: 'Modo Mantenimiento',
    href: '/maintenance-mode',
    icon: Construction,
    hasPermission: hasMenuPermission('manage maintenance mode'),
  },
];

// Estado para NotificationsSheet
const notificationsSheetOpen = ref(false);
const unreadCount = computed(() => page.props.unreadNotifications?.length || 0);

const footerNavItems = computed<NavItem[]>(() => [
  {
    title: 'Notificaciones',
    href: '', // No se usa porque tiene onClick
    icon: Bell,
    badge: unreadCount.value,
    onClick: () => {
      notificationsSheetOpen.value = true;
    },
    hasPermission: true,
  },
]);
</script>

<template>
  <Sidebar collapsible="icon" variant="inset">
    <SidebarHeader>
      <SidebarMenu>
        <SidebarMenuItem>
          <SidebarMenuButton size="lg" as-child>
            <Link :href="dashboard()">
              <AppLogo />
            </Link>
          </SidebarMenuButton>
        </SidebarMenuItem>
      </SidebarMenu>
    </SidebarHeader>

    <SidebarContent ref="scrollable" @scroll="handleScroll">
      <NavMain :items="mainNavItems" />
      <NavCompany :items="companyNavItems" />
      <NavSecurity :items="securityNavItems" />
      <NavDebug :items="debugNavItems" />
    </SidebarContent>

    <SidebarFooter>
      <NavFooter :items="footerNavItems" />
      <NavUser />
    </SidebarFooter>
  </Sidebar>

  <!-- Sheet de Notificaciones -->
  <NotificationsSheet v-model:open="notificationsSheetOpen" />

  <slot />
</template>
