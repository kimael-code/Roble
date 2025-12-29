<script setup lang="ts">
import NotificationController from '@/actions/App/Http/Controllers/NotificationController';
import { getInitials } from '@/composables/useInitials';
import { router, usePage } from '@inertiajs/vue3';
import { useEchoModel } from '@laravel/echo-vue';
import { BellRingIcon, CalendarClockIcon } from 'lucide-vue-next';
import { DateTime } from 'luxon';
import { computed, ref } from 'vue';
import { Avatar, AvatarFallback, AvatarImage } from './ui/avatar';
import { Badge } from './ui/badge';
import { Button } from './ui/button';
import { Popover, PopoverContent, PopoverTrigger } from './ui/popover';
import { ScrollArea } from './ui/scroll-area';

const page = ref(usePage());
const unreadNotifications = computed(
  () => page.value.props.unreadNotifications,
);
const unreadCount = computed(() => page.value.props.unreadNotifications.length);
const { channel } = useEchoModel(
  'App.Models.User',
  page.value.props.auth.user.id,
);

function markAsRead(notificationID: string) {
  router.visit(NotificationController.markAsRead(notificationID), {
    method: 'put',
  });
}

channel().notification(() =>
  router.reload({
    only: ['unreadNotifications'],
  }),
);

router.reload();
</script>

<template>
  <div class="mr-8 ml-auto">
    <Popover>
      <PopoverTrigger as-child>
        <div class="relative">
          <Button v-show="unreadCount" variant="ghost" size="icon">
            <BellRingIcon />
          </Button>
          <Badge
            v-if="unreadCount > 0"
            class="absolute -top-1 -right-1 flex h-5 w-5 items-center justify-center rounded-full p-0"
            >{{ unreadCount }}</Badge
          >
        </div>
      </PopoverTrigger>
      <PopoverContent class="w-96">
        <div class="grid gap-4">
          <div class="space-y-2">
            <h4 class="leading-none font-medium">Notificaciones</h4>
            <ScrollArea class="h-80">
              <div
                v-for="(notification, i) in unreadNotifications"
                class="mb-2 flex cursor-pointer justify-normal space-x-4 rounded-md transition-colors duration-200 hover:bg-gray-100 dark:hover:bg-gray-800"
                :key="i"
                @click="markAsRead(notification.id)"
              >
                <Avatar>
                  <AvatarImage
                    v-if="notification.data.photoUrl"
                    :src="notification.data.photoUrl"
                  />
                  <AvatarFallback>{{
                    getInitials(notification.data.causer)
                  }}</AvatarFallback>
                </Avatar>
                <div class="space-y-1">
                  <h4 class="text-sm font-semibold">
                    {{ notification.data?.causer }}
                  </h4>
                  <p class="text-sm">{{ notification.data?.message }}</p>
                  <div class="flex items-center pt-2">
                    <CalendarClockIcon class="mr-2 h-4 w-4 opacity-70" />
                    <span class="text-xs text-muted-foreground">
                      {{
                        DateTime.fromISO(
                          notification.data?.timestamp,
                        ).toRelative()
                      }}
                    </span>
                  </div>
                </div>
              </div>
            </ScrollArea>
          </div>
        </div>
      </PopoverContent>
    </Popover>
  </div>
</template>
