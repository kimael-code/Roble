import DataTableActions from '@/components/DataTableActions.vue';
import { Button } from '@/components/ui/button';
import {
  DropdownMenu,
  DropdownMenuContent,
  DropdownMenuLabel,
  DropdownMenuRadioGroup,
  DropdownMenuRadioItem,
  DropdownMenuSeparator,
  DropdownMenuTrigger,
} from '@/components/ui/dropdown-menu';
import { ActivityLog, Can } from '@/types';
import { createColumnHelper } from '@tanstack/vue-table';
import { ChevronDown, ChevronsUpDown, ChevronUp } from 'lucide-vue-next';
import { computed, h, ref } from 'vue';

const columnHelper = createColumnHelper<ActivityLog>();

export const permissions = ref<Can>({
  create: false,
  read: false,
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
  columnHelper.accessor('created_at_human', {
    header: ({ column }) => {
      const isSorted = column.getIsSorted();
      const direction = computed(() => {
        const sorted = column.getIsSorted();
        if (sorted === 'asc') return 'asc';
        if (sorted === 'desc') return 'desc';
        return 'none';
      });

      return h(DropdownMenu, () => [
        h(DropdownMenuTrigger, { asChild: true }, () => [
          h(Button, { variant: isSorted ? 'default' : 'ghost' }, () => [
            'Fecha',
            isSorted === 'desc'
              ? h(ChevronDown, { class: 'ml-2 h-4 w-4' })
              : isSorted === 'asc'
                ? h(ChevronUp, { class: 'ml-2 h-4 w-4' })
                : h(ChevronsUpDown, { class: 'ml-2 h-4 w-4' }),
          ]),
        ]),
        h(DropdownMenuContent, { align: 'start' }, () => [
          h(DropdownMenuLabel, () => 'Ordenar'),
          h(DropdownMenuSeparator),
          h(DropdownMenuRadioGroup, { modelValue: direction.value }, () => [
            h(DropdownMenuRadioItem, { value: 'asc', onSelect: () => column.toggleSorting(false, true) }, () => 'ASC'),
            h(DropdownMenuRadioItem, { value: 'desc', onSelect: () => column.toggleSorting(true, true) }, () => 'DESC'),
            h(DropdownMenuRadioItem, { value: 'none', onSelect: () => column.clearSorting() }, () => 'Restablecer'),
          ]),
        ]),
      ]);
    },
    cell: (info) => h('div', { class: 'px-2' }, info.getValue()),
  }),
  columnHelper.accessor('causer_name', {
    header: ({ column }) => {
      const isSorted = column.getIsSorted();
      const direction = computed(() => {
        const sorted = column.getIsSorted();
        if (sorted === 'asc') return 'asc';
        if (sorted === 'desc') return 'desc';
        return 'none';
      });

      return h(DropdownMenu, () => [
        h(DropdownMenuTrigger, { asChild: true }, () => [
          h(Button, { variant: isSorted ? 'default' : 'ghost' }, () => [
            'Usuario',
            isSorted === 'desc'
              ? h(ChevronDown, { class: 'ml-2 h-4 w-4' })
              : isSorted === 'asc'
                ? h(ChevronUp, { class: 'ml-2 h-4 w-4' })
                : h(ChevronsUpDown, { class: 'ml-2 h-4 w-4' }),
          ]),
        ]),
        h(DropdownMenuContent, { align: 'start' }, () => [
          h(DropdownMenuLabel, () => 'Ordenar'),
          h(DropdownMenuSeparator),
          h(DropdownMenuRadioGroup, { modelValue: direction.value }, () => [
            h(DropdownMenuRadioItem, { value: 'asc', onSelect: () => column.toggleSorting(false, true) }, () => 'ASC'),
            h(DropdownMenuRadioItem, { value: 'desc', onSelect: () => column.toggleSorting(true, true) }, () => 'DESC'),
            h(DropdownMenuRadioItem, { value: 'none', onSelect: () => column.clearSorting() }, () => 'Restablecer'),
          ]),
        ]),
      ]);
    },
    cell: (info) => h('div', { class: 'px-2' }, info.getValue()),
  }),
  columnHelper.accessor('event', {
    header: ({ column }) => {
      const isSorted = column.getIsSorted();
      const direction = computed(() => {
        const sorted = column.getIsSorted();
        if (sorted === 'asc') return 'asc';
        if (sorted === 'desc') return 'desc';
        return 'none';
      });

      return h(DropdownMenu, () => [
        h(DropdownMenuTrigger, { asChild: true }, () => [
          h(Button, { variant: isSorted ? 'default' : 'ghost' }, () => [
            'Evento',
            isSorted === 'desc'
              ? h(ChevronDown, { class: 'ml-2 h-4 w-4' })
              : isSorted === 'asc'
                ? h(ChevronUp, { class: 'ml-2 h-4 w-4' })
                : h(ChevronsUpDown, { class: 'ml-2 h-4 w-4' }),
          ]),
        ]),
        h(DropdownMenuContent, { align: 'start' }, () => [
          h(DropdownMenuLabel, () => 'Ordenar'),
          h(DropdownMenuSeparator),
          h(DropdownMenuRadioGroup, { modelValue: direction.value }, () => [
            h(DropdownMenuRadioItem, { value: 'asc', onSelect: () => column.toggleSorting(false, true) }, () => 'ASC'),
            h(DropdownMenuRadioItem, { value: 'desc', onSelect: () => column.toggleSorting(true, true) }, () => 'DESC'),
            h(DropdownMenuRadioItem, { value: 'none', onSelect: () => column.clearSorting() }, () => 'Restablecer'),
          ]),
        ]),
      ]);
    },
    cell: (info) => h('div', { class: 'px-2' }, info.getValue()),
  }),
  columnHelper.accessor('log_name', {
    header: ({ column }) => {
      const isSorted = column.getIsSorted();
      const direction = computed(() => {
        const sorted = column.getIsSorted();
        if (sorted === 'asc') return 'asc';
        if (sorted === 'desc') return 'desc';
        return 'none';
      });

      return h(DropdownMenu, () => [
        h(DropdownMenuTrigger, { asChild: true }, () => [
          h(Button, { variant: isSorted ? 'default' : 'ghost' }, () => [
            'Módulo/Func.',
            isSorted === 'desc'
              ? h(ChevronDown, { class: 'ml-2 h-4 w-4' })
              : isSorted === 'asc'
                ? h(ChevronUp, { class: 'ml-2 h-4 w-4' })
                : h(ChevronsUpDown, { class: 'ml-2 h-4 w-4' }),
          ]),
        ]),
        h(DropdownMenuContent, { align: 'start' }, () => [
          h(DropdownMenuLabel, () => 'Ordenar'),
          h(DropdownMenuSeparator),
          h(DropdownMenuRadioGroup, { modelValue: direction.value }, () => [
            h(DropdownMenuRadioItem, { value: 'asc', onSelect: () => column.toggleSorting(false, true) }, () => 'ASC'),
            h(DropdownMenuRadioItem, { value: 'desc', onSelect: () => column.toggleSorting(true, true) }, () => 'DESC'),
            h(DropdownMenuRadioItem, { value: 'none', onSelect: () => column.clearSorting() }, () => 'Restablecer'),
          ]),
        ]),
      ]);
    },
    cell: (info) => h('div', { class: 'px-2' }, info.getValue()),
  }),
  columnHelper.accessor('description', {
    header: ({ column }) => {
      const isSorted = column.getIsSorted();
      const direction = computed(() => {
        const sorted = column.getIsSorted();
        if (sorted === 'asc') return 'asc';
        if (sorted === 'desc') return 'desc';
        return 'none';
      });

      return h(DropdownMenu, () => [
        h(DropdownMenuTrigger, { asChild: true }, () => [
          h(Button, { variant: isSorted ? 'default' : 'ghost' }, () => [
            'Descripción',
            isSorted === 'desc'
              ? h(ChevronDown, { class: 'ml-2 h-4 w-4' })
              : isSorted === 'asc'
                ? h(ChevronUp, { class: 'ml-2 h-4 w-4' })
                : h(ChevronsUpDown, { class: 'ml-2 h-4 w-4' }),
          ]),
        ]),
        h(DropdownMenuContent, { align: 'start' }, () => [
          h(DropdownMenuLabel, () => 'Ordenar'),
          h(DropdownMenuSeparator),
          h(DropdownMenuRadioGroup, { modelValue: direction.value }, () => [
            h(DropdownMenuRadioItem, { value: 'asc', onSelect: () => column.toggleSorting(false, true) }, () => 'ASC'),
            h(DropdownMenuRadioItem, { value: 'desc', onSelect: () => column.toggleSorting(true, true) }, () => 'DESC'),
            h(DropdownMenuRadioItem, { value: 'none', onSelect: () => column.clearSorting() }, () => 'Restablecer'),
          ]),
        ]),
      ]);
    },
    cell: (info) => h('div', { class: 'px-2' }, info.getValue()),
  }),
  columnHelper.accessor('subject_id', {
    header: ({ column }) => {
      const isSorted = column.getIsSorted();
      const direction = computed(() => {
        const sorted = column.getIsSorted();
        if (sorted === 'asc') return 'asc';
        if (sorted === 'desc') return 'desc';
        return 'none';
      });

      return h(DropdownMenu, () => [
        h(DropdownMenuTrigger, { asChild: true }, () => [
          h(Button, { variant: isSorted ? 'default' : 'ghost' }, () => [
            'ID',
            isSorted === 'desc'
              ? h(ChevronDown, { class: 'ml-2 h-4 w-4' })
              : isSorted === 'asc'
                ? h(ChevronUp, { class: 'ml-2 h-4 w-4' })
                : h(ChevronsUpDown, { class: 'ml-2 h-4 w-4' }),
          ]),
        ]),
        h(DropdownMenuContent, { align: 'start' }, () => [
          h(DropdownMenuLabel, () => 'Ordenar'),
          h(DropdownMenuSeparator),
          h(DropdownMenuRadioGroup, { modelValue: direction.value }, () => [
            h(DropdownMenuRadioItem, { value: 'asc', onSelect: () => column.toggleSorting(false, true) }, () => 'ASC'),
            h(DropdownMenuRadioItem, { value: 'desc', onSelect: () => column.toggleSorting(true, true) }, () => 'DESC'),
            h(DropdownMenuRadioItem, { value: 'none', onSelect: () => column.clearSorting() }, () => 'Restablecer'),
          ]),
        ]),
      ]);
    },
    cell: (info) => h('div', { class: 'px-2' }, info.getValue()),
  }),
  columnHelper.accessor('ip_address', {
    header: ({ column }) => {
      const isSorted = column.getIsSorted();
      const direction = computed(() => {
        const sorted = column.getIsSorted();
        if (sorted === 'asc') return 'asc';
        if (sorted === 'desc') return 'desc';
        return 'none';
      });

      return h(DropdownMenu, () => [
        h(DropdownMenuTrigger, { asChild: true }, () => [
          h(Button, { variant: isSorted ? 'default' : 'ghost' }, () => [
            'IP Origen',
            isSorted === 'desc'
              ? h(ChevronDown, { class: 'ml-2 h-4 w-4' })
              : isSorted === 'asc'
                ? h(ChevronUp, { class: 'ml-2 h-4 w-4' })
                : h(ChevronsUpDown, { class: 'ml-2 h-4 w-4' }),
          ]),
        ]),
        h(DropdownMenuContent, { align: 'start' }, () => [
          h(DropdownMenuLabel, () => 'Ordenar'),
          h(DropdownMenuSeparator),
          h(DropdownMenuRadioGroup, { modelValue: direction.value }, () => [
            h(DropdownMenuRadioItem, { value: 'asc', onSelect: () => column.toggleSorting(false, true) }, () => 'ASC'),
            h(DropdownMenuRadioItem, { value: 'desc', onSelect: () => column.toggleSorting(true, true) }, () => 'DESC'),
            h(DropdownMenuRadioItem, { value: 'none', onSelect: () => column.clearSorting() }, () => 'Restablecer'),
          ]),
        ]),
      ]);
    },
    cell: (info) => h('div', { class: 'px-2' }, info.getValue()),
  }),
  columnHelper.display({
    id: 'actions',
    enableHiding: false,
    meta: { class: 'sticky-right' },
    cell: ({ row }) => {
      return h(DataTableActions, {
        row: row.original,
        can: permissions.value,
        loading: processingRowId.value === row.original.id,
        onExpand: row.toggleExpanded,
      });
    },
  }),
];
