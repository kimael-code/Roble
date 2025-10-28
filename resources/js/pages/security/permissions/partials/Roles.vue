<script setup lang="ts">
import DataTable from '@/components/DataTable.vue';
import { PaginatedCollection, Role } from '@/types';
import { router } from '@inertiajs/vue3';
import { getCoreRowModel, SortingState, TableOptions, useVueTable } from '@tanstack/vue-table';
import { reactive, ref } from 'vue';
import { columns } from './columnsRole';

interface Props {
  filters: object;
  permissionId: string | number;
  roles: PaginatedCollection<Role>;
}
const props = defineProps<Props>();

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

    router.visit(route('permissions.show', { permission: props.permissionId }), {
      data,
      only: ['roles'],
      preserveScroll: true,
      preserveState: true,
      onSuccess: () => (sorting.value = sortValue),
    });
  }
}

const tableOptions = reactive<TableOptions<Role>>({
  get data() {
    return props.roles.data;
  },
  get columns() {
    return columns;
  },
  manualPagination: true,
  manualSorting: true,
  get meta() {
    return {
      currentPage: props.roles.meta.current_page,
      pageSize: props.roles.meta.per_page,
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
</script>

<template>
  <DataTable
    :columns="columns"
    :data="roles"
    :filters
    :search-only="['roles']"
    :search-route="route('permissions.show', { permission: permissionId })"
    :table
    @search="(s) => (globalFilter = s)"
  />
</template>
