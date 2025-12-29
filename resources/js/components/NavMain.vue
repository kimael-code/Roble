<script setup lang="ts">
import {
  SidebarGroup,
  SidebarGroupLabel,
  SidebarMenu,
  SidebarMenuButton,
  SidebarMenuItem,
  SidebarMenuSub,
  SidebarMenuSubButton,
  SidebarMenuSubItem,
} from '@/components/ui/sidebar';
import { urlIsActive } from '@/lib/utils';
import { type NavItem } from '@/types';
import { Link, usePage } from '@inertiajs/vue3';
import { ChevronRight } from 'lucide-vue-next';
import {
  Collapsible,
  CollapsibleContent,
  CollapsibleTrigger,
} from './ui/collapsible';

defineProps<{
  items: NavItem[];
}>();

const page = usePage();
</script>

<template>
  <SidebarGroup class="px-2 py-0">
    <SidebarGroupLabel>Inicio</SidebarGroupLabel>
    <SidebarMenu>
      <template v-for="item in items" :key="item.title">
        <SidebarMenuItem
          v-if="item.hasPermission && !(item.items && item.items.length > 0)"
        >
          <SidebarMenuButton
            as-child
            :is-active="urlIsActive(item.href, page.url)"
            :tooltip="item.title"
          >
            <Link :href="item.href">
              <component :is="item.icon" />
              <span>{{ item.title }}</span>
            </Link>
          </SidebarMenuButton>
        </SidebarMenuItem>
        <template v-else-if="item.hasPermission">
          <Collapsible
            as-child
            :key="item.title"
            :default-open="urlIsActive(item.href, page.url)"
            class="group/collapsible"
          >
            <SidebarMenuItem v-if="item.hasPermission">
              <CollapsibleTrigger
                v-if="item.items && item.items.length > 0"
                as-child
              >
                <SidebarMenuButton
                  :tooltip="item.title"
                  :is-active="urlIsActive(item.href, page.url)"
                >
                  <component :is="item.icon" />
                  <span>{{ item.title }}</span>
                  <ChevronRight
                    class="ml-auto transition-transform duration-200 group-data-[state=open]/collapsible:rotate-90"
                  />
                </SidebarMenuButton>
              </CollapsibleTrigger>
              <CollapsibleContent>
                <SidebarMenuSub>
                  <SidebarMenuSubItem
                    v-for="subItem in item.items"
                    :key="subItem.title"
                  >
                    <SidebarMenuSubButton
                      as-child
                      :is-active="urlIsActive(subItem.href, page.url)"
                    >
                      <Link :href="subItem.href">
                        <span>{{ subItem.title }}</span>
                      </Link>
                    </SidebarMenuSubButton>
                  </SidebarMenuSubItem>
                </SidebarMenuSub>
              </CollapsibleContent>
            </SidebarMenuItem>
          </Collapsible>
        </template>
      </template>
    </SidebarMenu>
  </SidebarGroup>
</template>
