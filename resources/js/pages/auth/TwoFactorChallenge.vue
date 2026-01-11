<script setup lang="ts">
import InputError from '@/components/InputError.vue';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import {
  PinInput,
  PinInputGroup,
  PinInputSlot,
} from '@/components/ui/pin-input';
import AuthLayout from '@/layouts/AuthLayout.vue';
import { store } from '@/routes/two-factor/login';
import { Form, Head } from '@inertiajs/vue3';
import { computed, nextTick, onMounted, ref, watch } from 'vue';

interface AuthConfigContent {
  title: string;
  description: string;
  toggleText: string;
}

const authConfigContent = computed<AuthConfigContent>(() => {
  if (showRecoveryInput.value) {
    return {
      title: 'Código de Recuperación',
      description:
        'Por favor, confirme el acceso a su cuenta introduciendo uno de sus códigos de recuperación de emergencia.',
      toggleText: 'iniciar sesión con un código de autenticación',
    };
  }

  return {
    title: 'Código de Autenticación',
    description:
      'Introduzca el código de autenticación proporcionado por su aplicación de autenticación.',
    toggleText: 'iniciar sesión con un código de recuperación',
  };
});

const showRecoveryInput = ref<boolean>(false);

const toggleRecoveryMode = (clearErrors: () => void): void => {
  showRecoveryInput.value = !showRecoveryInput.value;
  clearErrors();
  code.value = [];
};

const code = ref<number[]>([]);
const codeValue = computed<string>(() => code.value.join(''));

const pinInputRef = ref<HTMLElement | null>(null);
const recoveryInputRef = ref<HTMLInputElement | null>(null);

const focusPinInput = () => {
  const componentInstance = pinInputRef.value as any;
  const rootElement = componentInstance?.$el as HTMLElement | undefined;
  rootElement?.querySelector('input')?.focus();
};

onMounted(() => {
  nextTick(() => {
    if (showRecoveryInput.value) {
      recoveryInputRef.value?.focus();
    } else {
      focusPinInput();
    }
  });
});

watch(showRecoveryInput, async (isRecovery) => {
  await nextTick();
  if (isRecovery) {
    recoveryInputRef.value?.focus();
  } else {
    focusPinInput();
  }
});
</script>

<template>
  <AuthLayout
    :title="authConfigContent.title"
    :description="authConfigContent.description"
  >
    <Head title="Autenticación de Dos Factores" />

    <div class="space-y-6">
      <template v-if="!showRecoveryInput">
        <Form
          v-bind="store.form()"
          class="space-y-4"
          reset-on-error
          @error="code = []"
          #default="{ errors, processing, clearErrors }"
        >
          <input type="hidden" name="code" :value="codeValue" />
          <div
            class="flex flex-col items-center justify-center space-y-3 text-center"
          >
            <div class="flex w-full items-center justify-center">
              <PinInput
                id="otp"
                placeholder="○"
                v-model="code"
                type="number"
                ref="pinInputRef"
                otp
              >
                <PinInputGroup>
                  <PinInputSlot
                    v-for="(id, index) in 6"
                    :key="id"
                    :index="index"
                    :disabled="processing"
                    :id="`otp-${index}`"
                  />
                </PinInputGroup>
              </PinInput>
            </div>
            <InputError :message="errors.code" />
          </div>
          <Button type="submit" class="w-full" :disabled="processing"
            >Continuar</Button
          >
          <div class="text-center text-sm text-muted-foreground">
            <span>o puede </span>
            <button
              type="button"
              class="text-foreground underline decoration-neutral-300 underline-offset-4 transition-colors duration-300 ease-out hover:decoration-current! dark:decoration-neutral-500"
              @click="() => toggleRecoveryMode(clearErrors)"
            >
              {{ authConfigContent.toggleText }}
            </button>
          </div>
        </Form>
      </template>

      <template v-else>
        <Form
          v-bind="store.form()"
          class="space-y-4"
          reset-on-error
          #default="{ errors, processing, clearErrors }"
        >
          <Input
            ref="recoveryInputRef"
            name="recovery_code"
            type="text"
            placeholder="Introduzca el código de recuperación"
            required
          />
          <InputError :message="errors.recovery_code" />
          <Button type="submit" class="w-full" :disabled="processing"
            >Continuar</Button
          >

          <div class="text-center text-sm text-muted-foreground">
            <span>o puede </span>
            <button
              type="button"
              class="text-foreground underline decoration-neutral-300 underline-offset-4 transition-colors duration-300 ease-out hover:decoration-current! dark:decoration-neutral-500"
              @click="() => toggleRecoveryMode(clearErrors)"
            >
              {{ authConfigContent.toggleText }}
            </button>
          </div>
        </Form>
      </template>
    </div>
  </AuthLayout>
</template>
