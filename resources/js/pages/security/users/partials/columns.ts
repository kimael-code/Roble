import DataTableActions from '@/components/DataTableActions.vue';
import { Button } from '@/components/ui/button';
import { Checkbox } from '@/components/ui/checkbox';
import {
  DropdownMenu,
  DropdownMenuContent,
  DropdownMenuLabel,
  DropdownMenuRadioGroup,
  DropdownMenuRadioItem,
  DropdownMenuSeparator,
  DropdownMenuTrigger,
} from '@/components/ui/dropdown-menu';
import { Tooltip, TooltipContent, TooltipProvider, TooltipTrigger } from '@/components/ui/tooltip';
import { Can, User } from '@/types';
import { createColumnHelper } from '@tanstack/vue-table';
import { ChevronDown, ChevronsUpDown, ChevronUp } from 'lucide-vue-next';
import { computed, h, ref } from 'vue';

const columnHelper = createColumnHelper<User>();

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
    id: 'select',
    header: ({ table }) =>
      h(TooltipProvider, () =>
        h(Tooltip, { delayDuration: 200 }, () => [
          h(TooltipTrigger, { asChild: true }, () =>
            h(Checkbox, {
              modelValue: table.getIsAllPageRowsSelected() || (table.getIsSomePageRowsSelected() && 'indeterminate'),
              'onUpdate:modelValue': (value) => table.toggleAllPageRowsSelected(!!value),
              ariaLabel: 'Marcar/Desmarcar todo',
            }),
          ),
          h(TooltipContent, () => h('p', 'Marcar/Desmarcar todos los registros de la página')),
        ]),
      ),
    cell: ({ row }) =>
      h(TooltipProvider, () =>
        h(Tooltip, { delayDuration: 200 }, () => [
          h(TooltipTrigger, { asChild: true }, () =>
            h(Checkbox, {
              modelValue: row.getIsSelected(),
              'onUpdate:modelValue': (value) => row.toggleSelected(!!value),
              ariaLabel: 'Marcar/Desmarcar registro',
            }),
          ),
          h(TooltipContent, () => h('p', 'Marcar/Desmarcar este registro')),
        ]),
      ),
    enableSorting: false,
    enableHiding: false,
  }),
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
  columnHelper.accessor('name', {
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
  columnHelper.accessor('email', {
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
            'Correo Electrónico',
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
  columnHelper.accessor('disabled_at_human', {
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
            'Desactivado',
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
    cell: (info) => {
      const css = info.getValue() ? 'text-amber-500' : 'text-green-500';

      return h('div', { class: `${css} px-2` }, info.getValue() ?? 'N/A');
    },
  }),
  columnHelper.accessor('deleted_at_human', {
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
            'Eliminado',
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
    cell: (info) => {
      const css = info.getValue() ? 'text-red-500' : 'text-green-500';

      return h('div', { class: `${css} px-2` }, info.getValue() ?? 'N/A');
    },
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
