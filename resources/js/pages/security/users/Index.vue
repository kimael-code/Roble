<script setup lang="ts">
import UserExporterController from '@/actions/App/Http/Controllers/Exporters/UserExporterController';
import UserController from '@/actions/App/Http/Controllers/Security/UserController';
import ActionAlertDialog from '@/components/ActionAlertDialog.vue';
import DataTable from '@/components/DataTable.vue';
import {
  Sheet,
  SheetContent,
  SheetDescription,
  SheetHeader,
  SheetTitle,
} from '@/components/ui/sheet';
import { valueUpdater } from '@/components/ui/table/utils';
import { useActionAlerts, useRequestActions } from '@/composables';
import { useExportUrl } from '@/composables/useExportUrl';
import AppLayout from '@/layouts/AppLayout.vue';
import ContentLayout from '@/layouts/ContentLayout.vue';
import {
  BreadcrumbItem,
  Can,
  OperationType,
  PaginatedCollection,
  Permission,
  Role,
  SearchFilter,
  User,
} from '@/types';
import { Head, router } from '@inertiajs/vue3';
import {
  getCoreRowModel,
  RowSelectionState,
  SortingState,
  TableOptions,
  useVueTable,
} from '@tanstack/vue-table';
import { UserIcon } from 'lucide-vue-next';
import { computed, reactive, ref, watchEffect } from 'vue';
import {
  columns,
  permissions as permissionsDT,
  processingRowId,
} from './partials/columns';
import ManualActivationDialog from './partials/ManualActivationDialog.vue';
import ResetPasswordDialog from './partials/ResetPasswordDialog.vue';
import SheetAdvancedFilters from './partials/SheetAdvancedFilters.vue';

const props = defineProps<{
  can: Can;
  filters: SearchFilter;
  permissions?: Permission[];
  roles?: Role[];
  users: PaginatedCollection<User>;
  statuses?: { label: string; value: string }[];
}>();

const breadcrumbs: BreadcrumbItem[] = [
  {
    title: 'Usuarios',
    href: '/users',
  },
];

const { action, resourceID, requestState, requestAction, isProcessing } =
  useRequestActions(UserController);

const alertData = ref<any>(null);
const resourceName = computed(() => alertData.value?.name || '');

const { alertOpen, alertAction, alertActionCss, alertTitle, alertDescription } =
  useActionAlerts(action, resourceName);

const showPdf = ref(false);
const showAdvancedFilters = ref(false);
const advancedSearchApplied = ref(false);

const activeFilters = reactive({
  search: props.filters.search ?? '',
  per_page: props.users.meta.per_page,
  sort_by: {} as Record<string, string>,
  // Filtros avanzados (pueden ser undefined al inicio)
  roles: props.filters.roles,
  permissions: props.filters.permissions,
});

permissionsDT.value = props.can;
const sorting = ref<SortingState>([]);
const globalFilter = ref('');
const rowSelection = ref<RowSelectionState>({});

const pdfUrl = useExportUrl(
  UserExporterController.indexToPdf().url,
  activeFilters,
);

const excelUrl = useExportUrl(
  UserExporterController.indexToExcel().url,
  activeFilters,
);

const jsonUrl = useExportUrl(
  UserExporterController.indexToJson().url,
  activeFilters,
);

function handleExport(format: 'pdf' | 'excel' | 'json') {
  switch (format) {
    case 'pdf':
      showPdf.value = true;
      break;
    case 'excel':
      window.open(excelUrl.value, '_blank');
      break;
    case 'json':
      window.open(jsonUrl.value, '_blank');
      break;
  }
}

// Estado para activación manual
const manualActivationDialog = ref(false);
const manualActivationData = ref<{
  password: string;
  user: string;
  email: string;
} | null>(null);

// Función para manejar activación manual desde DataTable
function handleManualActivation(row: User) {
  router.post(
    UserController.manuallyActivate(row.id).url,
    {},
    {
      preserveScroll: true,
      preserveState: true,
      onSuccess: (page) => {
        // Extraer datos de la respuesta
        const response = page.props as any;

        if (response.flash?.manualActivation) {
          manualActivationData.value = response.flash.manualActivation;
          manualActivationDialog.value = true;
        }
      },
      onError: (errors) => {
        console.error('Error en activación manual:', errors);
      },
    },
  );
}

// Función para confirmar activación
function confirmManualActivation() {
  manualActivationDialog.value = false;
  router.reload({ only: ['users'] });
}

function applyFilters() {
  router.visit(UserController.index(), {
    data: activeFilters,
    only: ['logs'],
    preserveScroll: true,
    preserveState: true,
    preserveUrl: true,
  });
}

function handleSortingChange(updater: any) {
  const newSorting =
    typeof updater === 'function' ? updater(sorting.value) : updater;
  sorting.value = newSorting;

  const sort_by: Record<string, string> = {};
  newSorting.forEach((col: any) => {
    if (col.id) {
      sort_by[col.id] = col.desc ? 'desc' : 'asc';
    }
  });
  activeFilters.sort_by = sort_by;

  applyFilters();
}

