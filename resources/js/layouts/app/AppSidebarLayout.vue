<script setup lang="ts">
import AppContent from '@/components/AppContent.vue';
import AppShell from '@/components/AppShell.vue';
import AppSidebar from '@/components/AppSidebar.vue';
import AppSidebarHeader from '@/components/AppSidebarHeader.vue';
import useStringUtils from '@/composables/useStringUtils';
import type {
  BreadcrumbItemType,
  NotificationData,
  NotificationFlashMessage,
} from '@/types';
import { router, usePage } from '@inertiajs/vue3';
import { useEchoModel } from '@laravel/echo-vue';
import { watchImmediate } from '@vueuse/core';
import { DateTime } from 'luxon';
import { toast } from 'vue-sonner';
import 'vue-sonner/style.css';

interface Props {
  breadcrumbs?: BreadcrumbItemType[];
}

withDefaults(defineProps<Props>(), {
  breadcrumbs: () => [],
});

const page = usePage();
const { channel } = useEchoModel('App.Models.User', page.props.auth.user.id);
const { removeEndDot } = useStringUtils();
const flashMessage = page.props?.flash?.message;

channel().notification((n: NotificationData) => {
  toast.info(n.causer, {
    description: `${removeEndDot(n.message)}, ${DateTime.fromISO(n?.timestamp).toRelative()}`,
  });
});
watchImmediate(
  () => flashMessage,
  () => {
    if (!flashMessage) return;

    // Si es un string simple, mostrarlo como mensaje info genérico
    if (typeof flashMessage === 'string') {
      toast.info(flashMessage, {
        onAutoClose: () => router.reload({ only: ['flash'] }),
        onDismiss: () => router.reload({ only: ['flash'] }),
      });
      return;
    }

    // Si no es un objeto, no tiene sentido seguir
    if (typeof flashMessage !== 'object') return;

    // Verifica que tenga las propiedades esperadas de NotificationFlashMessage
    const hasTitleAndMessage =
      'title' in flashMessage &&
      'content' in flashMessage &&
      'type' in flashMessage;

    if (hasTitleAndMessage) {
      const { title, content, type } = flashMessage as NotificationFlashMessage;

      switch (type) {
        case 'success':
          toast.success(title, {
            description: content,
            onAutoClose: () => router.reload({ only: ['flash'] }),
            onDismiss: () => router.reload({ only: ['flash'] }),
          });
          break;
        case 'info':
          toast.info(title, {
            description: content,
            onAutoClose: () => router.reload({ only: ['flash'] }),
            onDismiss: () => router.reload({ only: ['flash'] }),
          });
          break;
        case 'warning':
          toast.warning(title, {
            description: content,
            onAutoClose: () => router.reload({ only: ['flash'] }),
            onDismiss: () => router.reload({ only: ['flash'] }),
          });
          break;
        case 'danger':
          toast.error(title, {
            description: content,
            onAutoClose: () => router.reload({ only: ['flash'] }),
            onDismiss: () => router.reload({ only: ['flash'] }),
          });
          break;

        default:
          toast(title, {
            description: content,
            onAutoClose: () => router.reload({ only: ['flash'] }),
            onDismiss: () => router.reload({ only: ['flash'] }),
          });
          break;
      }
    }
  },
);
</script>

<template>
  <AppShell variant="sidebar">
    <AppSidebar />
    <AppContent
      variant="sidebar"
      class="h-[calc(100vh-1rem)] overflow-x-hidden"
    >
      <AppSidebarHeader :breadcrumbs="breadcrumbs" />
      <slot />
    </AppContent>
  </AppShell>
</template>
