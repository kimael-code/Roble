<script lang="ts" setup>
import InputError from '@/components/InputError.vue';
import { Button } from '@/components/ui/button';
import { Card, CardContent, CardDescription, CardFooter, CardHeader } from '@/components/ui/card';
import { Checkbox } from '@/components/ui/checkbox';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import AppLayout from '@/layouts/AppLayout.vue';
import ContentLayout from '@/layouts/ContentLayout.vue';
import { BreadcrumbItem, Permission } from '@/types';
import { Head, router } from '@inertiajs/vue3';
import { useForm } from 'laravel-precognition-vue-inertia';
import { KeySquare, LoaderCircleIcon } from 'lucide-vue-next';
import { ref } from 'vue';
import PermissionController from "@/actions/App/Http/Controllers/Security/PermissionController";

const props = defineProps<{
  permission: Permission;
}>();

const breadcrumbs: BreadcrumbItem[] = [
  {
    title: 'Permisos',
    href: '/permissions',
  },
  {
    title: 'Editar',
    href: '',
  },
];

const buttonCancel = ref(false);

const form = useForm('put', PermissionController.update(props.permission.id).url, {
  name: props.permission.name,
  description: props.permission.description,
  guard_name: props.permission.guard_name,
  set_menu: props.permission.set_menu,
});

function submit() {
  form.submit({
    preserveScroll: true,
    onSuccess: () => form.reset(),
  });
}

function index() {
  router.visit(PermissionController.index(), {
    onStart: () => (buttonCancel.value = true),
    onFinish: () => (buttonCancel.value = false),
  });
}
</script>

<template>
  <AppLayout :breadcrumbs>
    <Head title="Editar Permiso" />
    <ContentLayout title="Editar Permiso">
      <template #icon>
        <KeySquare />
      </template>
      <section class="mx-auto w-full">
        <Card class="container">
          <CardHeader>
            <CardDescription>Los campos con asterisco rojo son requeridos.</CardDescription>
          </CardHeader>
          <CardContent>
            <form @submit.prevent="submit" @keyup.enter.prevent="submit" @keyup.esc="index">
              <div class="grid w-full items-center gap-4">
                <div class="flex flex-col space-y-1.5">
                  <Label class="is-required" for="name">Nombre</Label>
                  <Input
                    id="name"
                    v-model="form.name"
                    type="text"
                    maxlength="255"
                    autocomplete="on"
                    placeholder="por ej.: read any user"
                    required
                    autofocus
                    @change="form.validate('name')"
                  />
                  <InputError :message="form.errors.name" />
                </div>
                <div class="flex flex-col space-y-1.5">
                  <Label class="is-required" for="description">Descripción</Label>
                  <Input
                    id="description"
                    v-model="form.description"
                    type="text"
                    maxlength="255"
                    autocomplete="on"
                    placeholder="por ej.: leer cualquier usuario"
                    required
                    @change="form.validate('description')"
                  />
                  <InputError :message="form.errors.description" />
                </div>
                <div class="flex flex-col space-y-1.5">
                  <Label class="is-required" for="guard_name">Autentificación</Label>
                  <Input
                    id="guard_name"
                    v-model="form.guard_name"
                    type="text"
                    placeholder="Autentificación"
                    readonly
                    required
                    @change="form.validate('guard_name')"
                  />
                  <InputError :message="form.errors.guard_name" />
                </div>
                <div class="flex items-center space-x-2">
                  <Checkbox id="set_menu" v-model="form.set_menu" @change="form.validate('set_menu')" />
                  <Label for="set_menu">Define menú</Label>
                  <InputError :message="form.errors.set_menu" />
                </div>
              </div>
            </form>
          </CardContent>
          <CardFooter class="flex justify-between px-6 pb-6">
            <Button variant="outline" :disabled="buttonCancel" @click="index" @keyup.esc="index" @keyup.enter="index">
              <LoaderCircleIcon v-if="buttonCancel" class="h-4 w-4 animate-spin" />
              Cancelar
            </Button>
            <Button :disabled="buttonCancel || form.processing" @click="submit" @keyup.esc="index" @keyup.enter="submit">
              <LoaderCircleIcon v-if="form.processing" class="h-4 w-4 animate-spin" />
              Guardar
            </Button>
          </CardFooter>
        </Card>
      </section>
    </ContentLayout>
  </AppLayout>
</template>
