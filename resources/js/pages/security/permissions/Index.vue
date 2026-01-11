<script setup lang="ts">
import PermissionExporterController from '@/actions/App/Http/Controllers/Exporters/PermissionExporterController';
import PermissionController from '@/actions/App/Http/Controllers/Security/PermissionController';
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
import { KeySquare } from 'lucide-vue-next';
import { computed, reactive, ref, watchEffect } from 'vue';
import {
  columns,
  permissions as DTpermissions,
  processingRowId,
} from './partials/columns';
import SheetAdvancedFilters from './partials/SheetAdvancedFilters.vue';

const props = defineProps<{
  can: Can;
  filters: SearchFilter;
  users?: User[];
  roles?: Role[];
  permissions: PaginatedCollection<Permission>;
}>();

const breadcrumbs: BreadcrumbItem[] = [
  {
    title: 'Permisos',
    href: '/permissions',
  },
];

const { action, resourceID, requestState, requestAction, isProcessing } =
  useRequestActions(PermissionController);

const alertData = ref<any>(null);
const resourceName = computed(() => alertData.value?.name || '');

const { alertOpen, alertAction, alertActionCss, alertTitle, alertDescription } =
  useActionAlerts(action, resourceName);

const showPdf = ref(false);
const showAdvancedFilters = ref(false);
const advancedSearchApplied = ref(false);
const activeFilters = reactive({
  search: props.filters.search ?? '',
  per_page: props.permissions.meta.per_page,
  sort_by: {} as Record<string, string>,
  // Filtros avanzados (pueden ser undefined al inicio)
  roles: props.filters.roles,
  users: props.filters.users,
});

DTpermissions.value = props.can;
const sorting = ref<SortingState>([]);
const globalFilter = ref('');
const rowSelection = ref<RowSelectionState>({});

const pdfUrl = useExportUrl(
  PermissionExporterController.indexToPdf().url,
  activeFilters,
);

const excelUrl = useExportUrl(
  PermissionExporterController.indexToExcel().url,
  activeFilters,
);

const jsonUrl = useExportUrl(
  PermissionExporterController.indexToJson().url,
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

function applyFilters() {
  router.visit(PermissionController.index(), {
    data: activeFilters,
    only: ['permissions'],
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

const tableOptions = reactive<TableOptions<Permission>>({
  get data() {
    return props.permissions.data;
  },
  get columns() {
    return columns;
  },
  manualPagination: true,
  manualSorting: true,
  get meta() {
    return {
      currentPage: props.permissions.meta.current_page,
      pageSize: props.permissions.meta.per_page,
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

// ¡22 líneas de watch eliminadas! Ahora usa useActionAlerts

watchEffect(() =>
  resourceID.value === null ? (processingRowId.value = null) : false,
);

function handleAdvancedSearch() {
  router.reload({
    only: ['users', 'roles', 'operations'],
    onSuccess: () => (showAdvancedFilters.value = true),
  });
}
</script>

<template>
  <AppLayout :breadcrumbs="breadcrumbs">
    <Head title="Permisos" />

    <ContentLayout title="Permisos">
      <template #icon>
        <KeySquare />
      </template>
      <DataTable
        :can="can"
        :columns="columns"
        :data="permissions"
        :filters="filters"
        :search-only="['permissions']"
        :search-route="PermissionController.index()"
        :table="table"
        :is-advanced-search="advancedSearchApplied"
        :is-loading-new="requestState.create"
        :is-loading-dropdown="isProcessing"
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
        @export="handleExport"
        @advanced-search="handleAdvancedSearch"
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
        :roles
        :users
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
