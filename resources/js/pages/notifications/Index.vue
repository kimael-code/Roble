<script setup lang="ts">
import NotificationController from '@/actions/App/Http/Controllers/NotificationController';
import {
  AlertDialog,
  AlertDialogAction,
  AlertDialogCancel,
  AlertDialogContent,
  AlertDialogDescription,
  AlertDialogFooter,
  AlertDialogHeader,
  AlertDialogTitle,
  AlertDialogTrigger,
} from '@/components/ui/alert-dialog';
import { Avatar, AvatarFallback, AvatarImage } from '@/components/ui/avatar';
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import { Card, CardContent } from '@/components/ui/card';
import { ScrollArea } from '@/components/ui/scroll-area';
import { Separator } from '@/components/ui/separator';
import {
  Tooltip,
  TooltipContent,
  TooltipProvider,
  TooltipTrigger,
} from '@/components/ui/tooltip';
import { getInitials } from '@/composables/useInitials';
import AppLayout from '@/layouts/AppLayout.vue';
import ContentLayout from '@/layouts/ContentLayout.vue';
import { BreadcrumbItem, Notification, Pagination } from '@/types';
import { Head, router, WhenVisible } from '@inertiajs/vue3';
import {
  Bell,
  BellOff,
  CalendarIcon,
  CheckCheckIcon,
  CheckCircle2Icon,
  CircleDotIcon,
  ClockIcon,
  Trash2Icon,
} from 'lucide-vue-next';
import { DateTime } from 'luxon';
import { computed, ref } from 'vue';

// Recargar datos
router.reload();

const props = defineProps<{
  notifications: Array<Notification>;
  pagination: Pagination<Notification>;
}>();

const breadcrumbs: BreadcrumbItem[] = [
  {
    title: 'Notificaciones',
    href: '/notifications',
  },
];

// Estadísticas computadas
const unreadCount = computed(
  () => props.notifications.filter((n) => !n.read_at).length,
);
const readCount = computed(
  () => props.notifications.filter((n) => n.read_at).length,
);
const totalCount = computed(() => props.notifications.length);
const hasUnread = computed(() => unreadCount.value > 0);
const hasNotifications = computed(() => totalCount.value > 0);

// Estado para confirmaciones
const isDeleting = ref(false);
const isDeletingAll = ref(false);

// Funciones de acción
function markAsReadOnly(notificationId: string) {
  router.visit(NotificationController.markAsReadOnly(notificationId), {
    method: 'put',
    preserveScroll: true,
  });
}

function markAllAsRead() {
  router.visit(NotificationController.markAllAsRead(), {
    method: 'post',
    preserveScroll: true,
  });
}

function deleteNotification(notificationId: string) {
  isDeleting.value = true;
  router.visit(NotificationController.destroy(notificationId), {
    method: 'delete',
    preserveScroll: true,
    onFinish: () => {
      isDeleting.value = false;
    },
  });
}

function deleteAllNotifications() {
  isDeletingAll.value = true;
  router.visit(NotificationController.destroyAll(), {
    method: 'delete',
    preserveScroll: true,
    onFinish: () => {
      isDeletingAll.value = false;
    },
  });
}

// Funciones de formateo
function formatFullDate(notification: Notification) {
  try {
    let timestamp: string =
      notification.data?.timestamp || notification.created_at || '';
    if (timestamp && typeof timestamp === 'string') {
      timestamp = timestamp.replace(' ', 'T');
    }
    return DateTime.fromISO(timestamp).toLocaleString(DateTime.DATETIME_MED);
  } catch {
    return 'Fecha no disponible';
  }
}

function formatRelativeTime(notification: Notification) {
  try {
    let timestamp: string =
      notification.data?.timestamp || notification.created_at || '';
    if (timestamp && typeof timestamp === 'string') {
      timestamp = timestamp.replace(' ', 'T');
    }
    return DateTime.fromISO(timestamp).toRelative();
  } catch {
    return '';
  }
}

function isUnread(notification: Notification): boolean {
  return !notification.read_at;
}
</script>

