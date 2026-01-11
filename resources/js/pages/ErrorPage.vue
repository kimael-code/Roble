<script setup lang="ts">
import AppLogo from '@/components/AppLogo.vue';
import { Button } from '@/components/ui/button';
import { Card, CardContent, CardDescription, CardFooter, CardHeader, CardTitle } from '@/components/ui/card';
import { ArrowLeft } from 'lucide-vue-next';
import { computed } from 'vue';

const props = defineProps<{
  status: number;
  message: string;
}>();

const title = computed(() => {
  return {
    503: '503',
    500: '500',
    404: '404',
    403: '403',
  }[props.status];
});

const subtitle = computed(() => {
  return {
    503: 'Disculpe, estamos haciendo labores de mantenimiento. Por favor, vuelva a visitarnos más tarde.',
    500: '¡Uy!, algo salió mal.',
    404: 'Disculpe, no fue posible localizar el recurso solicitado.',
    403: 'Disculpe, no tiene permitido ejecutar esta acción.',
  }[props.status];
});

const reason = computed(() => {
  return {
    503: props.message,
    500: props.message,
    404: props.message,
    403: props.message,
  }[props.status];
});

function goBack() {
  window.history.back();
}
</script>

<template>
  <div
    class="flex min-h-screen items-center justify-center bg-linear-to-br from-neutral-50 to-neutral-100 p-4 dark:from-neutral-900 dark:to-neutral-800"
  >
    <Card class="w-full max-w-md">
      <CardHeader class="text-center">
        <div class="mb-4 flex justify-center">
          <div class="mb-4 flex flex-col items-center justify-center gap-y-2">
            <AppLogo vertical />
          </div>
        </div>
        <CardTitle class="text-3xl font-bold tracking-tight">
          {{ title }}
        </CardTitle>
        <CardDescription class="mt-2 text-lg">
          {{ subtitle }}
        </CardDescription>
      </CardHeader>

      <CardContent class="space-y-4">
        <div class="rounded-lg bg-neutral-100 p-4 dark:bg-neutral-800">
          <p class="text-sm text-amber-600 dark:text-amber-400">
            {{ reason }}
          </p>
        </div>
      </CardContent>

      <CardFooter class="flex justify-center">
        <Button variant="default" @click="goBack">
          <ArrowLeft class="mr-2 h-4 w-4" />
          Volver atrás
        </Button>
      </CardFooter>
    </Card>
  </div>
</template>
