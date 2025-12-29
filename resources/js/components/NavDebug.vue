<script setup lang="ts">
import {
  SidebarGroup,
  SidebarGroupLabel,
  SidebarMenu,
  SidebarMenuButton,
  SidebarMenuItem,
} from '@/components/ui/sidebar';
import { urlIsActive } from '@/lib/utils';
import { type NavItem } from '@/types';
import { Link, usePage } from '@inertiajs/vue3';
import { computed } from 'vue';

const props = defineProps<{
  items: NavItem[];
}>();

const page = usePage();
const show = computed(() =>
  props.items.some((it) => it.hasPermission === true),
);
</script>

<template>
  <SidebarGroup v-if="show" class="px-2 py-0">
    <SidebarGroupLabel>Monitoreo</SidebarGroupLabel>
    <SidebarMenu>
      <template v-for="item in items" :key="item.title">
        <SidebarMenuItem v-if="item.hasPermission">
          <SidebarMenuButton
            as-child
            :is-active="urlIsActive(item.href, page.url)"
          >
            <Link :href="item.href">
              <component :is="item.icon" />
              <span>{{ item.title }}</span>
            </Link>
          </SidebarMenuButton>
        </SidebarMenuItem>
      </template>
    </SidebarMenu>
  </SidebarGroup>
</template>
