<script setup lang="ts">
import { Button } from '@/components/ui/button';
import { Card, CardContent, CardDescription, CardHeader, CardTitle } from '@/components/ui/card';
import { Separator } from '@/components/ui/separator';
import { Head, useForm } from '@inertiajs/vue3';
import { LoaderCircleIcon } from 'lucide-vue-next';

const form = useForm({});

const submit = () => {
  form.get(route('installer.wizard'));
};
</script>

<template>
  <div
    class="flex min-h-screen items-center justify-center bg-gray-100 opacity-100 transition-opacity duration-750 dark:bg-gray-900 starting:opacity-0"
  >
    <Head title="Instalador" />

    <Card class="mx-4 w-full max-w-lg">
      <CardHeader class="text-center">
        <CardTitle class="text-2xl font-bold"> Bienvenido al Instalador de Roble </CardTitle>
        <CardDescription> Gracias por elegir Roble. Sigue los pasos para configurar tu aplicación. </CardDescription>
      </CardHeader>
      <CardContent>
        <div class="space-y-6">
          <Separator />
          <div class="text-center">
            <p class="text-gray-600 dark:text-gray-400">Haz clic en el botón de abajo para comenzar el proceso de instalación.</p>
          </div>
          <form @submit.prevent="submit">
            <Button type="submit" class="w-full" :disabled="form.processing">
              <LoaderCircleIcon v-if="form.processing" class="h-4 w-4 animate-spin" />
              Comenzar Instalación
            </Button>
          </form>
        </div>
      </CardContent>
    </Card>
  </div>
</template>