<template>
  <AppLayout :breadcrumbs>
    <Head title="Gestión de Notificaciones" />
    <ContentLayout title="Gestión de Notificaciones">
      <template #icon>
        <Bell />
      </template>

      <!-- Barra de estadísticas y acciones -->
      <div
        class="mb-6 flex flex-col gap-4 rounded-lg border bg-card/50 p-4 backdrop-blur-sm sm:flex-row sm:items-center sm:justify-between"
      >
        <!-- Estadísticas -->
        <div class="flex flex-wrap items-center gap-4">
          <div class="flex items-center gap-2">
            <div
              class="flex h-8 w-8 items-center justify-center rounded-full bg-primary/10"
            >
              <Bell class="h-4 w-4 text-primary" />
            </div>
            <div>
              <p class="text-xs text-muted-foreground">Total</p>
              <p class="text-lg font-semibold">{{ totalCount }}</p>
            </div>
          </div>
          <Separator orientation="vertical" class="hidden h-10 sm:block" />
          <div class="flex items-center gap-2">
            <div
              class="flex h-8 w-8 items-center justify-center rounded-full bg-blue-500/10"
            >
              <CircleDotIcon class="h-4 w-4 text-blue-500" />
            </div>
            <div>
              <p class="text-xs text-muted-foreground">Sin leer</p>
              <p class="text-lg font-semibold">{{ unreadCount }}</p>
            </div>
          </div>
          <Separator orientation="vertical" class="hidden h-10 sm:block" />
          <div class="flex items-center gap-2">
            <div
              class="flex h-8 w-8 items-center justify-center rounded-full bg-emerald-500/10"
            >
              <CheckCircle2Icon class="h-4 w-4 text-emerald-500" />
            </div>
            <div>
              <p class="text-xs text-muted-foreground">Leídas</p>
              <p class="text-lg font-semibold">{{ readCount }}</p>
            </div>
          </div>
        </div>

        <!-- Acciones globales -->
        <div class="flex flex-wrap gap-2">
          <Button
            v-if="hasUnread"
            variant="default"
            size="sm"
            @click="markAllAsRead"
          >
            <CheckCheckIcon class="mr-2 h-4 w-4" />
            Marcar todas como leídas
          </Button>

          <AlertDialog>
            <AlertDialogTrigger as-child>
              <Button
                v-if="hasNotifications"
                variant="destructive"
                size="sm"
                :disabled="isDeletingAll"
              >
                <Trash2Icon class="mr-2 h-4 w-4" />
                Eliminar todas
              </Button>
            </AlertDialogTrigger>
            <AlertDialogContent>
              <AlertDialogHeader>
                <AlertDialogTitle
                  >¿Eliminar todas las notificaciones?</AlertDialogTitle
                >
                <AlertDialogDescription>
                  Esta acción eliminará permanentemente todas tus notificaciones
                  ({{ totalCount }} en total). Esta acción no se puede deshacer.
                </AlertDialogDescription>
              </AlertDialogHeader>
              <AlertDialogFooter>
                <AlertDialogCancel>Cancelar</AlertDialogCancel>
                <AlertDialogAction
                  class="bg-destructive text-destructive-foreground hover:bg-destructive/90"
                  @click="deleteAllNotifications"
                >
                  Eliminar todas
                </AlertDialogAction>
              </AlertDialogFooter>
            </AlertDialogContent>
          </AlertDialog>
        </div>
      </div>

      <!-- Lista de notificaciones -->
      <ScrollArea class="h-[calc(100vh-350px)] rounded-lg border">
        <div class="space-y-2 p-4">
          <TransitionGroup name="notification">
            <Card
              v-for="notification in notifications"
              :key="notification.id"
              class="group relative overflow-hidden transition-all duration-300"
              :class="[
                isUnread(notification)
                  ? 'border-primary/30 bg-primary/5 shadow-sm hover:border-primary/50 hover:shadow-md'
                  : 'border-muted/50 bg-muted/20 opacity-75 hover:opacity-100',
              ]"
            >
              <!-- Indicador de estado no leído -->
              <div
                v-if="isUnread(notification)"
                class="absolute top-0 left-0 h-full w-1 bg-linear-to-b from-primary to-primary/50"
              />

              <CardContent class="p-4 pl-5">
                <div class="flex items-start gap-4">
                  <!-- Avatar -->
                  <Avatar class="h-12 w-12 shrink-0 ring-2 ring-background">
                    <AvatarImage
                      v-if="notification.data?.photoUrl"
                      :src="notification.data.photoUrl"
                    />
                    <AvatarFallback
                      :class="
                        isUnread(notification)
                          ? 'bg-primary/15 text-primary'
                          : 'bg-muted text-muted-foreground'
                      "
                    >
                      {{ getInitials(notification.data?.causer) }}
                    </AvatarFallback>
                  </Avatar>

                  <!-- Contenido principal -->
                  <div class="min-w-0 flex-1">
                    <!-- Header -->
                    <div class="flex items-start justify-between gap-2">
                      <div class="flex items-center gap-2">
                        <h4
                          class="truncate font-semibold"
                          :class="
                            isUnread(notification)
                              ? 'text-foreground'
                              : 'text-muted-foreground'
                          "
                        >
                          {{ notification.data?.causer }}
                        </h4>
                        <Badge
                          v-if="isUnread(notification)"
                          variant="default"
                          class="animate-pulse text-xs"
                        >
                          Nueva
                        </Badge>
                      </div>

                      <!-- Acciones -->
                      <div
                        class="flex shrink-0 items-center gap-1 opacity-0 transition-opacity group-hover:opacity-100"
                      >
                        <TooltipProvider>
                          <Tooltip>
                            <TooltipTrigger as-child>
                              <Button
                                v-if="isUnread(notification)"
                                variant="ghost"
                                size="icon"
                                class="h-8 w-8"
                                @click.stop="markAsReadOnly(notification.id)"
                              >
                                <CheckCircle2Icon
                                  class="h-4 w-4 text-emerald-500"
                                />
                              </Button>
                            </TooltipTrigger>
                            <TooltipContent>
                              <p>Marcar como leída</p>
                            </TooltipContent>
                          </Tooltip>
                        </TooltipProvider>

                        <AlertDialog>
                          <TooltipProvider>
                            <Tooltip>
                              <TooltipTrigger as-child>
                                <AlertDialogTrigger as-child>
                                  <Button
                                    variant="ghost"
                                    size="icon"
                                    class="h-8 w-8"
                                    :disabled="isDeleting"
                                  >
                                    <Trash2Icon
                                      class="h-4 w-4 text-destructive"
                                    />
                                  </Button>
                                </AlertDialogTrigger>
                              </TooltipTrigger>
                              <TooltipContent>
                                <p>Eliminar notificación</p>
                              </TooltipContent>
                            </Tooltip>
                          </TooltipProvider>
                          <AlertDialogContent>
                            <AlertDialogHeader>
                              <AlertDialogTitle
                                >¿Eliminar esta notificación?</AlertDialogTitle
                              >
                              <AlertDialogDescription>
                                Esta acción eliminará permanentemente esta
                                notificación y no se puede deshacer.
                              </AlertDialogDescription>
                            </AlertDialogHeader>
                            <AlertDialogFooter>
                              <AlertDialogCancel>Cancelar</AlertDialogCancel>
                              <AlertDialogAction
                                class="bg-destructive text-destructive-foreground hover:bg-destructive/90"
                                @click="deleteNotification(notification.id)"
                              >
                                Eliminar
                              </AlertDialogAction>
                            </AlertDialogFooter>
                          </AlertDialogContent>
                        </AlertDialog>
                      </div>
                    </div>

                    <!-- Mensaje -->
                    <p
                      class="mt-1 text-sm leading-relaxed"
                      :class="
                        isUnread(notification)
                          ? 'text-foreground/80'
                          : 'text-muted-foreground'
                      "
                    >
                      {{ notification.data?.message }}
                    </p>

                    <!-- Footer con fechas -->
                    <div class="mt-2 flex flex-wrap items-center gap-4 text-xs">
                      <div
                        class="flex items-center gap-1.5 text-muted-foreground"
                      >
                        <CalendarIcon class="h-3 w-3" />
                        <span>{{ formatFullDate(notification) }}</span>
                      </div>
                      <div
                        class="flex items-center gap-1.5 text-muted-foreground"
                      >
                        <ClockIcon class="h-3 w-3" />
                        <span>{{ formatRelativeTime(notification) }}</span>
                      </div>
                    </div>
                  </div>
                </div>
              </CardContent>
            </Card>
          </TransitionGroup>

          <!-- Paginación infinita -->
          <WhenVisible
            v-if="pagination && pagination.current_page < pagination.last_page"
            :params="{
              data: { page: pagination.current_page + 1 },
              only: ['notifications', 'pagination'],
            }"
          >
            <div class="py-4 text-center text-sm text-muted-foreground">
              Cargando más notificaciones...
            </div>
          </WhenVisible>

          <!-- Estado vacío -->
          <div
            v-if="!hasNotifications"
            class="flex flex-col items-center justify-center py-16 text-center"
          >
            <div
              class="mb-4 rounded-full bg-linear-to-br from-muted to-muted/50 p-6"
            >
              <BellOff class="h-12 w-12 text-muted-foreground" />
            </div>
            <h3 class="mb-2 text-lg font-semibold">Sin notificaciones</h3>
            <p class="max-w-sm text-sm text-muted-foreground">
              No tienes notificaciones. Cuando recibas nuevas actualizaciones
              aparecerán aquí.
            </p>
          </div>
        </div>
      </ScrollArea>
    </ContentLayout>
  </AppLayout>
</template>

<style scoped>
/* Animaciones de transición para el listado */
.notification-enter-active,
.notification-leave-active {
  transition: all 0.3s ease;
}

.notification-enter-from {
  opacity: 0;
  transform: translateX(-20px);
}

.notification-leave-to {
  opacity: 0;
  transform: translateX(20px);
}

.notification-move {
  transition: transform 0.3s ease;
}
</style>
