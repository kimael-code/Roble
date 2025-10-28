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
import { valueUpdater } from '@/components/ui/table/utils';
import { useConfirmAction, useRequestActions } from '@/composables';
import AppLayout from '@/layouts/AppLayout.vue';
import ContentLayout from '@/layouts/ContentLayout.vue';
import { BreadcrumbItem, Can, OperationType, OrganizationalUnit, PaginatedCollection } from '@/types';
import { Head, router } from '@inertiajs/vue3';
import { getCoreRowModel, RowSelectionState, SortingState, TableOptions, useVueTable } from '@tanstack/vue-table';
import { Workflow } from 'lucide-vue-next';
import { reactive, ref, watch, watchEffect } from 'vue';
import { columns, permissions, processingRowId } from './partials/columns';
import OrganizationalUnitController from "@/actions/App/Http/Controllers/Organization/OrganizationalUnitController";

const props = defineProps<{
  can: Can;
  filters: object;
  organizationalUnits: PaginatedCollection<OrganizationalUnit>;
}>();

const breadcrumbs: BreadcrumbItem[] = [
  {
    title: 'Unidades Administrativas',
    href: '/organizational-units',
  },
];

const { action, resourceID, requestState, requestAction } = useRequestActions(OrganizationalUnitController);
const { alertOpen, alertAction, alertActionCss, alertTitle, alertDescription, alertData } = useConfirmAction();

permissions.value = props.can;
const sorting = ref<SortingState>([]);
const globalFilter = ref('');
const rowSelection = ref<RowSelectionState>({});

function handleSortingChange(item: any) {
  if (typeof item === 'function') {
    const sortValue = item(sorting.value);
    const data: { [index: string]: any } = {
      per_page: table.getState().pagination.pageSize,
    };

    sortValue.forEach((element: any) => {
      const sortBy = element?.id ? element.id : '';
      if (sortBy) {
        data[`sort_by[${sortBy}]`] = element?.desc ? 'desc' : 'asc';
      }
    });

    router.visit(OrganizationalUnitController.index(), {
      data,
      only: ['organizationalUnits'],
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

const tableOptions = reactive<TableOptions<OrganizationalUnit>>({
  get data() {
    return props.organizationalUnits.data;
  },
  get columns() {
    return columns;
  },
  manualPagination: true,
  manualSorting: true,
  get meta() {
    return {
      currentPage: props.organizationalUnits.meta.current_page,
      pageSize: props.organizationalUnits.meta.per_page,
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
      alertTitle.value = `¿Eliminar unidad administrativa «${alertData.value.name}» permanentemente?`;
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
</script>

<template>
  <AppLayout :breadcrumbs="breadcrumbs">
    <Head title="Unidades Administrativas" />

    <ContentLayout title="Unidades Administrativas">
      <template #icon>
        <Workflow />
      </template>
      <DataTable
        :can="can"
        :columns="columns"
        :data="organizationalUnits"
        :filters="filters"
        :search-only="['organizationalUnits']"
        :search-route="OrganizationalUnitController.index()"
        :table="table"
        :is-loading-new="requestState.create"
        :is-loading-dropdown="requestState.batchDestroy"
        @batch-destroy="handleBatchAction('batch_destroy')"
        @search="(s) => (globalFilter = s)"
        @new="requestAction({ operation: 'create' })"
        @read="(row) => (requestAction({ operation: 'read', data: { id: row.id } }), (processingRowId = row.id))"
        @update="(row) => (requestAction({ operation: 'edit', data: { id: row.id } }), (processingRowId = row.id))"
        @destroy="(row) => handleAction('destroy', row)"
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
    </ContentLayout>
  </AppLayout>
</template>
