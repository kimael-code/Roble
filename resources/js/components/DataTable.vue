<script setup lang="ts">
import { Can, PaginatedCollection } from '@/types';
import { router, useForm } from '@inertiajs/vue3';
import { ColumnDef, FlexRender, Table as TanstackTable } from '@tanstack/vue-table';
import { watchDebounced } from '@vueuse/core';
import {
  BinocularsIcon,
  DeleteIcon,
  EllipsisIcon,
  EraserIcon,
  FileDownIcon,
  LoaderCircleIcon,
  PlusIcon,
  ToggleLeftIcon,
  ToggleRightIcon,
  Trash2Icon,
} from 'lucide-vue-next';
import { TooltipPortal } from 'reka-ui';
import { ref } from 'vue';
import { Badge } from './ui/badge';
import { Button } from './ui/button';
import {
  DropdownMenu,
  DropdownMenuContent,
  DropdownMenuGroup,
  DropdownMenuItem,
  DropdownMenuLabel,
  DropdownMenuPortal,
  DropdownMenuSeparator,
  DropdownMenuSub,
  DropdownMenuSubContent,
  DropdownMenuSubTrigger,
  DropdownMenuTrigger,
} from './ui/dropdown-menu';
import { Input } from './ui/input';
import { Pagination, PaginationContent, PaginationEllipsis, PaginationItem, PaginationNext, PaginationPrevious } from './ui/pagination';
import { Select, SelectContent, SelectItem, SelectTrigger, SelectValue } from './ui/select';
import { Table, TableBody, TableCell, TableHead, TableHeader, TableRow } from './ui/table';
import { Tooltip, TooltipContent, TooltipProvider, TooltipTrigger } from './ui/tooltip';
import { RouteDefinition } from '@/wayfinder';

interface Props {
  can?: Can;
  columns: ColumnDef<any, any>[];
  data: PaginatedCollection<any>;
  filters: { [key: string]: any };
  searchOnly: Array<string>;
  searchRoute: RouteDefinition<"get">;
  searchRouteData?: { [key: string]: any };
  table: TanstackTable<any>;
  hasNewButton?: boolean;
  hasBatchActionsButton?: boolean;
  isLoadingNew?: boolean;
  isLoadingDropdown?: boolean;
  isAdvancedSearch?: boolean;
}
const props = withDefaults(defineProps<Props>(), {
  hasNewButton: true,
  hasBatchActionsButton: true,
  isLoadingNew: false,
  isLoadingDropdown: false,
  isAdvancedSearch: false,
});

const emit = defineEmits([
  'batchEnable',
  'batchDisable',
  'batchDestroy',
  'export',
  'exportRow',
  'search',
  'new',
  'read',
  'update',
  'destroy',
  'forceDestroy',
  'restore',
  'enable',
  'disable',
  'advancedSearch',
]);

const menuIsOpen = ref(false);

const form = useForm({
  search: props.filters?.search || undefined,
  ...props.searchRouteData,
});
form.defaults({
  search: undefined,
});

watchDebounced(
  () => form.search,
  (s: string | undefined) => {
    // @ts-expect-error La creación de una instancia de tipo es excesivamente profunda y posiblemente infinita.
    if (s === '') form.reset('search');

    router.reload({
      data: form.data(),
      only: props.searchOnly,
      onSuccess: () => emit('search', s),
    });
  },
  { debounce: 500, maxWait: 1000 },
);

function handlePerPage(perPageValue: number) {
  if (!perPageValue) {
    return;
  }

  const perPage = perPageValue ?? 10;

  props.table.setPageSize(perPage);

  router.reload({
    data: { per_page: perPage },
    only: props.searchOnly,
  });
}
</script>

