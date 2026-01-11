<script setup lang="ts">
import InputError from '@/components/InputError.vue';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { Spinner } from '@/components/ui/spinner';
import AuthLayout from '@/layouts/AuthLayout.vue';
import { store } from '@/routes/password/confirm';
import { Form, Head } from '@inertiajs/vue3';
</script>

<template>
  <AuthLayout
    title="Confirme su contraseña"
    description="Antes de continuar, por favor, ingrese su contraseña."
  >
    <Head title="Confirmar contraseña" />

    <Form
      v-bind="store.form()"
      reset-on-success
      v-slot="{ errors, processing }"
    >
      <div class="space-y-6">
        <div class="grid gap-2">
          <Label htmlFor="password">Contraseña</Label>
          <Input
            id="password"
            type="password"
            name="password"
            class="mt-1 block w-full"
            required
            autocomplete="current-password"
            autofocus
          />

          <InputError :message="errors.password" />
        </div>

        <div class="flex items-center">
          <Button
            class="w-full"
            :disabled="processing"
            data-test="confirm-password-button"
          >
            <Spinner v-if="processing" />
            Confirmar Contraseña
          </Button>
        </div>
      </div>
    </Form>
  </AuthLayout>
</template>
