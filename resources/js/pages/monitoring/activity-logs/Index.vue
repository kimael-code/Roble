<script setup lang="ts">
import ActivityLogExporter from '@/actions/App/Http/Controllers/Exporters/ActivityLogExporterController';
import ActivityLogController from '@/actions/App/Http/Controllers/Monitoring/ActivityLogController';
import DataTable from '@/components/DataTable.vue';
import {
  Sheet,
  SheetContent,
  SheetDescription,
  SheetHeader,
  SheetTitle,
} from '@/components/ui/sheet';
import { valueUpdater } from '@/components/ui/table/utils';
import { useRequestActions } from '@/composables';
import { useExportUrl } from '@/composables/useExportUrl';
import AppLayout from '@/layouts/AppLayout.vue';
import ContentLayout from '@/layouts/ContentLayout.vue';
import {
  ActivityLog,
  BreadcrumbItem,
  Can,
  PaginatedCollection,
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
import { LogsIcon } from 'lucide-vue-next';
import { reactive, ref, watchEffect } from 'vue';
import { columns, permissions, processingRowId } from './partials/columns';
import SheetAdvancedFilters from './partials/SheetAdvancedFilters.vue';

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
  },
];

const { requestAction, requestState } = useRequestActions(
  ActivityLogController,
);
const showPdf = ref(false);
const showAdvancedFilters = ref(false);
const advancedSearchApplied = ref(false);

const activeFilters = reactive({
  search: props.filters.search ?? '',
  per_page: props.logs.meta.per_page,
  sort_by: {} as Record<string, string>,
  // Filtros avanzados (pueden ser undefined al inicio)
  date: props.filters.date,
  date_range: props.filters.date_range,
  ips: props.filters.ips,
  users: props.filters.users,
  events: props.filters.events,
  modules: props.filters.modules,
  time: props.filters.time,
  time_from: props.filters.time_from,
  time_until: props.filters.time_until,
});

permissions.value = props.can;
const sorting = ref<SortingState>([]);
const globalFilter = ref('');
const rowSelection = ref<RowSelectionState>({});

const pdfUrl = useExportUrl(
  ActivityLogExporter.indexToPdf().url,
  activeFilters,
);

function applyFilters() {
  router.visit(ActivityLogController.index(), {
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

watchEffect(() =>
  requestState.value.read === false ? (processingRowId.value = null) : false,
);

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
        @search="
          (s) => {
            activeFilters.search = s;
            applyFilters();
          }
        "
        @read="
          (row) => (
            requestAction({ operation: 'read', data: { id: row.id } }),
            (processingRowId = row.id)
          )
        "
        @export="showPdf = true"
        @advanced-search="handleAdvancedSearch"
      />

      <Sheet v-model:open="showPdf">
        <SheetContent side="bottom">
          <SheetHeader>
            <SheetTitle>Exportar a PDF</SheetTitle>
            <SheetDescription
              >Reporte: Trazas de Actividades de Usuarios</SheetDescription
            >
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
        :events
        :log-names
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
