<script setup lang="ts">
import NotificationController from '@/actions/App/Http/Controllers/NotificationController';
import { Button } from '@/components/ui/button';
import { Card, CardContent } from '@/components/ui/card';
import { ScrollArea } from '@/components/ui/scroll-area';
import { Separator } from '@/components/ui/separator';
import {
  Sheet,
  SheetContent,
  SheetDescription,
  SheetHeader,
  SheetTitle,
} from '@/components/ui/sheet';
import { getInitials } from '@/composables/useInitials';
import { router, usePage } from '@inertiajs/vue3';
import { useEchoModel } from '@laravel/echo-vue';
import { CalendarIcon, CheckCheckIcon, ClockIcon } from 'lucide-vue-next';
import { DateTime } from 'luxon';
import { computed, ref } from 'vue';
import { Avatar, AvatarFallback, AvatarImage } from './ui/avatar';

interface Props {
  open: boolean;
}

defineProps<Props>();

defineEmits<{
  'update:open': [value: boolean];
}>();

const page = ref(usePage());
const unreadNotifications = computed(
  () => page.value.props.unreadNotifications,
);
const unreadCount = computed(() => page.value.props.unreadNotifications.length);

// Echo para actualizaciones en tiempo real
const { channel } = useEchoModel(
  'App.Models.User',
  page.value.props.auth.user.id,
);

channel().notification(() =>
  router.reload({
    only: ['unreadNotifications'],
  }),
);

function markAsRead(notificationID: string) {
  router.visit(NotificationController.markAsRead(notificationID), {
    method: 'put',
    preserveScroll: true,
    preserveState: true,
  });
}

function markAllAsRead() {
  router.visit(NotificationController.markAllAsRead(), {
    method: 'post',
    preserveScroll: true,
    preserveState: true,
  });
}

function formatFullDate(notification: any) {
  try {
    // Intentar con timestamp de data, sino usar created_at de la notificación
    let timestamp = notification.data?.timestamp || notification.created_at;

    // Convertir formato DB (YYYY-MM-DD HH:mm:ss) a ISO (YYYY-MM-DDTHH:mm:ss)
    if (timestamp && typeof timestamp === 'string') {
      timestamp = timestamp.replace(' ', 'T');
    }

    return DateTime.fromISO(timestamp).toLocaleString(DateTime.DATETIME_MED);
  } catch {
    return 'Fecha no disponible';
  }
}

function formatRelativeTime(notification: any) {
  try {
    let timestamp = notification.data?.timestamp || notification.created_at;

    // Convertir formato DB a ISO
    if (timestamp && typeof timestamp === 'string') {
      timestamp = timestamp.replace(' ', 'T');
    }

    return DateTime.fromISO(timestamp).toRelative();
  } catch {
    return '';
  }
}
</script>

<template>
  <Sheet :open="open" @update:open="$emit('update:open', $event)">
    <SheetContent side="right">
      <SheetHeader class="space-y-3">
        <SheetTitle>Notificaciones</SheetTitle>
        <SheetDescription>
          <span v-if="unreadCount > 0">
            Tienes
            <span class="font-semibold text-foreground">{{ unreadCount }}</span>
            notificación{{ unreadCount !== 1 ? 'es' : '' }} sin leer
          </span>
          <span v-else class="text-muted-foreground">
            No tienes notificaciones pendientes
          </span>
        </SheetDescription>
      </SheetHeader>

      <Separator />

      <div class="space-y-4 p-4">
        <!-- Botón marcar todas como leídas -->
        <Button
          v-if="unreadCount > 0"
          variant="default"
          size="sm"
          class="w-full"
          @click="markAllAsRead"
        >
          <CheckCheckIcon class="mr-2 h-4 w-4" />
          Marcar todas como leídas
        </Button>

        <!-- Lista de notificaciones -->
        <ScrollArea class="h-[calc(100vh-240px)]">
          <div class="space-y-3">
            <Card
              v-for="notification in unreadNotifications"
              :key="notification.id"
              class="cursor-pointer transition-all hover:border-primary/50 hover:shadow-md p-2 "
              @click="markAsRead(notification.id)"
            >
              <CardContent>
                <div class="flex gap-4">
                  <!-- Avatar -->
                  <Avatar class="h-10 w-10 shrink-0">
                    <AvatarImage
                      v-if="notification.data.photoUrl"
                      :src="notification.data.photoUrl"
                    />
                    <AvatarFallback class="bg-primary/10 text-primary">
                      {{ getInitials(notification.data.causer) }}
                    </AvatarFallback>
                  </Avatar>

                  <!-- Contenido -->
                  <div class="flex-1">
                    <!-- Header con nombre y badge -->
                    <div class="flex items-start justify-between gap-2">
                      <h4 class="text-sm leading-tight font-semibold">
                        {{ notification.data?.causer }}
                      </h4>
                      <!-- Badge como punto azul -->
                      <div class="h-2 w-2 shrink-0 rounded-full bg-primary" />
                    </div>

                    <!-- Mensaje -->
                    <p class="text-sm leading-relaxed text-muted-foreground">
                      {{ notification.data?.message }}
                    </p>

                    <!-- Footer con fecha y hora -->
                    <div class="flex flex-col gap-1 pt-1">
                      <div
                        class="flex items-center gap-1.5 text-xs text-muted-foreground"
                      >
                        <CalendarIcon class="h-3 w-3" />
                        <span>{{ formatFullDate(notification) }}</span>
                      </div>
                      <div
                        class="flex items-center gap-1.5 text-xs text-muted-foreground"
                      >
                        <ClockIcon class="h-3 w-3" />
                        <span>{{ formatRelativeTime(notification) }}</span>
                      </div>
                    </div>
                  </div>
                </div>
              </CardContent>
            </Card>

            <!-- Estado vacío -->
            <div
              v-if="unreadCount === 0"
              class="flex flex-col items-center justify-center py-16 text-center"
            >
              <div class="mb-4 rounded-full bg-muted p-6">
                <CheckCheckIcon class="h-12 w-12 text-muted-foreground" />
              </div>
              <h3 class="mb-2 text-lg font-semibold">¡Todo al día!</h3>
              <p class="max-w-sm text-sm text-muted-foreground">
                No tienes notificaciones pendientes. Cuando recibas nuevas
                actualizaciones aparecerán aquí.
              </p>
            </div>
          </div>
        </ScrollArea>
      </div>
    </SheetContent>
  </Sheet>
</template>
