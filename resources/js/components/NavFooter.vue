<script setup lang="ts">
import { Badge } from '@/components/ui/badge';
import {
  SidebarGroup,
  SidebarGroupContent,
  SidebarMenu,
  SidebarMenuButton,
  SidebarMenuItem,
} from '@/components/ui/sidebar';
import { toUrl } from '@/lib/utils';
import { type NavItem } from '@/types';

interface Props {
  items: NavItem[];
  class?: string;
}

defineProps<Props>();
</script>

<template>
  <SidebarGroup
    :class="`group-data-[collapsible=icon]:p-0 ${$props.class || ''}`"
  >
    <SidebarGroupContent>
      <SidebarMenu>
        <SidebarMenuItem v-for="item in items" :key="item.title">
          <!-- Item con onClick (sin href) -->
          <SidebarMenuButton
            v-if="item.onClick"
            class="relative text-sidebar-foreground hover:bg-sidebar-accent"
            @click="item.onClick"
          >
            <component :is="item.icon" />
            <span>{{ item.title }}</span>
            <Badge
              v-if="item.badge && item.badge > 0"
              class="ml-auto h-5 min-w-5 px-1"
              variant="default"
            >
              {{ item.badge }}
            </Badge>
          </SidebarMenuButton>

          <!-- Item con href (link externo) -->
          <SidebarMenuButton
            v-else
            class="text-sidebar-foreground hover:bg-sidebar-accent"
            as-child
          >
            <a
              :href="toUrl(item.href)"
              target="_blank"
              rel="noopener noreferrer"
            >
              <component :is="item.icon" />
              <span>{{ item.title }}</span>
            </a>
          </SidebarMenuButton>
        </SidebarMenuItem>
      </SidebarMenu>
    </SidebarGroupContent>
  </SidebarGroup>
</template>
