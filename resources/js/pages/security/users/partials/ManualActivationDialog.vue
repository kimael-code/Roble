<script setup lang="ts">
import { Button } from '@/components/ui/button';
import {
  Dialog,
  DialogContent,
  DialogDescription,
  DialogFooter,
  DialogHeader,
  DialogTitle,
} from '@/components/ui/dialog';
import { CheckIcon, ClipboardCopyIcon } from 'lucide-vue-next';
import { ref } from 'vue';

interface Props {
  open: boolean;
  userName: string;
  userEmail: string;
  password: string;
}

const props = defineProps<Props>();

defineEmits<{
  confirm: [];
}>();

const copied = ref(false);

async function copyPassword() {
  try {
    await navigator.clipboard.writeText(props.password);
    copied.value = true;
    setTimeout(() => (copied.value = false), 2000);
  } catch (err) {
    console.error('Error al copiar:', err);
  }
}
</script>

<template>
  <Dialog :open="open" @update:open="(val) => !val && $emit('confirm')">
    <DialogContent class="sm:max-w-md">
      <DialogHeader>
        <DialogTitle>¡Activación manual exitosa!</DialogTitle>
        <DialogDescription>
          El usuario «{{ userName }}» ha sido activado correctamente.
        </DialogDescription>
      </DialogHeader>

      <div class="space-y-4 py-4">
        <!-- Contraseña con botón copiar -->
        <div
          class="rounded-lg border border-amber-200 bg-amber-50 p-4 dark:border-amber-800 dark:bg-amber-950"
        >
          <p
            class="mb-2 text-sm font-medium text-amber-900 dark:text-amber-100"
          >
            Contraseña establecida:
          </p>
          <div class="flex items-center gap-2">
            <code
              class="flex-1 rounded bg-amber-100 px-3 py-2 font-mono text-lg font-bold text-amber-900 dark:bg-amber-900 dark:text-amber-100"
            >
              {{ password }}
            </code>
            <Button
              variant="outline"
              size="icon"
              @click="copyPassword"
              :class="
                copied ? 'border-green-500 bg-green-50 dark:bg-green-950' : ''
              "
            >
              <CheckIcon v-if="copied" class="h-4 w-4 text-green-600" />
              <ClipboardCopyIcon v-else class="h-4 w-4" />
            </Button>
          </div>
        </div>

        <!-- Advertencia -->
        <div
          class="rounded-lg border border-blue-200 bg-blue-50 p-4 dark:border-blue-800 dark:bg-blue-950"
        >
          <p class="text-sm font-medium text-blue-900 dark:text-blue-100">
            ⚠️ IMPORTANTE:
          </p>
          <p class="mt-1 text-sm text-blue-800 dark:text-blue-200">
            Debe comunicar estas credenciales al usuario de forma segura
            (presencial, teléfono interno, sistema corporativo, etc.)
          </p>
        </div>

        <!-- Resumen de credenciales -->
        <div class="space-y-1 text-sm text-muted-foreground">
          <p><strong>Usuario:</strong> {{ userName }}</p>
          <p><strong>Email:</strong> {{ userEmail }}</p>
          <p><strong>Contraseña:</strong> {{ password }}</p>
        </div>
      </div>

      <DialogFooter>
        <Button @click="$emit('confirm')" class="w-full"> Entendido </Button>
      </DialogFooter>
    </DialogContent>
  </Dialog>
</template>
