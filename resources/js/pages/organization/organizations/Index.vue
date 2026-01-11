<script setup lang="ts">
import routes from '@/actions/App/Http/Controllers/Organization/OrganizationController';
import ActionAlertDialog from '@/components/ActionAlertDialog.vue';
import DataTable from '@/components/DataTable.vue';
import { valueUpdater } from '@/components/ui/table/utils';
import { useRequestActions, useActionAlerts } from '@/composables';
import AppLayout from '@/layouts/AppLayout.vue';
import ContentLayout from '@/layouts/ContentLayout.vue';
import {
  BreadcrumbItem,
  Can,
  OperationType,
  Organization,
  PaginatedCollection,
} from '@/types';
import { Head, router } from '@inertiajs/vue3';
import {
  getCoreRowModel,
  RowSelectionState,
  SortingState,
  TableOptions,
  useVueTable,
} from '@tanstack/vue-table';
import { Building } from 'lucide-vue-next';
import { reactive, ref, computed, watchEffect } from 'vue';
import { columns, permissions, processingRowId } from './partials/columns';

const props = defineProps<{
  can: Can;
  filters: object;
  organizations: PaginatedCollection<Organization>;
}>();

const breadcrumbs: BreadcrumbItem[] = [
  {
    title: 'Entes',
    href: '/organizations',
  },
];

const { action, resourceID, requestState, requestAction, isProcessing } =
  useRequestActions(routes);

const alertData = ref<any>(null);
const resourceName = computed(() => alertData.value?.name || '');

const { alertOpen, alertAction, alertActionCss, alertTitle, alertDescription } =
  useActionAlerts(action, resourceName);

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

    router.visit(routes.index(), {
      data,
      only: ['organizations'],
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

const tableOptions = reactive<TableOptions<Organization>>({
  get data() {
    return props.organizations.data;
  },
  get columns() {
    return columns;
  },
  manualPagination: true,
  manualSorting: true,
  get meta() {
    return {
      currentPage: props.organizations.meta.current_page,
      pageSize: props.organizations.meta.per_page,
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
</script>

<template>
  <AppLayout :breadcrumbs="breadcrumbs">
    <Head title="Entes" />

    <ContentLayout title="Entes">
      <template #icon>
        <Building />
      </template>
      <DataTable
        :can="can"
        :columns="columns"
        :data="organizations"
        :filters="filters"
        :search-only="['organizations']"
        :search-route="routes.index()"
        :table="table"
        :is-loading-new="requestState.create"
        :is-loading-dropdown="isProcessing"
        :has-advanced-search="false"
        @batch-destroy="handleBatchAction('batch_destroy')"
        @search="(s) => (globalFilter = s)"
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
    </ContentLayout>
  </AppLayout>
</template>
