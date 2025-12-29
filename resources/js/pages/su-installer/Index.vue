<script setup lang="ts">
import { wizard } from '@/routes/su-installer';
import { Button } from '@/components/ui/button';
import {
  Card,
  CardContent,
  CardDescription,
  CardHeader,
  CardTitle,
} from '@/components/ui/card';
import { Separator } from '@/components/ui/separator';
import { Form, Head } from '@inertiajs/vue3';
import { LoaderCircleIcon } from 'lucide-vue-next';
import StarSkyBackground from '@/components/StarSkyBackground.vue';
</script>

<template>
  <StarSkyBackground />

  <div
    class="relative z-10 flex min-h-screen items-center justify-center bg-transparent opacity-100 transition-opacity duration-750 dark:bg-transparent starting:opacity-0"
  >
    <Head title="Instalador" />

    <Card class="mx-4 w-full max-w-xl">
      <CardHeader class="text-center">
        <CardTitle class="text-2xl font-bold">
          Bienvenido al Instalador del Superusuario en
          {{ $page.props.name }}
        </CardTitle>
        <CardDescription>
          Gracias por elegir {{ $page.props.name }}. Siga los pasos para
          configurar su sistema.
        </CardDescription>
      </CardHeader>
      <CardContent>
        <div class="space-y-6">
          <Separator />
          <div class="text-center">
            <p class="text-gray-600 dark:text-gray-400">
              Haga clic en el siguiente botón para comenzar el proceso de
              instalación.
            </p>
          </div>
          <Form :action="wizard()" v-slot="{ processing }">
            <Button type="submit" class="w-full" :disabled="processing">
              <LoaderCircleIcon
                v-if="processing"
                class="h-4 w-4 animate-spin"
              />
              COMENZAR
            </Button>
          </Form>
        </div>
      </CardContent>
    </Card>
  </div>
</template>
