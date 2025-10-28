<script setup lang="ts">
import { Avatar, AvatarFallback, AvatarImage } from '@/components/ui/avatar';
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import { ScrollArea } from '@/components/ui/scroll-area';
import { getInitials } from '@/composables/useInitials';
import AppLayout from '@/layouts/AppLayout.vue';
import ContentLayout from '@/layouts/ContentLayout.vue';
import { BreadcrumbItem, Notification, Pagination } from '@/types';
import { Head, router, WhenVisible } from '@inertiajs/vue3';
import { Bell, CalendarClockIcon, CheckIcon } from 'lucide-vue-next';
import { DateTime } from 'luxon';
import NotificationController from "@/actions/App/Http/Controllers/NotificationController";

router.reload();

defineProps<{
  notifications: Array<Notification>;
  pagination: Pagination<Notification>;
}>();

const breadcrumbs: BreadcrumbItem[] = [
  {
    title: 'Notificaciones',
    href: '/notifications',
  },
];

function markAsRead(notificationID: string) {
  router.visit(NotificationController.markAsRead(notificationID), {
    method: 'put',
  });
}

function markAllAsRead() {
  router.visit(NotificationController.markAllAsRead(), {
    method: 'post',
  });
}
</script>

<template>
  <AppLayout :breadcrumbs>
    <Head title="Notificaciones" />
    <ContentLayout title="Notificaciones">
      <template #icon>
        <Bell />
      </template>
      <div class="flex items-center justify-end px-2 py-4">
        <Button class="ml-3" @click="markAllAsRead">
          <CheckIcon class="mr-2 h-4 w-4" />
          Marcar todas como leídas
        </Button>
      </div>
      <ScrollArea class="m-3 h-96 rounded-md border">
        <div
          v-for="(notification, i) in notifications"
          class="flex cursor-pointer justify-normal space-x-4 rounded-md p-2 transition-colors duration-200 hover:bg-gray-100 dark:hover:bg-gray-800"
          :key="i"
          @click="markAsRead(notification.id)"
        >
          <Avatar>
            <AvatarImage v-if="notification.data.photoUrl" :src="notification.data.photoUrl" />
            <AvatarFallback>{{ getInitials(notification.data.causer) }}</AvatarFallback>
          </Avatar>
          <div class="space-y-1">
            <h4 class="text-sm font-semibold">{{ notification.data?.causer }}</h4>
            <p class="text-sm">{{ notification.data?.message }}</p>
            <div class="flex items-center pt-2">
              <CalendarClockIcon class="mr-2 h-4 w-4 opacity-70" />
              <span class="text-xs text-muted-foreground"> {{ DateTime.fromISO(notification.data?.timestamp).toRelative() }} </span>
              <Badge v-if="!notification.read_at" class="ml-0.5">no leída</Badge>
            </div>
          </div>
        </div>
        <WhenVisible :params="{ data: { page: pagination?.current_page + 1 }, only: ['notifications', 'pagination'] }">
          <div class="text-muted">No hay más registros</div>
        </WhenVisible>
      </ScrollArea>
    </ContentLayout>
  </AppLayout>
</template>
