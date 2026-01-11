import DataTableActions from '@/components/DataTableActions.vue';
import { ActivityLog, Can } from '@/types';
import { createColumnHelper } from '@tanstack/vue-table';
import { h, ref } from 'vue';

const columnHelper = createColumnHelper<ActivityLog>();

const permissions = ref<Can>({
  create: false,
  read: true,
  update: false,
  delete: false,
  enable: false,
  disable: false,
  export: false,
});

export const processingRowId = ref<number | string | null>(null);

export const columns = [
  columnHelper.display({
    id: 'serial',
    header: '#',
    cell: ({ row, table }) => {
      const meta = table.options.meta as { currentPage: number; pageSize: number };

      const startingIndex = (meta.currentPage - 1) * meta.pageSize;
      const trueIndex = startingIndex + row.index + 1;

      return h('div', trueIndex);
    },
    enableSorting: false,
    enableHiding: false,
  }),
  columnHelper.accessor('causer.name', {
    header: 'Usuario',
    cell: (info) => h('div', info.getValue()),
  }),
  columnHelper.accessor('description', {
    header: 'Nombre',
    cell: (info) => h('div', info.getValue()),
  }),
  columnHelper.accessor('created_at_human', {
    enableSorting: false,
    header: 'Fecha',
    cell: ({ row }) => h('div', row.getValue('created_at_human')),
  }),
  columnHelper.display({
    id: 'actions',
    enableHiding: false,
    enableSorting: false,
    meta: { class: 'sticky-right' },
    cell: ({ row }) => {
      return h(DataTableActions, {
        row: row.original,
        can: permissions.value,
        loading: processingRowId.value === row.original.id,
      });
    },
  }),
];
