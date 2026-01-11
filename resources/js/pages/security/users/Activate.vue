<script setup lang="ts">
import InputError from '@/components/InputError.vue';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { Spinner } from '@/components/ui/spinner';
import useStringUtils from '@/composables/useStringUtils';
import AuthLayout from '@/layouts/AuthLayout.vue';
import { User } from '@/types';
import { Form, Head } from '@inertiajs/vue3';
import { computed } from 'vue';

const props = defineProps<{
  user: User;
  formActionUrl: string;
}>();

const { capitalizeFirstLetter } = useStringUtils();

const firstName = computed(() => {
  const raw = props.user?.person?.names
    ? props.user.person.names.split(' ')[0]
    : 'Usuario';

  return capitalizeFirstLetter(raw);
});
</script>

<template>
  <AuthLayout
    title="Activar Cuenta"
    :description="`${firstName}, por favor, ingresa tu nueva contraseña.`"
  >
    <Head title="Activar Cuenta" />

    <Form
      :action="formActionUrl"
      method="post"
      :reset-on-success="['password', 'password_confirmation']"
      v-slot="{ errors, processing }"
    >
      <div class="grid gap-6">
        <div class="grid gap-2">
          <Label for="password">Contraseña</Label>
          <Input
            id="password"
            type="password"
            name="password"
            autocomplete="new-password"
            class="mt-1 block w-full"
            autofocus
            placeholder="Contraseña"
          />
          <InputError :message="errors.password" />
        </div>

        <div class="grid gap-2">
          <Label for="password_confirmation"> Confirmar contraseña </Label>
          <Input
            id="password_confirmation"
            type="password"
            name="password_confirmation"
            autocomplete="new-password"
            class="mt-1 block w-full"
            placeholder="Confirmar contraseña"
          />
          <InputError :message="errors.password_confirmation" />
        </div>

        <Button
          type="submit"
          class="mt-4 w-full"
          :disabled="processing"
          data-test="activation-account-button"
        >
          <Spinner v-if="processing" />
          Activar Cuenta
        </Button>
      </div>
    </Form>
  </AuthLayout>
</template>
