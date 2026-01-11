<script setup lang="ts">
import UserInfo from '@/components/UserInfo.vue';
import {
  DropdownMenuGroup,
  DropdownMenuItem,
  DropdownMenuLabel,
  DropdownMenuSeparator,
} from '@/components/ui/dropdown-menu';
import useAppVersion from '@/composables/useAppVersion';
import { logout } from '@/routes';
import { index as notificationsIndex } from '@/routes/notifications';
import { edit } from '@/routes/profile';
import type { User } from '@/types';
import { Link, router } from '@inertiajs/vue3';
import { Bell, GitBranch, LogOut, Settings } from 'lucide-vue-next';

interface Props {
  user: User;
}

const handleLogout = () => {
  router.flushAll();
};

const { fullVersion } = useAppVersion();

defineProps<Props>();
</script>

<template>
  <DropdownMenuLabel class="p-0 font-normal">
    <div class="flex items-center gap-2 px-1 py-1.5 text-left text-sm">
      <UserInfo :user="user" :show-email="true" />
    </div>
  </DropdownMenuLabel>
  <DropdownMenuSeparator />
  <DropdownMenuGroup>
    <DropdownMenuItem :as-child="true">
      <Link class="block w-full" :href="edit()" prefetch as="button">
        <Settings class="mr-2 h-4 w-4" />
        Configuración
      </Link>
    </DropdownMenuItem>
    <DropdownMenuItem :as-child="true">
      <Link
        class="block w-full"
        :href="notificationsIndex()"
        prefetch
        as="button"
      >
        <Bell class="mr-2 h-4 w-4" />
        Notificaciones
      </Link>
    </DropdownMenuItem>
  </DropdownMenuGroup>
  <DropdownMenuSeparator />
  <DropdownMenuItem :as-child="true">
    <Link
      class="block w-full"
      :href="logout()"
      @click="handleLogout"
      as="button"
      data-test="logout-button"
    >
      <LogOut class="mr-2 h-4 w-4" />
      Salir
    </Link>
  </DropdownMenuItem>
  
  <!-- Versión al final del menú -->
  <DropdownMenuSeparator />
  <DropdownMenuItem disabled class="cursor-default opacity-70">
    <GitBranch class="mr-2 h-4 w-4" />
    v{{ fullVersion }}
  </DropdownMenuItem>
</template>
