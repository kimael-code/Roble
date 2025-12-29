import { OperationType } from '@/types';
import { router } from '@inertiajs/vue3';
import { computed, ref } from 'vue';

// Tipos base para las rutas
interface BaseRouteDefinition {
  definition: {
    methods: string[];
    url: string;
  };
  url: (...args: any[]) => string;
}

interface RouteWithoutParams extends BaseRouteDefinition {
  (options?: any): any;
}

interface RouteWithParams extends BaseRouteDefinition {
  (args: any, options?: any): any;
}

// Tipo para el controlador de rutas completo
export interface RouteController {
  batchDestroy?: RouteWithoutParams;
  index?: RouteWithoutParams;
  create?: RouteWithoutParams;
  store?: RouteWithoutParams;
  show?: RouteWithParams;
  edit?: RouteWithParams;
  update?: RouteWithParams;
  destroy?: RouteWithParams;
  // Rutas adicionales que podrían existir
  forceDestroy?: RouteWithParams;
  restore?: RouteWithParams;
  enable?: RouteWithParams;
  disable?: RouteWithParams;
  batchEnable?: RouteWithoutParams;
  batchDisable?: RouteWithoutParams;
  send?: RouteWithParams;
  resendActivation?: RouteWithParams;
}

/**
 * A Vue composable for handling common CRUD operations with Inertia.js
 *
 * @param {RouteController} routes - The routes object (like OrganizationController)
 * @returns {Object} An object containing request methods and state
 */
