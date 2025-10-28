<script setup lang="ts">
import DataTable from '@/components/DataTable.vue';
import { PaginatedCollection, Permission } from '@/types';
import { router } from '@inertiajs/vue3';
import { getCoreRowModel, SortingState, TableOptions, useVueTable } from '@tanstack/vue-table';
import { reactive, ref } from 'vue';
import { columns } from './columnsPermission';
import RoleController from "@/actions/App/Http/Controllers/Security/RoleController";

interface Props {
  filters: object;
  roleId: number;
  permissions: PaginatedCollection<Permission>;
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

    router.visit(RoleController.show(props.roleId), {
      data,
      only: ['permissions'],
      preserveScroll: true,
      preserveState: true,
      onSuccess: () => (sorting.value = sortValue),
    });
  }
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
    :data="permissions"
    :filters
    :search-only="['permissions']"
    :search-route="RoleController.show(roleId)"
    :table
    @search="(s) => (globalFilter = s)"
  />
</template>
