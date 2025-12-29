import { OperationType } from '@/types';
import { Ref, computed, ref, watch } from 'vue';

/**
 * Configuración para cada tipo de acción
 */
interface ActionConfig {
  buttonText: string;
  buttonCss: string;
  title: (name: string) => string;
  description: (name: string) => string;
}

/**
 * Configuración centralizada de alertas para todas las acciones
 */
const ACTION_CONFIGS: Partial<
  Record<NonNullable<OperationType>, ActionConfig>
> = {
  destroy: {
    buttonText: 'Eliminar',
    buttonCss:
      'bg-destructive text-destructive-foreground hover:bg-destructive/90',
    title: (name) => `¿Eliminar «${name}»?`,
    description: (name) =>
      `«${name}» perderá el acceso al sistema. Sus datos se conservarán.`,
  },
  restore: {
    buttonText: 'Restaurar',
    buttonCss: '',
    title: (name) => `¿Restaurar «${name}»?`,
    description: (name) =>
      `«${name}» recuperará el acceso al sistema. Sus datos se restaurarán.`,
  },
  force_destroy: {
    buttonText: 'Eliminar permanentemente',
    buttonCss:
      'bg-destructive text-destructive-foreground hover:bg-destructive/90',
    title: (name) => `¿Eliminar «${name}» permanentemente?`,
    description: (name) =>
      `Esta acción no podrá revertirse. «${name}» perderá el acceso al sistema. Sus datos se eliminarán.`,
  },
  enable: {
    buttonText: 'Activar',
    buttonCss: '',
    title: (name) => `¿Activar «${name}»?`,
    description: (name) =>
      `«${name}» recuperará el acceso al sistema. Sus datos se restaurarán.`,
  },
  disable: {
    buttonText: 'Desactivar',
    buttonCss: 'bg-amber-500 text-foreground hover:bg-amber-500/90',
    title: (name) => `¿Desactivar «${name}»?`,
    description: (name) =>
      `«${name}» perderá el acceso al sistema. Sus datos se conservarán.`,
  },
  batch_destroy: {
    buttonText: 'Eliminar seleccionados',
    buttonCss:
      'bg-destructive text-destructive-foreground hover:bg-destructive/90',
    title: () => `¿Eliminar los registros que Usted ha seleccionado?`,
    description: () =>
      `Esta acción podrá revertirse. Los datos no se eliminarán, sin embargo, los usuarios no podrán ingresar al sistema.`,
  },
  batch_enable: {
    buttonText: 'Activar seleccionados',
    buttonCss: '',
    title: () => `¿Activar los registros que Usted ha seleccionado?`,
    description: () =>
      `Los registros recuperarán el acceso al sistema. Sus datos serán restaurados.`,
  },
  batch_disable: {
    buttonText: 'Desactivar seleccionados',
    buttonCss: 'bg-amber-500 text-foreground hover:bg-amber-500/90',
    title: () => `¿Desactivar los registros que Usted ha seleccionado?`,
    description: () =>
      `Los registros perderán el acceso al sistema. Sus datos se conservarán.`,
  },
};

/**
 * Composable para manejar alertas de confirmación de acciones
 *
 * Centraliza toda la configuración de alertas (títulos, descripciones, estilos)
 * eliminando la necesidad de watch() repetitivos en cada vista.
 *
 * @param action - Ref con la acción actual
 * @param resourceName - Ref con el nombre del recurso (ej: nombre del usuario)
 * @returns Estado y configuración de la alerta
 *
 * @example
 * ```ts
 * const action = ref<OperationType>(null);
 * const resourceName = computed(() => alertData.value?.name || '');
 *
 * const { alertOpen, alertAction, alertActionCss, alertTitle, alertDescription } =
 *   useActionAlerts(action, resourceName);
 * ```
 */
export function useActionAlerts(
  action: Ref<OperationType>,
  resourceName: Ref<string>,
) {
  const alertOpen = ref(false);
  const alertAction = ref('');
  const alertActionCss = ref('');
  const alertTitle = ref('');
  const alertDescription = ref('');

  watch(action, () => {
    if (!action.value) {
      alertOpen.value = false;
      return;
    }

    // Acciones que no requieren confirmación (navegación, lectura, etc.)
    const noAlertActions: OperationType[] = [
      'read',
      'read_all',
      'create',
      'edit',
      'send',
      'resend_activation',
    ];

    if (noAlertActions.includes(action.value)) {
      return;
    }

    const config = ACTION_CONFIGS[action.value];
    if (!config) {
      console.warn(`No alert configuration found for action: ${action.value}`);
      return;
    }

    alertAction.value = config.buttonText;
    alertActionCss.value = config.buttonCss;
    alertTitle.value = config.title(resourceName.value);
    alertDescription.value = config.description(resourceName.value);
    alertOpen.value = true;
  });

  // Computed para verificar si hay una alerta activa
  const hasActiveAlert = computed(() => alertOpen.value && !!action.value);

  return {
    alertOpen,
    alertAction,
    alertActionCss,
    alertTitle,
    alertDescription,
    hasActiveAlert,
  };
}
