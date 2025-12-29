<script setup lang="ts">
import InputError from '@/components/InputError.vue';
import TextLink from '@/components/TextLink.vue';
import { Button } from '@/components/ui/button';
import { Checkbox } from '@/components/ui/checkbox';
import { Input } from '@/components/ui/input';
import {
  InputGroup,
  InputGroupAddon,
  InputGroupButton,
  InputGroupInput,
  InputGroupText,
} from '@/components/ui/input-group';
import { Label } from '@/components/ui/label';
import { Spinner } from '@/components/ui/spinner';
import {
  Tooltip,
  TooltipContent,
  TooltipProvider,
  TooltipTrigger,
} from '@/components/ui/tooltip';
import AuthBase from '@/layouts/AuthLayout.vue';
import { register } from '@/routes';
import { store } from '@/routes/login';
import { request } from '@/routes/password';
import { Form, Head } from '@inertiajs/vue3';
import { InfoIcon } from 'lucide-vue-next';

defineProps<{
  status?: string;
  canResetPassword: boolean;
  canRegister: boolean;
}>();
</script>

<template>
  <AuthBase
    title="Iniciar Sesión"
    description="Ingrese sus credenciales a continuación para acceder."
  >
    <Head title="Iniciar Sesión" />

    <div
      v-if="status"
      class="mb-4 text-center text-sm font-medium text-green-600"
    >
      {{ status }}
    </div>

    <Form
      v-bind="store.form()"
      :reset-on-success="['password']"
      v-slot="{ errors, processing }"
      class="flex flex-col gap-6"
    >
      <div class="grid gap-6">
        <div class="grid gap-2">
          <Label for="name">Nombre de usuario</Label>
          <InputGroup>
            <InputGroupInput
              id="name"
              name="name"
              placeholder="ej.: jose.canizales"
              autocomplete="username"
              :tabindex="1"
              required
              autofocus
            />
            <InputGroupAddon align="inline-end">
              <InputGroupText>@empresa.com</InputGroupText>
            </InputGroupAddon>
            <InputGroupAddon align="inline-end">
              <TooltipProvider>
                <Tooltip>
                  <TooltipTrigger as-child>
                    <InputGroupButton class="rounded-full" size="icon-xs">
                      <InfoIcon class="size-4" />
                    </InputGroupButton>
                  </TooltipTrigger>
                  <TooltipContent
                    >Coloque solamente la parte local de su dirección de correo
                    electrónico.</TooltipContent
                  >
                </Tooltip>
              </TooltipProvider>
            </InputGroupAddon>
          </InputGroup>
          <InputError :message="errors.name" />
        </div>

        <div class="grid gap-2">
          <div class="flex items-center justify-between">
            <Label for="password">Contraseña</Label>
            <TextLink
              v-if="canResetPassword"
              :href="request()"
              class="text-sm"
              :tabindex="5"
            >
              ¿Olvidó la contraseña?
            </TextLink>
          </div>
          <Input
            id="password"
            type="password"
            name="password"
            required
            :tabindex="2"
            autocomplete="current-password"
            placeholder="Contraseña"
          />
          <InputError :message="errors.password" />
        </div>

        <div class="flex items-center justify-between">
          <Label for="remember" class="flex items-center space-x-3">
            <Checkbox id="remember" name="remember" :tabindex="3" />
            <span>Permanecer conectado</span>
          </Label>
        </div>

        <Button
          type="submit"
          class="mt-4 w-full"
          :tabindex="4"
          :disabled="processing"
          data-test="login-button"
        >
          <Spinner v-if="processing" />
          Iniciar sesión
        </Button>
      </div>

      <div class="text-center text-sm text-muted-foreground" v-if="canRegister">
        ¿No tiene cuenta de usuario?
        <TextLink :href="register()" :tabindex="5">Regístrese</TextLink>
      </div>
    </Form>
  </AuthBase>
</template>
