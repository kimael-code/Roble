<script setup lang="ts">
import DataTable from '@/components/DataTable.vue';
import { useRequestActions } from '@/composables';
import { ActivityLog, PaginatedCollection, SearchFilter } from '@/types';
import { router } from '@inertiajs/vue3';
import { getCoreRowModel, SortingState, TableOptions, useVueTable } from '@tanstack/vue-table';
import { reactive, ref, watchEffect } from 'vue';
import { columns, processingRowId } from './columns';
import routes from "@/actions/App/Http/Controllers/Monitoring/ActivityLogController";
import { RouteDefinition } from '@/wayfinder';

const props = defineProps<{
  filters: SearchFilter;
  logs: PaginatedCollection<ActivityLog>;
  route: RouteDefinition<"get">;
}>();

const { resourceID, requestAction } = useRequestActions(routes);

const sorting = ref<SortingState>([]);
const globalFilter = ref('');

function handleSortingChange(item: any) {
  if (typeof item === 'function') {
    const sortValue = item(sorting.value);
    const data: { [index: string]: any } = {};

    sortValue.forEach((element: any) => {
      const sortBy = element?.id ? element.id : '';
      const sortDirection = sortBy ? (element?.desc ? 'desc' : 'asc') : '';
      data[sortBy] = sortDirection;
    });

    router.visit(props.route, {
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
  state: {
    get sorting() {
      return sorting.value;
    },
    get globalFilter() {
      return globalFilter.value;
    },
  },
});

const table = useVueTable(tableOptions);

watchEffect(() => (resourceID.value === null ? (processingRowId.value = null) : false));
</script>

<template>
  <DataTable
    :columns="columns"
    :data="logs"
    :filters
    :search-only="['logs']"
    :search-route="route"
    :table
    @search="(s) => (globalFilter = s)"
    @read="(row) => (requestAction({ operation: 'read', data: { id: row.id } }), (processingRowId = row.id))"
  />
</template>
