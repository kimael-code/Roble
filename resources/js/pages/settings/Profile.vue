<script setup lang="ts">
import ProfileController from '@/actions/App/Http/Controllers/Settings/ProfileController';
import { edit } from '@/routes/profile';
import { Form, Head, usePage } from '@inertiajs/vue3';

// import DeleteUser from '@/components/DeleteUser.vue';
import HeadingSmall from '@/components/HeadingSmall.vue';
import InputError from '@/components/InputError.vue';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import AppLayout from '@/layouts/AppLayout.vue';
import SettingsLayout from '@/layouts/settings/Layout.vue';
import { type BreadcrumbItem } from '@/types';

interface Props {
  mustVerifyEmail: boolean;
  status?: string;
}

defineProps<Props>();

const breadcrumbItems: BreadcrumbItem[] = [
  {
    title: 'Configuración del perfil',
    href: edit().url,
  },
];

const page = usePage();
const user = page.props.auth.user;
</script>

<template>
  <AppLayout :breadcrumbs="breadcrumbItems">
    <Head title="Configuración del perfil" />

    <SettingsLayout>
      <div class="flex flex-col space-y-6">
        <HeadingSmall
          title="Información del perfil"
          description="Modifique su nombre y dirección de correo electrónico"
        />

        <Form
          v-bind="ProfileController.update.form()"
          class="space-y-6"
          v-slot="{ errors, processing, recentlySuccessful }"
        >
          <div class="grid gap-2">
            <Label for="name">Nombre de Usuario</Label>
            <Input
              id="name"
              class="mt-1 block w-full"
              name="name"
              :default-value="user.name"
              required
              autocomplete="name"
              placeholder="Nombre completo"
            />
            <InputError class="mt-2" :message="errors.name" />
          </div>

          <div class="grid gap-2">
            <Label for="email">Dirección de correo electrónico</Label>
            <Input
              id="email"
              type="email"
              class="mt-1 block w-full"
              name="email"
              :default-value="user.email"
              required
              autocomplete="username"
              placeholder="Dirección de correo electrónico"
            />
            <InputError class="mt-2" :message="errors.email" />
          </div>

          <div class="flex items-center gap-4">
            <Button :disabled="processing" data-test="update-profile-button"
              >Guardar</Button
            >

            <Transition
              enter-active-class="transition ease-in-out"
              enter-from-class="opacity-0"
              leave-active-class="transition ease-in-out"
              leave-to-class="opacity-0"
            >
              <p v-show="recentlySuccessful" class="text-sm text-neutral-600">
                Guardado.
              </p>
            </Transition>
          </div>
        </Form>
      </div>

      <!-- <DeleteUser /> -->
    </SettingsLayout>
  </AppLayout>
</template>
