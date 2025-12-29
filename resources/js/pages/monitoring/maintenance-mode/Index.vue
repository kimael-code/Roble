<script setup lang="ts">
import MaintenanceController from '@/actions/App/Http/Controllers/Monitoring/MaintenanceController';
import InputError from '@/components/InputError.vue';
import { Alert, AlertDescription } from '@/components/ui/alert';
import {
  AlertDialog,
  AlertDialogAction,
  AlertDialogCancel,
  AlertDialogContent,
  AlertDialogDescription,
  AlertDialogFooter,
  AlertDialogHeader,
  AlertDialogTitle,
} from '@/components/ui/alert-dialog';
import { Badge } from '@/components/ui/badge';
import {
  Card,
  CardContent,
  CardDescription,
  CardHeader,
  CardTitle,
} from '@/components/ui/card';
import {
  InputGroup,
  InputGroupAddon,
  InputGroupButton,
  InputGroupInput,
} from '@/components/ui/input-group';
import { Label } from '@/components/ui/label';
import { Switch } from '@/components/ui/switch';
import AppLayout from '@/layouts/AppLayout.vue';
import ContentLayout from '@/layouts/ContentLayout.vue';
import { BreadcrumbItem, NotificationFlashMessage } from '@/types';
import { Head, usePage } from '@inertiajs/vue3';
import { useForm } from 'laravel-precognition-vue-inertia';
import {
  AlertTriangleIcon,
  CheckCircle2Icon,
  ConstructionIcon,
  Info,
  LoaderCircleIcon,
  ServerIcon,
  SparklesIcon,
} from 'lucide-vue-next';
import { computed, ref, watch } from 'vue';

const props = defineProps<{
  status: boolean;
}>();

const breadcrumbs: BreadcrumbItem[] = [
  {
    title: 'Modo Mantenimiento',
    href: '/maintenance-mode',
  },
];

const form = useForm('post', MaintenanceController.toggle().url, {
  secret: '',
});

// 👇 Estado para controlar el diálogo de confirmación
const openConfirmDialog = ref(false);
const pendingAction = ref<'activate' | 'deactivate' | null>(null);

// 👇 Estado local del switch (para controlar visualmente el switch)
const switchValue = ref(props.status);

// 👇 Sincronizar switchValue con props.status cuando cambie
watch(
  () => props.status,
  (newStatus) => {
    switchValue.value = newStatus;
  },
);

// 👇 Computada segura para el mensaje flash
const flashContent = computed<string | null>(() => {
  const flash = usePage().props.flash?.message;
  if (!flash) return null;

  if (typeof flash === 'string') {
    return flash;
  }

  if (typeof flash === 'object' && flash !== null) {
    // Si tiene 'content', es NotificationFlashMessage
    if ('content' in flash) {
      return (flash as NotificationFlashMessage).content;
    }
  }

  return null;
});

// 👇 Manejar el cambio del switch
const handleSwitchChange = (checked: boolean) => {
  // Revertir el cambio visual temporalmente
  switchValue.value = props.status;

  // Determinar la acción pendiente
  pendingAction.value = checked ? 'activate' : 'deactivate';
  openConfirmDialog.value = true;
};

// 👇 Confirmar la acción
const confirmToggle = () => {
  form.submit({
    preserveScroll: true,
    onSuccess: () => {
      form.reset('secret');
      openConfirmDialog.value = false;
      pendingAction.value = null;
    },
    onError: () => {
      openConfirmDialog.value = false;
      pendingAction.value = null;
    },
  });
};

// 👇 Cancelar la acción
const cancelToggle = () => {
  openConfirmDialog.value = false;
  pendingAction.value = null;
  // Asegurar que el switch vuelva a su estado original
  switchValue.value = props.status;
};

