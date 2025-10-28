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
import { BreadcrumbItem, Can, OperationType, PaginatedCollection, Permission, Role, User } from '@/types';
import { Head, router, usePage } from '@inertiajs/vue3';
import { getCoreRowModel, RowSelectionState, SortingState, TableOptions, useVueTable } from '@tanstack/vue-table';
import { UserIcon } from 'lucide-vue-next';
import { computed, reactive, ref, watch, watchEffect } from 'vue';
import { columns, permissions as permissionsDT, processingRowId } from './partials/columns';
import SheetAdvancedFilters from './partials/SheetAdvancedFilters.vue';
import UserController from "@/actions/App/Http/Controllers/Security/UserController";
import UserExporterController from "@/actions/App/Http/Controllers/Exporters/UserExporterController";

const props = defineProps<{
  can: Can;
  filters: object;
  permissions?: Array<Permission>;
  roles?: Array<Role>;
  users: PaginatedCollection<User>;
}>();

const breadcrumbs: BreadcrumbItem[] = [
  {
    title: 'Usuarios',
    href: '/users',
  },
];

const { action, resourceID, requestState, requestAction } = useRequestActions(UserController);
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

permissionsDT.value = props.can;
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

    router.visit(UserController.index(), {
      data,
      only: ['users'],
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
      alertAction.value = 'Eliminar';
      alertActionCss.value = 'bg-destructive text-destructive-foreground hover:bg-destructive/90';
      alertTitle.value = `¿Eliminar usuario(a) «${alertData.value?.name}»?`;
      alertDescription.value = `«${alertData.value?.name}» perderá el acceso al sistema. Sus datos se conservarán.`;
      alertOpen.value = true;
      break;
    case 'restore':
      alertAction.value = 'Restaurar';
      alertActionCss.value = '';
      alertTitle.value = `¿Restaurar usuario(a) «${alertData.value?.name}»?`;
      alertDescription.value = `«${alertData.value?.name}» recuperará el acceso al sistema. Sus datos se restaurarán.`;
      alertOpen.value = true;
      break;
    case 'force_destroy':
      alertAction.value = 'Eliminar permanentemente';
      alertActionCss.value = 'bg-destructive text-destructive-foreground hover:bg-destructive/90';
      alertTitle.value = `¿Eliminar usuario(a) «${alertData.value?.name}» permanentemente?`;
      alertDescription.value = `Esta acción no podrá revertirse. «${alertData.value?.name}» perderá el acceso al sistema. Sus datos se eliminarán.`;
      alertOpen.value = true;
      break;
    case 'enable':
      alertAction.value = 'Activar';
      alertActionCss.value = '';
      alertTitle.value = `Activar usuario(a) «${alertData.value?.name}»?`;
      alertDescription.value = `«${alertData.value?.name}» recuperará el acceso al sistema. Sus datos se restaurarán.`;
      alertOpen.value = true;
      break;
    case 'disable':
      alertAction.value = 'Desactivar';
      alertActionCss.value = 'bg-amber-500 text-foreground hover:bg-amber-500/90';
      alertTitle.value = `Desactivar usuario(a) «${alertData.value?.name}»?`;
      alertDescription.value = `«${alertData.value?.name}» perderá el acceso al sistema. Sus datos se conservarán.`;
      alertOpen.value = true;
      break;
    case 'batch_destroy':
      alertAction.value = 'Eliminar seleccionados';
      alertActionCss.value = 'bg-destructive text-destructive-foreground hover:bg-destructive/90';
      alertTitle.value = `¿Eliminar los registros que Usted ha seleccionado?`;
      alertDescription.value = `Esta acción podrá revertirse. Los datos no se eliminarán, sin embargo, los usuarios no podrán ingresar al sistema.`;
      alertOpen.value = true;
      break;
    case 'batch_enable':
      alertAction.value = 'Activar seleccionados';
      alertActionCss.value = '';
      alertTitle.value = `¿Activar los usuarios que Usted ha seleccionado?`;
      alertDescription.value = `Los usuarios recuperarán el acceso al sistema. Sus datos serán restaurados.`;
      alertOpen.value = true;
      break;
    case 'batch_disable':
      alertAction.value = 'Desactivar seleccionados';
      alertActionCss.value = 'bg-amber-500 text-foreground hover:bg-amber-500/90';
      alertTitle.value = `¿Desactivar los usuarios que Usted ha seleccionado?`;
      alertDescription.value = `Los usuarios perderán el acceso al sistema. Sus datos se conservarán.`;
      alertOpen.value = true;
      break;

    default:
      break;
  }
});
watchEffect(() => (resourceID.value === null ? (processingRowId.value = null) : false));

function handleAdvancedSearch() {
  router.reload({
    only: ['roles', 'permissions'],
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
        :is-loading-dropdown="requestState.batchDestroy"
        @batch-enable="handleBatchAction('batch_enable')"
        @batch-disable="handleBatchAction('batch_disable')"
        @batch-destroy="handleBatchAction('batch_destroy')"
        @search="(s) => (globalFilter = s)"
        @new="requestAction({ operation: 'create' })"
        @read="(row) => (requestAction({ operation: 'read', data: { id: row.id } }), (processingRowId = row.id))"
        @update="(row) => (requestAction({ operation: 'edit', data: { id: row.id } }), (processingRowId = row.id))"
        @destroy="(row) => handleAction('destroy', row)"
        @force-destroy="(row) => handleAction('force_destroy', row)"
        @restore="(row) => handleAction('restore', row)"
        @enable="(row) => handleAction('enable', row)"
        @disable="(row) => handleAction('disable', row)"
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
            <iframe :src="`${UserExporterController.indexToPdf().url}/${urlQueryString}`" frameborder="0" width="100%" height="100%"></iframe>
          </div>
        </SheetContent>
      </Sheet>

      <SheetAdvancedFilters
        :permissions
        :roles
        :statuses="[
          { value: 'disabled_at', name: 'Desactivado' },
          { value: 'deleted_at', name: 'Eliminado' },
        ]"
        :show="showAdvancedFilters"
        @close="showAdvancedFilters = false"
        @advanced-search="(advFilters) => ((advancedSearchApplied = true), (advancedFilters = advFilters))"
      />
    </ContentLayout>
  </AppLayout>
</template>
