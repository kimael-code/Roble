<script setup lang="ts">
import NavFooter from '@/components/NavFooter.vue';
import NavMain from '@/components/NavMain.vue';
import NavUser from '@/components/NavUser.vue';
import {
    Sidebar,
    SidebarContent,
    SidebarFooter,
    SidebarHeader,
    SidebarMenu,
    SidebarMenuButton,
    SidebarMenuItem,
} from '@/components/ui/sidebar';
import { dashboard } from '@/routes';
import { useRememberScroll } from '@/composables/useRememberScroll';
import { type NavItem } from '@/types';
import { Link, usePage } from '@inertiajs/vue3';
import { Bug, Building, Construction, FolderGit2, KeySquare, LayoutGrid, LogsIcon, User, Users, Workflow } from 'lucide-vue-next';
import { computed } from 'vue';
import AppLogo from './AppLogo.vue';
import NavCompany from './NavCompany.vue';
import NavDebug from './NavDebug.vue';
import NavSecurity from './NavSecurity.vue';

const { scrollable, handleScroll } = useRememberScroll('sidebar-scroll');
const page = usePage();
const hasSuperuserRole = computed(() => page.props.auth?.user?.roles?.some((r) => r.id == '1'));

const mainNavItems: NavItem[] = [
  {
    title: 'Tablero',
    href: dashboard(),
    icon: LayoutGrid,
  },
];
const companyNavItems: NavItem[] = [
  {
    title: 'Entes',
    href: '/organizations',
    icon: Building,
    hasPermission: page.props.auth?.menu.includes('read any organization') || hasSuperuserRole.value,
  },
  {
    title: 'Unidades Administrativas',
    href: '/organizational-units',
    icon: Workflow,
    hasPermission: page.props.auth?.menu.includes('read any organizational unit') || hasSuperuserRole.value,
  },
];
const securityNavItems: NavItem[] = [
  {
    title: 'Permisos',
    href: '/permissions',
    icon: KeySquare,
    hasPermission: page.props.auth?.menu.includes('read any permission') || hasSuperuserRole.value,
  },
  {
    title: 'Roles',
    href: '/roles',
    icon: Users,
    hasPermission: page.props.auth?.menu.includes('read any role') || hasSuperuserRole.value,
  },
  {
    title: 'Usuarios',
    href: '/users',
    icon: User,
    hasPermission: page.props.auth?.menu.includes('read any user') || hasSuperuserRole.value,
  },
];
const debugNavItems: NavItem[] = [
  {
    title: 'Trazas',
    href: '/activity-logs',
    icon: LogsIcon,
    hasPermission: page.props.auth?.menu.includes('read any activity trace') || hasSuperuserRole.value,
  },
  {
    title: 'Depuración',
    href: '/log-files',
    icon: Bug,
    hasPermission: page.props.auth?.menu.includes('read any system log') || hasSuperuserRole.value,
  },
  {
    title: 'Modo Mantenimiento',
    href: '/maintenance-mode',
    icon: Construction,
    hasPermission: page.props.auth?.menu.includes('manage maintenance mode') || hasSuperuserRole.value,
  },
];

const footerNavItems: NavItem[] = [
  {
    title: 'Github Repo',
    href: 'https://github.com/kimael-code/roble-vue-tailwind',
    icon: FolderGit2,
  },
];
</script>

<template>
  <Sidebar collapsible="icon" variant="floating">
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
  <slot />
</template>

<style lang="css" scoped>
/* Sólo para navegadores basados en Chromium (Chrome, Opera, Brave, Edge...) */

/* Estilo general para la barra de desplazamiento */
::-webkit-scrollbar {
  width: 4px;
  height: 8px;
}

/* Fondo de la barra de desplazamiento */
::-webkit-scrollbar-track {
  border-radius: 10px;
}

/* El "pulgar" o la parte arrastrable de la barra de desplazamiento */
::-webkit-scrollbar-thumb {
  background: #888;
  border-radius: 10px;
}

/* El "pulgar" al pasar el ratón por encima */
::-webkit-scrollbar-thumb:hover {
  background: #555;
}
</style>