export function useRequestActions(routes: RouteController) {
  /**
   * Opciones disponibles para la petición.
   * @see https://inertiajs.com/manual-visits
   */
  interface RequestOptions {
    data?: { [index: string]: any };
    replace?: boolean;
    preserveState?: boolean;
    preserveScroll?: boolean;
    only?: Array<any>;
    except?: Array<any>;
    headers?: { [index: string]: any };
    errorBag?: null;
    forceFormData?: boolean;
    queryStringArrayFormat?: 'brackets';
    async?: boolean;
    showProgress?: true;
    fresh?: boolean;
    reset?: Array<any>;
    preserveUrl?: boolean;
    prefetch?: boolean;
  }

  interface ActionData {
    [index: string]: any;
  }

  interface RequestActionParams {
    operation?: OperationType;
    data?: ActionData;
    options?: RequestOptions;
  }

  const action = ref<OperationType>(null);
  const resourceID = ref<number | string | null>(null);
  const requestState = ref({
    create: false,
    read: false,
    readAll: false,
    edit: false,
    destroy: false,
    forceDestroy: false,
    restore: false,
    enable: false,
    disable: false,
    batchEnable: false,
    batchDisable: false,
    batchDestroy: false,
    send: false,
    resendActivation: false,
  });

  function requestAction({ operation, data, options }: RequestActionParams) {
    resourceID.value = data?.id;

    if (operation) action.value = operation;

    switch (action.value) {
      case 'create':
        requestCreate(options);
        break;
      case 'read':
        requestRead(data?.id, options);
        break;
      case 'read_all':
        requestReadAll(options);
        break;
      case 'edit':
        requestEdit(data?.id, options);
        break;
      case 'destroy':
        requestDestroy(data?.id, options);
        break;
      case 'force_destroy':
        requestForceDestroy(data?.id, options);
        break;
      case 'restore':
        requestRestore(data?.id, options);
        break;
      case 'enable':
        requestEnable(data?.id, options);
        break;
      case 'disable':
        requestDisable(data?.id, options);
        break;
      case 'batch_enable':
        requestBatchEnable((data as { [x: string]: boolean }) ?? {}, options);
        break;
      case 'batch_disable':
        requestBatchDisable((data as { [x: string]: boolean }) ?? {}, options);
        break;
      case 'batch_destroy':
        requestBatchDestroy((data as { [x: string]: boolean }) ?? {}, options);
        break;
      case 'send':
        requestSend(data?.id, options);
        break;
      case 'resend_activation':
        requestResendActivation(data?.id, options);
        break;

      default:
        console.log('action: ', action.value);
        break;
    }
  }

  function requestCreate(options?: RequestOptions) {
    requestState.value.create = false;

    if (!routes.create) {
      console.error('Create route not available in routes object');
      return;
    }

    router.visit(routes.create.url(), {
      ...options,
      onStart: () => (requestState.value.create = true),
      onFinish: () => {
        requestState.value.create = false;
        action.value = null;
        resourceID.value = null;
      },
    });
  }

  function requestRead(id: number | string, options?: RequestOptions) {
    requestState.value.read = false;
    resourceID.value = id;

    if (!routes.show) {
      console.error('Show route not available in routes object');
      return;
    }

    router.visit(routes.show.url(id), {
      ...options,
      onStart: () => (requestState.value.read = true),
      onFinish: () => {
        requestState.value.read = false;
        action.value = null;
        resourceID.value = null;
      },
    });
  }

  function requestReadAll(options?: RequestOptions) {
    requestState.value.readAll = false;
    resourceID.value = null;

    if (!routes.index) {
      console.error('Index route not available in routes object');
      return;
    }

    router.visit(routes.index.url(), {
      ...options,
      onStart: () => (requestState.value.readAll = true),
      onFinish: () => {
        requestState.value.readAll = false;
        action.value = null;
        resourceID.value = null;
      },
    });
  }

  function requestEdit(id: number | string, options?: RequestOptions) {
    requestState.value.edit = false;
    resourceID.value = id;

    if (!routes.edit) {
      console.error('Edit route not available in routes object');
      return;
    }

    router.visit(routes.edit.url(id), {
      ...options,
      onStart: () => (requestState.value.edit = true),
      onFinish: () => {
        requestState.value.edit = false;
        action.value = null;
        resourceID.value = null;
      },
    });
  }

  function requestDestroy(id: number | string, options?: RequestOptions) {
    requestState.value.destroy = false;
    resourceID.value = id;

    if (!routes.destroy) {
      console.error('Destroy route not available in routes object');
      return;
    }

    router.visit(routes.destroy.url(id), {
      ...options,
      method: 'delete',
      onStart: () => (requestState.value.destroy = true),
      onFinish: () => {
        requestState.value.destroy = false;
        action.value = null;
        resourceID.value = null;
      },
    });
  }

  function requestForceDestroy(id: number | string, options?: RequestOptions) {
    requestState.value.forceDestroy = false;
    resourceID.value = id;

    if (!routes.forceDestroy) {
      console.error('ForceDestroy route not available in routes object');
      return;
    }

    router.visit(routes.forceDestroy?.url(id), {
      ...options,
      method: 'delete',
      onStart: () => (requestState.value.forceDestroy = true),
      onFinish: () => {
        requestState.value.forceDestroy = false;
        action.value = null;
        resourceID.value = null;
      },
    });
  }

  function requestBatchEnable(
    selectedRows: { [x: string]: boolean },
    options?: RequestOptions,
  ) {
    requestState.value.batchEnable = false;
    resourceID.value = null;

    if (!routes.batchEnable && !routes.batchDestroy) {
      console.error(
        'BatchEnable or BatchDestroy route not available in routes object',
      );
      return;
    }

    // Usar batchEnable si existe, sino usar batchDestroy como fallback
    const route = routes.batchEnable || routes.batchDestroy;

    router.visit(route!.url(), {
      ...options,
      method: 'post',
      data: selectedRows,
      onStart: () => (requestState.value.batchEnable = true),
      onFinish: () => {
        requestState.value.batchEnable = false;
        action.value = null;
        resourceID.value = null;
      },
    });
  }

  function requestBatchDisable(
    selectedRows: { [x: string]: boolean },
    options?: RequestOptions,
  ) {
    requestState.value.batchDisable = false;
    resourceID.value = null;

    if (!routes.batchDisable && !routes.batchDestroy) {
      console.error(
        'BatchDisable or BatchDestroy route not available in routes object',
      );
      return;
    }

    // Usar batchDisable si existe, sino usar batchDestroy como fallback
    const route = routes.batchDisable || routes.batchDestroy;

    router.visit(route!.url(), {
      ...options,
      method: 'post',
      data: selectedRows,
      onStart: () => (requestState.value.batchDisable = true),
      onFinish: () => {
        requestState.value.batchDisable = false;
        action.value = null;
        resourceID.value = null;
      },
    });
  }

  function requestBatchDestroy(
    selectedRows: { [x: string]: boolean },
    options?: RequestOptions,
  ) {
    requestState.value.batchDestroy = false;
    resourceID.value = null;

    if (!routes.batchDestroy) {
      console.error('BatchDestroy route not available in routes object');
      return;
    }

    router.visit(routes.batchDestroy.url(), {
      ...options,
      method: 'delete',
      data: selectedRows,
      onStart: () => (requestState.value.batchDestroy = true),
      onFinish: () => {
        requestState.value.batchDestroy = false;
        action.value = null;
        resourceID.value = null;
      },
    });
  }

  function requestRestore(id: number | string, options?: RequestOptions) {
    requestState.value.restore = false;
    resourceID.value = id;

    if (!routes.restore) {
      console.error('Restore route not available in routes object');
      return;
    }

    router.visit(routes.restore.url(id), {
      ...options,
      method: 'put',
      onStart: () => (requestState.value.restore = true),
      onFinish: () => {
        requestState.value.restore = false;
        action.value = null;
        resourceID.value = null;
      },
    });
  }

  function requestEnable(id: number | string, options?: RequestOptions) {
    requestState.value.enable = false;
    resourceID.value = id;

    if (!routes.enable) {
      console.error('Enable route not available in routes object');
      return;
    }

    router.visit(routes.enable.url(id), {
      ...options,
      method: 'put',
      onStart: () => (requestState.value.enable = true),
      onFinish: () => {
        requestState.value.enable = false;
        action.value = null;
        resourceID.value = null;
      },
    });
  }

  function requestDisable(id: number | string, options?: RequestOptions) {
    requestState.value.disable = false;
    resourceID.value = id;

    if (!routes.disable) {
      console.error('Disable route not available in routes object');
      return;
    }

    router.visit(routes.disable.url(id), {
      ...options,
      method: 'put',
      onStart: () => (requestState.value.disable = true),
      onFinish: () => {
        requestState.value.disable = false;
        action.value = null;
        resourceID.value = null;
      },
    });
  }

  function requestSend(id: number | string, options?: RequestOptions) {
    requestState.value.send = false;
    resourceID.value = id;

    if (!routes.send) {
      console.error('Send route not available in routes object');
      return;
    }

    router.visit(routes.send.url(id), {
      ...options,
      method: 'post',
      onStart: () => (requestState.value.send = true),
      onFinish: () => {
        requestState.value.send = false;
        action.value = null;
        resourceID.value = null;
      },
    });
  }

  function requestResendActivation(
    id: number | string,
    options?: RequestOptions,
  ) {
    requestState.value.resendActivation = false;
    resourceID.value = id;

    if (!routes.resendActivation) {
      console.error('ResendActivation route not available in routes object');
      return;
    }

    router.visit(routes.resendActivation.url(id), {
      ...options,
      method: 'post',
      onStart: () => (requestState.value.resendActivation = true),
      onFinish: () => {
        requestState.value.resendActivation = false;
        action.value = null;
        resourceID.value = null;
      },
    });
  }

  /**
   * Computed que indica si hay alguna acción en progreso
   */
  const isProcessing = computed(() => {
    return Object.values(requestState.value).some((state) => state === true);
  });

  /**
   * Verifica si un recurso específico está siendo procesado
   *
   * @param id - ID del recurso a verificar
   * @returns true si el recurso está siendo procesado
   */
  const isResourceProcessing = (id: number | string) => {
    return resourceID.value === id && isProcessing.value;
  };

  return {
    action,
    resourceID,
    requestState,
    requestAction,
    isProcessing,
    isResourceProcessing,
  };
}