function handleAction(operation: OperationType, rowData: Record<string, any>) {
  alertData.value = rowData;
  action.value = operation;
  processingRowId.value = rowData.id;
}

function handleBatchAction(operation: OperationType) {
  action.value = operation;
  alertData.value = rowSelection.value;
}

const tableOptions = reactive<TableOptions<User>>({
  get data() {
    return props.users.data;
  },
  get columns() {
    return columns;
  },
  manualPagination: true,
  manualSorting: true,
  get meta() {
    return {
      currentPage: props.users.meta.current_page,
      pageSize: props.users.meta.per_page,
    };
  },
  getCoreRowModel: getCoreRowModel(),
  getRowId: (row) => String(row.id),
  onSortingChange: handleSortingChange,
  onRowSelectionChange: (updaterOrValue) =>
    valueUpdater(updaterOrValue, rowSelection),
  state: {
    get sorting() {
      return sorting.value;
    },
    get globalFilter() {
      return globalFilter.value;
    },
    get rowSelection() {
      return rowSelection.value;
    },
  },
});

const table = useVueTable(tableOptions);

// ¡67 líneas de watch eliminadas! Ahora usa useActionAlerts
watchEffect(() =>
  resourceID.value === null ? (processingRowId.value = null) : false,
);

function handleAdvancedSearch() {
  router.reload({
    only: ['roles', 'permissions', 'statuses'],
    onSuccess: () => (showAdvancedFilters.value = true),
  });
}
</script>

<template>
  <AppLayout :breadcrumbs="breadcrumbs">
    <Head title="Usuarios" />

    <ContentLayout title="Usuarios">
      <template #icon>
        <UserIcon />
      </template>
      <DataTable
        :can="can"
        :columns="columns"
        :data="users"
        :filters="filters"
        :search-only="['users']"
        :search-route="UserController.index()"
        :table="table"
        :is-advanced-search="advancedSearchApplied"
        :is-loading-new="requestState.create"
        :is-loading-dropdown="isProcessing"
        @batch-enable="handleBatchAction('batch_enable')"
        @batch-disable="handleBatchAction('batch_disable')"
        @batch-destroy="handleBatchAction('batch_destroy')"
        @search="
          (s) => {
            activeFilters.search = s;
            applyFilters();
          }
        "
        @new="requestAction({ operation: 'create' })"
        @read="
          (row) => (
            requestAction({ operation: 'read', data: { id: row.id } }),
            (processingRowId = row.id)
          )
        "
        @update="
          (row) => (
            requestAction({ operation: 'edit', data: { id: row.id } }),
            (processingRowId = row.id)
          )
        "
        @destroy="(row) => handleAction('destroy', row)"
        @force-destroy="(row) => handleAction('force_destroy', row)"
        @restore="(row) => handleAction('restore', row)"
        @enable="(row) => handleAction('enable', row)"
        @disable="(row) => handleAction('disable', row)"
        @export="handleExport"
        @advanced-search="handleAdvancedSearch"
        @reset-password="
          (row) =>
            router.visit(UserController.resetPassword(row.id), {
              preserveScroll: true,
              preserveState: true,
              preserveUrl: true,
            })
        "
        @resend-activation="
          (row: User) =>
            router.visit(UserController.resendActivation(row.id), {
              method: 'post',
              preserveScroll: true,
              preserveState: false,
            })
        "
        @manually-activate="(row: User) => handleManualActivation(row)"
      />

      <ActionAlertDialog
        :open="alertOpen"
        :title="alertTitle"
        :description="alertDescription"
        :action-text="alertAction"
        :action-css="alertActionCss"
        @cancel="((action = null), (processingRowId = null))"
        @confirm="
          requestAction({
            data: alertData,
            options: { preserveState: false },
          })
        "
      />

      <ResetPasswordDialog />

      <ManualActivationDialog
        v-if="manualActivationData"
        :open="manualActivationDialog"
        :user-name="manualActivationData.user"
        :user-email="manualActivationData.email"
        :password="manualActivationData.password"
        @confirm="confirmManualActivation"
      />

      <Sheet v-model:open="showPdf">
        <SheetContent side="bottom">
          <SheetHeader>
            <SheetTitle>Exportar a PDF</SheetTitle>
            <SheetDescription>Reporte: Permisos</SheetDescription>
          </SheetHeader>
          <div class="h-[70dvh]">
            <iframe
              :src="pdfUrl"
              frameborder="0"
              width="100%"
              height="100%"
            ></iframe>
          </div>
        </SheetContent>
      </Sheet>

      <SheetAdvancedFilters
        :permissions
        :roles
        :statuses
        :show="showAdvancedFilters"
        @close="showAdvancedFilters = false"
        @advanced-search="
          (advFilters) => {
            Object.assign(activeFilters, advFilters);
            advancedSearchApplied = true;
            applyFilters();
          }
        "
      />
    </ContentLayout>
  </AppLayout>
</template>
