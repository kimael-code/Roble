<script setup lang="ts">
import { Button } from '@/components/ui/button';
import {
  Dialog,
  DialogClose,
  DialogContent,
  DialogDescription,
  DialogFooter,
  DialogHeader,
  DialogTitle,
} from '@/components/ui/dialog';
import {
  InputGroup,
  InputGroupAddon,
  InputGroupButton,
  InputGroupInput,
} from '@/components/ui/input-group';
import { Label } from '@/components/ui/label';
import { PasswordResetFlashMessage } from '@/types';
import { router, usePage } from '@inertiajs/vue3';
import { useClipboard } from '@vueuse/core';
import { CheckIcon, CopyIcon } from 'lucide-vue-next';
import { computed, ref, watch } from 'vue';

const page = usePage();

const passwordResetMessage = computed<PasswordResetFlashMessage | null>(() => {
  const message = page.props.flash?.message;

  // Verifica si es el tipo correcto antes de usarlo
  if (
    message &&
    typeof message === 'object' &&
    'route' in message &&
    'expiresAt' in message
  ) {
    return message as PasswordResetFlashMessage;
  }
  return null;
});
const isResetDialogOpen = ref(!!passwordResetMessage.value);
const resetUrl = ref(passwordResetMessage.value?.route || '');
const expiresAt = ref(passwordResetMessage.value?.expiresAt || '');

const { copy, copied, isSupported } = useClipboard({ source: resetUrl });

function closeResetDialog() {
  isResetDialogOpen.value = false;
  router.reload({ only: ['flash'] });
}

watch(passwordResetMessage, (newMessage) => {
  if (newMessage) {
    resetUrl.value = newMessage.route;
    expiresAt.value = newMessage.expiresAt;
    isResetDialogOpen.value = true;
  }
});
</script>

<template>
  <Dialog v-model:open="isResetDialogOpen" @update:open="closeResetDialog">
    <DialogContent class="sm:max-w-md lg:max-w-4xl">
      <DialogHeader>
        <DialogTitle>Enlace de Restablecimiento</DialogTitle>
        <DialogDescription>
          Entrega este enlace AL USUARIO por un canal seguro. Expira en
          {{ expiresAt }}.
        </DialogDescription>
      </DialogHeader>
      <div class="flex items-center gap-2">
        <div class="grid flex-1 gap-2">
          <Label for="link" class="sr-only"> Enlace </Label>
          <InputGroup>
            <InputGroupInput
              class="font-mono text-xs"
              id="link"
              :placeholder="resetUrl"
              :model-value="resetUrl"
              readonly
            />
            <InputGroupAddon align="inline-end">
              <InputGroupButton
                v-if="isSupported"
                aria-label="Copy"
                title="Copiar al portapapeles"
                size="icon-xs"
                @click="copy()"
              >
                <CheckIcon v-if="copied" />
                <CopyIcon v-else />
              </InputGroupButton>
            </InputGroupAddon>
          </InputGroup>
        </div>
      </div>
      <DialogFooter class="sm:justify-start">
        <DialogClose as-child>
          <Button type="button" variant="secondary" @click="closeResetDialog">
            Cerrar
          </Button>
        </DialogClose>
      </DialogFooter>
    </DialogContent>
  </Dialog>
</template>
