<script setup lang="ts">
import DataTable from '@/components/DataTable.vue';
import { Sheet, SheetContent, SheetDescription, SheetHeader, SheetTitle } from '@/components/ui/sheet';
import { valueUpdater } from '@/components/ui/table/utils';
import { useRequestActions } from '@/composables';
import AppLayout from '@/layouts/AppLayout.vue';
import ContentLayout from '@/layouts/ContentLayout.vue';
import { ActivityLog, BreadcrumbItem, Can, PaginatedCollection, SearchFilter, User } from '@/types';
import { Head, router, usePage } from '@inertiajs/vue3';
import { getCoreRowModel, RowSelectionState, SortingState, TableOptions, useVueTable } from '@tanstack/vue-table';
import { LogsIcon } from 'lucide-vue-next';
import { computed, reactive, ref, watchEffect } from 'vue';
import { columns, permissions, processingRowId } from './partials/columns';
import SheetAdvancedFilters from './partials/SheetAdvancedFilters.vue';
import ActivityLogController from "@/actions/App/Http/Controllers/Monitoring/ActivityLogController";

const props = defineProps<{
  can: Can;
  filters: SearchFilter;
  users?: Array<User>;
  events?: Array<string>;
  logNames?: Array<string>;
  logs: PaginatedCollection<ActivityLog>;
}>();

const breadcrumbs: BreadcrumbItem[] = [
  {
    title: 'Trazas',
    href: '/activity-logs',
  },
];

const { requestAction, requestState } = useRequestActions(ActivityLogController);
const showPdf = ref(false);
const showAdvancedFilters = ref(false);
const advancedSearchApplied = ref(false);
const advancedFilters = ref({});
const page = usePage();

const urlQueryString = computed(() => {
  const queryString = page.url.indexOf('?');

  return queryString >= 0 ? page.url.substring(queryString) : '';
});

permissions.value = props.can;
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

    router.visit(ActivityLogController.index(), {
      data,
      only: ['logs'],
      preserveScroll: true,
      preserveState: true,
      onSuccess: () => (sorting.value = sortValue),
    });
  }
}

const tableOptions = reactive<TableOptions<ActivityLog>>({
  get data() {
    return props.logs.data;
  },
  get columns() {
    return columns;
  },
  manualPagination: true,
  manualSorting: true,
  get meta() {
    return {
      currentPage: props.logs.meta.current_page,
      pageSize: props.logs.meta.per_page,
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

watchEffect(() => (requestState.value.read === false ? (processingRowId.value = null) : false));

function handleAdvancedSearch() {
  router.reload({
    only: ['users', 'events', 'logNames'],
    onSuccess: () => (showAdvancedFilters.value = true),
  });
}
</script>

<template>
  <AppLayout :breadcrumbs="breadcrumbs">
    <Head title="Trazas" />

    <ContentLayout title="Trazas">
      <template #icon>
        <LogsIcon />
      </template>
      <DataTable
        :can="can"
        :columns="columns"
        :data="logs"
        :filters="filters"
        :search-only="['logs']"
        :search-route="ActivityLogController.index()"
        :table="table"
        :is-advanced-search="advancedSearchApplied"
        @search="(s) => (globalFilter = s)"
        @read="(row) => (requestAction({ operation: 'read', data: { id: row.id } }), (processingRowId = row.id))"
        @export="showPdf = true"
        @advanced-search="handleAdvancedSearch"
      />

      <Sheet v-model:open="showPdf">
        <SheetContent side="bottom">
          <SheetHeader>
            <SheetTitle>Exportar a PDF</SheetTitle>
            <SheetDescription>Reporte: Trazas de Actividades de Usuarios</SheetDescription>
          </SheetHeader>
          <div class="h-[70dvh]">
            <iframe :src="`${ActivityLogController.index().url}/${urlQueryString}`" frameborder="0" width="100%" height="100%"></iframe>
          </div>
        </SheetContent>
      </Sheet>

      <SheetAdvancedFilters
        :events
        :log-names
        :users
        :show="showAdvancedFilters"
        @close="showAdvancedFilters = false"
        @advanced-search="(advFilters) => ((advancedSearchApplied = true), (advancedFilters = advFilters))"
      />
    </ContentLayout>
  </AppLayout>
</template>