<template>
  <div class="w-full">
    <div class="flex items-center justify-start px-2 py-4">
      <div class="flex max-w-max flex-1 items-center space-x-2">
        <div class="relative w-full max-w-sm items-center">
          <Input id="search" type="text" class="flex w-full pr-10" placeholder="Buscar rápido..." v-model:model-value="form.search" />
          <span
            v-if="form.search"
            class="absolute inset-y-0 right-0 flex items-center justify-center px-2 opacity-100 transition-opacity duration-750 starting:opacity-0"
            @click="form.reset()"
          >
            <DeleteIcon class="size-6 text-muted-foreground" />
          </span>
        </div>
        <Tooltip v-if="isAdvancedSearch || table.getState().sorting.length">
          <TooltipTrigger as-child>
            <Button
              variant="ghost"
              class="inline-flex items-center justify-center gap-2"
              @click="router.visit(searchRoute, { only: searchOnly, preserveScroll: false, preserveState: false })"
            >
              Reiniciar
              <EraserIcon class="h-4 w-4" />
            </Button>
          </TooltipTrigger>
          <TooltipContent>
            <p>Remover todos los filtros/ordenamientos</p>
          </TooltipContent>
        </Tooltip>
        <Badge v-if="isAdvancedSearch"> Filtros de búsqueda avanzada aplicados </Badge>
      </div>
      <DropdownMenu v-if="can && (can.delete || can.export)" v-model:open="menuIsOpen">
        <DropdownMenuTrigger as-child>
          <Tooltip>
            <TooltipTrigger as-child>
              <Button variant="outline" class="ml-auto" @click="menuIsOpen = true">
                <LoaderCircleIcon v-if="isLoadingDropdown" class="animate-spin" />
                <EllipsisIcon v-else />
              </Button>
            </TooltipTrigger>
            <TooltipContent>
              <p>Búsqueda avanzada, exportación de datos y otras acciones por lote</p>
            </TooltipContent>
          </Tooltip>
        </DropdownMenuTrigger>

        <DropdownMenuContent align="end">
          <DropdownMenuLabel>Otras acciones</DropdownMenuLabel>
          <DropdownMenuSeparator />
          <DropdownMenuGroup>
            <DropdownMenuItem @click="$emit('advancedSearch')">
              <BinocularsIcon />
              <span>Buscar avanzado</span>
            </DropdownMenuItem>
            <DropdownMenuSub v-if="can.export">
              <DropdownMenuSubTrigger inset>
                <FileDownIcon class="mr-2 h-4 w-4" />
                Exportar a
              </DropdownMenuSubTrigger>
              <DropdownMenuPortal>
                <DropdownMenuSubContent>
                  <DropdownMenuItem @click="$emit('export', 'pdf')">
                    <span>PDF</span>
                  </DropdownMenuItem>
                  <DropdownMenuItem @click="$emit('export', 'excel')" disabled>
                    <span>Excel</span>
                  </DropdownMenuItem>
                  <DropdownMenuItem @click="$emit('export', 'json')" disabled>
                    <span>JSON</span>
                  </DropdownMenuItem>
                </DropdownMenuSubContent>
              </DropdownMenuPortal>
            </DropdownMenuSub>
          </DropdownMenuGroup>
          <DropdownMenuSeparator v-if="can.delete || can.enable || can.disable" />
          <DropdownMenuGroup>
            <DropdownMenuItem
              v-if="can.enable"
              class="text-green-600 transition-colors focus:bg-accent focus:text-accent-foreground"
              :disabled="table.getFilteredSelectedRowModel().rows.length < 1"
              @click="$emit('batchEnable')"
            >
              <ToggleRightIcon class="text-green-600" />
              <span>Activar/Restaurar selección</span>
            </DropdownMenuItem>
            <DropdownMenuItem
              v-if="can.disable"
              class="text-amber-600 transition-colors focus:bg-accent focus:text-accent-foreground"
              :disabled="table.getFilteredSelectedRowModel().rows.length < 1"
              @click="$emit('batchDisable')"
            >
              <ToggleLeftIcon class="text-amber-600" />
              <span>Desactivar selección</span>
            </DropdownMenuItem>
            <DropdownMenuItem
              v-if="can.delete"
              class="text-red-600 transition-colors focus:bg-accent focus:text-accent-foreground"
              :disabled="table.getFilteredSelectedRowModel().rows.length < 1"
              @click="$emit('batchDestroy')"
            >
              <Trash2Icon class="text-red-600" />
              <span>Eliminar selección</span>
            </DropdownMenuItem>
          </DropdownMenuGroup>
        </DropdownMenuContent>
      </DropdownMenu>
      <Tooltip>
        <TooltipTrigger as-child>
          <Button v-if="can && can.create" class="ml-3" :disabled="isLoadingNew" @click="$emit('new')">
            <LoaderCircleIcon v-if="isLoadingNew" class="h-4 w-4 animate-spin" />
            <PlusIcon v-else class="mr-2 h-4 w-4" />
            Nuevo
          </Button>
        </TooltipTrigger>
        <TooltipPortal>
          <TooltipContent>
            <p>Crear nuevo registro</p>
          </TooltipContent>
        </TooltipPortal>
      </Tooltip>
    </div>
    <div class="rounded-md border">
      <Table>
        <TableHeader>
          <TableRow v-for="headerGroup in table.getHeaderGroups()" :key="headerGroup.id">
            <!-- @vue-expect-error extender columDef para añadir la propiedad class al objeto meta -->
            <TableHead v-for="header in headerGroup.headers" :key="header.id" :class="header.column.columnDef.meta?.class">
              <FlexRender v-if="!header.isPlaceholder" :render="header.column.columnDef.header" :props="header.getContext()" />
            </TableHead>
          </TableRow>
        </TableHeader>
        <TableBody>
          <template v-if="table.getRowModel().rows?.length">
            <template v-for="row in table.getRowModel().rows" :key="row.id">
              <TableRow :data-state="row.getIsSelected() && 'selected'">
                <!-- @vue-expect-error extender columDef para añadir la propiedad class al objeto meta -->
                <TableCell v-for="cell in row.getVisibleCells()" :key="cell.id" :class="cell.column.columnDef.meta?.class">
                  <FlexRender
                    :render="cell.column.columnDef.cell"
                    :props="cell.getContext()"
                    @read="(row: any) => $emit('read', row)"
                    @update="(row: any) => $emit('update', row)"
                    @destroy="(row: any) => $emit('destroy', row)"
                    @force-destroy="(row: any) => $emit('forceDestroy', row)"
                    @restore="(row: any) => $emit('restore', row)"
                    @export="(row: any) => $emit('exportRow', row)"
                    @enable="(row: any) => $emit('enable', row)"
                    @disable="(row: any) => $emit('disable', row)"
                  />
                </TableCell>
              </TableRow>
              <TableRow v-if="row.getIsExpanded()">
                <TableCell :colspan="row.getAllCells().length">
                  {{ JSON.stringify(row.original) }}
                </TableCell>
              </TableRow>
            </template>
          </template>

          <TableRow v-else>
            <TableCell :colspan="columns.length" class="h-24 text-center"> No hay registros. </TableCell>
          </TableRow>
        </TableBody>
      </Table>
    </div>

    <div class="flex items-center justify-end space-x-2 py-4">
      <div v-if="table.getFilteredSelectedRowModel().rows.length" class="flex-1 text-sm text-muted-foreground">
        {{ table.getFilteredSelectedRowModel().rows.length }} de {{ data.meta.total }}
        {{ table.getFilteredRowModel().rows.length > 1 ? 'registros seleccionados' : 'registro seleccionado' }}.
      </div>
      <div v-else class="flex-1 text-sm text-muted-foreground">
        {{ data.meta.from }} a {{ data.meta.to }} de {{ data.meta.total }} {{ data.meta.total > 1 ? 'registros' : 'registro' }}
      </div>
      <div class="flex items-center space-x-2">
        <p class="text-xs font-medium">Registros por página</p>
        <!-- @vue-expect-error -->
        <Select :model-value="table.getState().pagination.pageSize" @update:model-value="handlePerPage">
          <SelectTrigger class="h-8 w-[70px]">
            <SelectValue :placeholder="`${table.getState().pagination.pageSize}`" />
          </SelectTrigger>
          <SelectContent side="top">
            <SelectItem v-for="pageSize in [10, 20, 30, 40, 50]" :key="pageSize" :value="pageSize">
              {{ pageSize }}
            </SelectItem>
          </SelectContent>
        </Select>
      </div>
      <div class="space-x-2">
        <Pagination :page="data.meta.current_page" :items-per-page="data.meta.per_page" :total="data.meta.total">
          <PaginationContent>
            <TooltipProvider>
              <Tooltip>
                <TooltipTrigger as-child>
                  <PaginationPrevious @click="router.visit(data.links.prev, { preserveScroll: true, preserveState: true })" />
                </TooltipTrigger>
                <TooltipContent>
                  <p>Página anterior</p>
                </TooltipContent>
              </Tooltip>

              <template v-for="(item, i) in data.meta.links">
                <Tooltip v-if="item.url" :key="i">
                  <TooltipTrigger as-child>
                    <PaginationItem
                      :value="parseInt(item.label)"
                      :is-active="item.active"
                      @click="router.visit(item.url, { preserveScroll: true, preserveState: true })"
                    />
                  </TooltipTrigger>
                  <TooltipContent>
                    <p>{{ `Página ${item.label}` }}</p>
                  </TooltipContent>
                </Tooltip>
                <PaginationEllipsis v-else :key="item.label" :index="i" />
              </template>

              <Tooltip>
                <TooltipTrigger as-child>
                  <PaginationNext @click="router.visit(data.links.next, { preserveScroll: true, preserveState: true })" />
                </TooltipTrigger>
                <TooltipContent>
                  <p>Página siguiente</p>
                </TooltipContent>
              </Tooltip>
            </TooltipProvider>
          </PaginationContent>
        </Pagination>
      </div>
    </div>
  </div>
</template>

<style lang="css" scoped>
.sticky-left {
  position: sticky;
  left: 0;
  /*z-index: 10;*/
}
.sticky-right {
  position: sticky;
  right: 0;
  /*z-index: 10;*/
}
</style>
