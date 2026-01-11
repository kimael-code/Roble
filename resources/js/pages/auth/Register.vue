<script setup lang="ts">
import InputError from '@/components/InputError.vue';
import TextLink from '@/components/TextLink.vue';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { Spinner } from '@/components/ui/spinner';
import AuthBase from '@/layouts/AuthLayout.vue';
import { login } from '@/routes';
import { store } from '@/routes/register';
import { Form, Head } from '@inertiajs/vue3';
</script>

<template>
  <AuthBase
    title="Crear cuenta de usuario"
    description="Ingrese los siguientes datos a continuación para crear su cuenta"
  >
    <Head title="Registrarse" />

    <Form
      v-bind="store.form()"
      :reset-on-success="['password', 'password_confirmation']"
      v-slot="{ errors, processing }"
      class="flex flex-col gap-6"
    >
      <div class="grid gap-6">
        <div class="grid gap-2">
          <Label for="id_card">Número de CI</Label>
          <Input
            id="id_card"
            type="text"
            required
            autofocus
            :tabindex="1"
            autocomplete="on"
            name="id_card"
            placeholder="ej.: 12345678"
          />
          <InputError :message="errors.id_card" />
        </div>

        <div class="grid gap-2">
          <Label for="password">Contraseña</Label>
          <Input
            id="password"
            type="password"
            required
            :tabindex="3"
            autocomplete="new-password"
            name="password"
            placeholder="Contraseña"
          />
          <InputError :message="errors.password" />
        </div>

        <div class="grid gap-2">
          <Label for="password_confirmation">Confirmar contraseña</Label>
          <Input
            id="password_confirmation"
            type="password"
            required
            :tabindex="4"
            autocomplete="new-password"
            name="password_confirmation"
            placeholder="Confirmar contraseña"
          />
          <InputError :message="errors.password_confirmation" />
        </div>

        <Button
          type="submit"
          class="mt-2 w-full"
          tabindex="5"
          :disabled="processing"
          data-test="register-user-button"
        >
          <Spinner v-if="processing" />
          Crear cuenta
        </Button>
      </div>

      <div class="text-center text-sm text-muted-foreground">
        ¿Ya tiene cuenta creada?
        <TextLink
          :href="login()"
          class="underline underline-offset-4"
          :tabindex="6"
          >Ingresar</TextLink
        >
      </div>
    </Form>
  </AuthBase>
</template>
