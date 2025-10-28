<script setup lang="ts">
import InputError from '@/components/InputError.vue';
import { Alert, AlertDescription } from '@/components/ui/alert';
import { Button } from '@/components/ui/button';
import { Card, CardContent, CardFooter, CardHeader, CardTitle } from '@/components/ui/card';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import AppLayout from '@/layouts/AppLayout.vue';
import ContentLayout from '@/layouts/ContentLayout.vue';
import { BreadcrumbItem } from '@/types';
import { Head, usePage } from '@inertiajs/vue3';
import { useForm } from 'laravel-precognition-vue-inertia';
import { ConstructionIcon, Info, LoaderCircleIcon } from 'lucide-vue-next';
import MaintenanceController from "@/actions/App/Http/Controllers/Monitoring/MaintenanceController";

defineProps<{
  status: boolean;
}>();

const breadcrumbs: BreadcrumbItem[] = [
  {
    title: 'Modo Mantenimiento',
    href: '/maintenance-mode',
  },
];

const form = useForm('post', MaintenanceController.toggle().url, {
  secret: '',
});

const toggleMaintenance = () => {
  form.submit({
    preserveScroll: true,
    onSuccess: () => form.reset(),
  });
};
</script>

<template>
  <AppLayout :breadcrumbs="breadcrumbs">
    <Head title="Modo Mantenimiento" />

    <ContentLayout title="Modo Mantenimiento">
      <template #icon>
        <ConstructionIcon />
      </template>
      <Card>
        <CardHeader>
          <CardTitle>Configuración de Mantenimiento</CardTitle>
        </CardHeader>
        <CardContent>
          <Alert v-if="$page.props.flash?.message" class="mb-4">
            <Info class="h-4 w-4" />
            <AlertDescription>
              {{ $page.props.flash.message.message }}
            </AlertDescription>
          </Alert>

          <div class="space-y-4">
            <div>
              <p class="text-sm text-muted-foreground">
                Estado actual:
                <span class="font-mono font-bold" :class="{ 'text-red-500': status, 'text-green-500': !status }">
                  {{ status ? 'EN MANTENIMIENTO' : 'ACTIVO' }}
                </span>
              </p>
            </div>

            <form @submit.prevent="toggleMaintenance" class="space-y-4">
              <div v-if="!status" class="grid w-full items-center gap-4">
                <div class="flex flex-col space-y-1.5">
                  <Label for="secret">Clave secreta (opcional)</Label>
                  <Input
                    id="secret"
                    v-model="form.secret"
                    type="text"
                    maxlength="36"
                    placeholder="Dejar vacío para acceso público restringido"
                    autofocus
                    autocomplete="off"
                    @change="form.validate('secret')"
                  />
                  <InputError :message="form.errors.secret" />
                </div>
              </div>
            </form>
          </div>
        </CardContent>
        <CardFooter class="flex justify-between px-6 pb-6">
          <Button :variant="status ? 'default' : 'destructive'" :disabled="form.processing" @click="toggleMaintenance">
            <LoaderCircleIcon v-if="form.processing" class="h-4 w-4 animate-spin" />
            {{ status ? 'Desactivar Modo Mantenimiento' : 'Activar Modo Mantenimiento' }}
          </Button>
        </CardFooter>
      </Card>
    </ContentLayout>
  </AppLayout>
</template>
