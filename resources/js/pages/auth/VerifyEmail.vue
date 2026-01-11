<script setup lang="ts">
import TextLink from '@/components/TextLink.vue';
import { Button } from '@/components/ui/button';
import { Spinner } from '@/components/ui/spinner';
import AuthLayout from '@/layouts/AuthLayout.vue';
import { logout } from '@/routes';
import { send } from '@/routes/verification';
import { Form, Head } from '@inertiajs/vue3';

defineProps<{
  status?: string;
}>();
</script>

<template>
  <AuthLayout
    title="Verificar correo electrónico"
    description="Por favor, verifique su dirección de correo electrónico haciendo clic en el enlace que le acabamos de enviar."
  >
    <Head title="Email verification" />

    <div
      v-if="status === 'verification-link-sent'"
      class="mb-4 text-center text-sm font-medium text-green-600"
    >
      Se ha enviado un nuevo enlace de verificación a la dirección de correo
      electrónico que usted proporcionó durante el registro.
    </div>

    <div class="space-y-6 text-center">
      <Form v-bind="send.form()" v-slot="{ processing }">
        <Button :disabled="processing" variant="secondary">
          <Spinner v-if="processing" />
          Reenviar correo electrónico de verificación
        </Button>
      </Form>

      <TextLink :href="logout()" as="button" class="mx-auto block text-sm">
        Salir
      </TextLink>
    </div>
  </AuthLayout>
</template>