// 👇 Generar UUID v4
const generateUUID = () => {
  // Generar UUID v4 usando crypto.randomUUID() si está disponible
  if (typeof crypto !== 'undefined' && crypto.randomUUID) {
    form.secret = crypto.randomUUID();
  } else {
    // Fallback para navegadores antiguos
    form.secret = 'xxxxxxxx-xxxx-4xxx-yxxx-xxxxxxxxxxxx'.replace(
      /[xy]/g,
      function (c) {
        const r = (Math.random() * 16) | 0;
        const v = c === 'x' ? r : (r & 0x3) | 0x8;
        return v.toString(16);
      },
    );
  }
};
</script>

<template>
  <AppLayout :breadcrumbs="breadcrumbs">
    <Head title="Modo Mantenimiento" />

    <ContentLayout title="Modo Mantenimiento">
      <template #icon>
        <ConstructionIcon />
      </template>

      <!-- 👇 Mensaje flash de éxito/error -->
      <Alert v-if="flashContent" class="mb-6">
        <Info class="h-4 w-4" />
        <AlertDescription>
          {{ flashContent }}
        </AlertDescription>
      </Alert>

      <div class="grid gap-6">
        <!-- 👇 Card de Estado Actual -->
        <Card>
          <CardHeader>
            <CardTitle class="flex items-center justify-between">
              <span>Estado del Sistema</span>
              <Badge
                :variant="status ? 'destructive' : 'default'"
                class="text-sm"
              >
                <ServerIcon class="mr-1.5 h-3.5 w-3.5" />
                {{ status ? 'EN MANTENIMIENTO' : 'OPERATIVO' }}
              </Badge>
            </CardTitle>
            <CardDescription>
              {{
                status
                  ? 'El sistema está actualmente en modo mantenimiento. Los usuarios no pueden acceder.'
                  : 'El sistema está funcionando normalmente y accesible para todos los usuarios.'
              }}
            </CardDescription>
          </CardHeader>
          <CardContent>
            <div
              class="flex items-center justify-between rounded-lg border p-4"
            >
              <div class="space-y-0.5">
                <Label class="text-base font-semibold">
                  Modo Mantenimiento
                </Label>
                <p class="text-sm text-muted-foreground">
                  {{
                    status
                      ? 'Desactivar para restaurar el acceso público'
                      : 'Activar para restringir el acceso al sistema'
                  }}
                </p>
              </div>
              <Switch
                v-model:model-value="switchValue"
                @update:model-value="handleSwitchChange"
                :disabled="form.processing"
              />
            </div>
          </CardContent>
        </Card>

        <!-- 👇 Card de Configuración (solo visible cuando NO está en mantenimiento) -->
        <Card v-if="!status">
          <CardHeader>
            <CardTitle>Configuración de Mantenimiento</CardTitle>
            <CardDescription>
              Configure la clave secreta antes de activar el modo mantenimiento.
            </CardDescription>
          </CardHeader>
          <CardContent class="space-y-4">
            <!-- Campo de clave secreta -->
            <div class="space-y-2">
              <Label for="secret">
                Clave Secreta
                <span class="text-xs text-muted-foreground">(opcional)</span>
              </Label>
              <InputGroup>
                <InputGroupInput
                  id="secret"
                  v-model="form.secret"
                  type="text"
                  maxlength="36"
                  placeholder="Generar o ingresar un UUID válido"
                  autocomplete="off"
                  @change="form.validate('secret')"
                />
                <InputGroupAddon align="inline-end">
                  <InputGroupButton
                    type="button"
                    size="icon-xs"
                    class="rounded-full"
                    @click="generateUUID"
                  >
                    <SparklesIcon class="h-3.5 w-3.5" />
                  </InputGroupButton>
                </InputGroupAddon>
              </InputGroup>
              <Alert>
                <Info class="h-4 w-4" />
                <AlertDescription class="text-xs">
                  <p>
                    <strong>¿Para qué sirve?</strong> Si proporciona una clave
                    secreta (UUID), podrá acceder al sistema durante el
                    mantenimiento usando la URL con el parámetro
                    <code class="text-xs">?secret=SU_CLAVE</code>. Esto es útil
                    para verificar cambios sin desactivar el modo mantenimiento.
                  </p>
                  <br />
                  <p>
                    <strong>Tip:</strong> Haga clic en el botón
                    <SparklesIcon class="inline h-3 w-3" /> para generar
                    automáticamente una clave válida.
                  </p>
                </AlertDescription>
              </Alert>
              <InputError :message="form.errors.secret" />
            </div>
          </CardContent>
        </Card>
      </div>

      <!-- 👇 Diálogo de Confirmación -->
      <AlertDialog v-model:open="openConfirmDialog">
        <AlertDialogContent>
          <AlertDialogHeader>
            <AlertDialogTitle class="flex items-center gap-2">
              <AlertTriangleIcon
                class="h-5 w-5"
                :class="
                  pendingAction === 'activate'
                    ? 'text-destructive'
                    : 'text-green-600'
                "
              />
              {{
                pendingAction === 'activate'
                  ? '¿Activar Modo Mantenimiento?'
                  : '¿Desactivar Modo Mantenimiento?'
              }}
            </AlertDialogTitle>
            <AlertDialogDescription class="space-y-3">
              <p v-if="pendingAction === 'activate'">
                Está a punto de poner el sistema en modo mantenimiento. Esto
                tendrá los siguientes efectos:
              </p>
              <p v-else>
                Está a punto de desactivar el modo mantenimiento y restaurar el
                acceso público al sistema.
              </p>

              <ul
                v-if="pendingAction === 'activate'"
                class="list-inside list-disc space-y-1 text-sm"
              >
                <li>
                  Los usuarios <strong>no podrán acceder</strong> al sistema
                </li>
                <li>
                  Las sesiones activas podrían verse
                  <strong>interrumpidas</strong>
                </li>
                <li>
                  Solo los administradores con la clave secreta (si se
                  proporcionó) podrán acceder
                </li>
                <li>
                  Se mostrará el mensaje de mantenimiento a los usuarios que
                  intenten acceder
                </li>
              </ul>

              <Alert
                v-if="pendingAction === 'activate'"
                class="border-destructive/50 bg-destructive/10"
              >
                <AlertTriangleIcon class="h-4 w-4 text-destructive" />
                <AlertDescription class="text-xs text-destructive">
                  <strong>Advertencia:</strong> Asegúrese de que todos los
                  usuarios hayan sido notificados antes de proceder.
                </AlertDescription>
              </Alert>

              <Alert v-else class="border-green-600/50 bg-green-600/10">
                <CheckCircle2Icon class="h-4 w-4 text-green-600" />
                <AlertDescription class="text-xs text-green-600">
                  El sistema volverá a estar disponible para todos los usuarios
                  inmediatamente.
                </AlertDescription>
              </Alert>
            </AlertDialogDescription>
          </AlertDialogHeader>
          <AlertDialogFooter>
            <AlertDialogCancel
              @click="cancelToggle"
              :disabled="form.processing"
            >
              Cancelar
            </AlertDialogCancel>
            <AlertDialogAction
              @click="confirmToggle"
              :disabled="form.processing"
              :class="
                pendingAction === 'activate'
                  ? 'bg-destructive text-destructive-foreground hover:bg-destructive/90'
                  : 'bg-green-600 text-white hover:bg-green-600/90'
              "
            >
              <LoaderCircleIcon
                v-if="form.processing"
                class="mr-2 h-4 w-4 animate-spin"
              />
              {{
                pendingAction === 'activate'
                  ? 'Sí, Activar Mantenimiento'
                  : 'Sí, Desactivar Mantenimiento'
              }}
            </AlertDialogAction>
          </AlertDialogFooter>
        </AlertDialogContent>
      </AlertDialog>
    </ContentLayout>
  </AppLayout>
</template>
