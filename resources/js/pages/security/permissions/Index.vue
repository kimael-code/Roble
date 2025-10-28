<script setup lang="ts">
import DataTable from '@/components/DataTable.vue';
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
import { Sheet, SheetContent, SheetDescription, SheetHeader, SheetTitle } from '@/components/ui/sheet';
import { valueUpdater } from '@/components/ui/table/utils';
import { useConfirmAction, useRequestActions } from '@/composables';
import AppLayout from '@/layouts/AppLayout.vue';
import ContentLayout from '@/layouts/ContentLayout.vue';
import { BreadcrumbItem, Can, OperationType, PaginatedCollection, Permission, Role, SearchFilter, User } from '@/types';
import { Head, router, usePage } from '@inertiajs/vue3';
import { getCoreRowModel, RowSelectionState, SortingState, TableOptions, useVueTable } from '@tanstack/vue-table';
import { KeySquare } from 'lucide-vue-next';
import { computed, reactive, ref, watch, watchEffect } from 'vue';
import { columns, permissions as DTpermissions, processingRowId } from './partials/columns';
import SheetAdvancedFilters from './partials/SheetAdvancedFilters.vue';
import PermissionController from "@/actions/App/Http/Controllers/Security/PermissionController";
import PermissionExporterController from "@/actions/App/Http/Controllers/Exporters/PermissionExporterController";

const props = defineProps<{
  can: Can;
  filters: SearchFilter;
  users?: Array<User>;
  roles?: Array<Role>;
  permissions: PaginatedCollection<Permission>;
}>();

const breadcrumbs: BreadcrumbItem[] = [
  {
    title: 'Permisos',
    href: '/permissions',
  },
];

const { action, resourceID, requestState, requestAction } = useRequestActions(PermissionController);
const { alertOpen, alertAction, alertActionCss, alertTitle, alertDescription, alertData } = useConfirmAction();
const showPdf = ref(false);
const showAdvancedFilters = ref(false);
const advancedSearchApplied = ref(false);
const advancedFilters = ref({});
const page = usePage();

const urlQueryString = computed(() => {
  const queryString = page.url.indexOf('?');

  return queryString >= 0 ? page.url.substring(queryString) : '';
});

DTpermissions.value = props.can;
const sorting = ref<SortingState>([]);
const globalFilter = ref('');
const rowSelection = ref<RowSelectionState>({});

function handleSortingChange(item: any) {
  if (typeof item === 'function') {
    const sortValue = item(sorting.value);
    const data: { [index: string]: any } = {
      ...advancedFilters.value, // Preserve advanced filters
      per_page: table.getState().pagination.pageSize,
    };

    sortValue.forEach((element: any) => {
      const sortBy = element?.id ? element.id : '';
      if (sortBy) {
        data[`sort_by[${sortBy}]`] = element?.desc ? 'desc' : 'asc';
      }
    });

    router.visit(PermissionController.index(), {
      data,
      only: ['permissions'],
      preserveScroll: true,
      preserveState: true,
      onSuccess: () => (sorting.value = sortValue),
    });
  }
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
  onRowSelectionChange: (updaterOrValue) => valueUpdater(updaterOrValue, rowSelection),
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

watch(action, () => {
  switch (action.value) {
    case 'destroy':
      alertAction.value = 'Eliminar permanentemente';
      alertActionCss.value = 'bg-destructive text-destructive-foreground hover:bg-destructive/90';
      alertTitle.value = `¿Eliminar permiso «${alertData.value.name}» permanentemente?`;
      alertDescription.value = `Esta acción no podrá revertirse. Los datos de «${alertData.value.name}» se perderán permanentemente.`;
      alertOpen.value = true;
      break;
    case 'batch_destroy':
      alertAction.value = 'Eliminar seleccionados';
      alertActionCss.value = 'bg-destructive text-destructive-foreground hover:bg-destructive/90';
      alertTitle.value = `¿Eliminar los registros que Usted ha seleccionado?`;
      alertDescription.value = `Esta acción no podrá revertirse. Los datos se perderán permanentemente.`;
      alertOpen.value = true;
      break;

    default:
      break;
  }
});

watchEffect(() => (resourceID.value === null ? (processingRowId.value = null) : false));

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
        :is-loading-dropdown="requestState.batchDestroy"
        @batch-destroy="handleBatchAction('batch_destroy')"
        @search="(s) => (globalFilter = s)"
        @new="requestAction({ operation: 'create' })"
        @read="(row) => (requestAction({ operation: 'read', data: { id: row.id } }), (processingRowId = row.id))"
        @update="(row) => (requestAction({ operation: 'edit', data: { id: row.id } }), (processingRowId = row.id))"
        @destroy="(row) => handleAction('destroy', row)"
        @export="showPdf = true"
        @advanced-search="handleAdvancedSearch"
      />

      <AlertDialog v-model:open="alertOpen">
        <AlertDialogContent>
          <AlertDialogHeader>
            <AlertDialogTitle>{{ alertTitle }}</AlertDialogTitle>
            <AlertDialogDescription>{{ alertDescription }}</AlertDialogDescription>
          </AlertDialogHeader>
          <AlertDialogFooter>
            <AlertDialogCancel @click="((action = null), (processingRowId = null))">Cancelar</AlertDialogCancel>
            <AlertDialogAction :class="alertActionCss" @click="requestAction({ data: alertData, options: { preserveState: false } })">
              {{ alertAction }}
            </AlertDialogAction>
          </AlertDialogFooter>
        </AlertDialogContent>
      </AlertDialog>

      <Sheet v-model:open="showPdf">
        <SheetContent side="bottom">
          <SheetHeader>
            <SheetTitle>Exportar a PDF</SheetTitle>
            <SheetDescription>Reporte: Permisos</SheetDescription>
          </SheetHeader>
          <div class="h-[70dvh]">
            <iframe :src="`${PermissionExporterController.indexToPdf().url}/${urlQueryString}`" frameborder="0" width="100%" height="100%"></iframe>
          </div>
        </SheetContent>
      </Sheet>

      <SheetAdvancedFilters
        :roles
        :users
        :show="showAdvancedFilters"
        @close="showAdvancedFilters = false"
        @advanced-search="(advFilters) => ((advancedSearchApplied = true), (advancedFilters = advFilters))"
      />
    </ContentLayout>
  </AppLayout>
</template>
