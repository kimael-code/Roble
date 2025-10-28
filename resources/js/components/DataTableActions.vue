<script setup lang="ts">
import { Button } from '@/components/ui/button';
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
} from '@/components/ui/dropdown-menu';
import { Can } from '@/types';
import {
  EllipsisIcon,
  EyeIcon,
  FileDownIcon,
  LoaderCircleIcon,
  PencilIcon,
  RotateCcwIcon,
  ToggleLeftIcon,
  ToggleRightIcon,
  Trash2Icon,
  XIcon,
} from 'lucide-vue-next';
import { ref } from 'vue';
import { Tooltip, TooltipContent, TooltipTrigger } from './ui/tooltip';

defineProps<{
  row: {
    [index: string]: any;
  };
  can: Can;
  loading?: boolean;
}>();

defineEmits<{
  read: [row: object];
  update: [row: object];
  destroy: [row: object];
  forceDestroy: [row: object];
  export: [row: object];
  enable: [row: object];
  disable: [row: object];
  restore: [row: object];
}>();

const menuIsOpen = ref(false);
</script>

<template>
  <DropdownMenu v-model:open="menuIsOpen">
    <DropdownMenuTrigger as-child>
      <Tooltip>
        <TooltipTrigger as-child>
          <Button variant="secondary" class="h-6 w-6 p-0 shadow-md" :disabled="loading" @click="menuIsOpen = true">
            <LoaderCircleIcon v-if="loading" class="animate-spin" />
            <EllipsisIcon v-else />
          </Button>
        </TooltipTrigger>
        <TooltipContent>
          <p>Menú de acciones</p>
        </TooltipContent>
      </Tooltip>
    </DropdownMenuTrigger>
    <DropdownMenuContent align="end">
      <DropdownMenuLabel>Acciones</DropdownMenuLabel>
      <DropdownMenuSeparator />
      <DropdownMenuGroup>
        <DropdownMenuItem v-if="can.read" @click="$emit('read', row)">
          <EyeIcon />
          <span>Ver</span>
        </DropdownMenuItem>
        <DropdownMenuItem v-if="can.update" @click="$emit('update', row)">
          <PencilIcon />
          <span>Editar</span>
        </DropdownMenuItem>
        <DropdownMenuItem v-if="can.export" @click="$emit('export', row)">
          <FileDownIcon />
          <span>Exportar</span>
        </DropdownMenuItem>
      </DropdownMenuGroup>

      <DropdownMenuSeparator v-if="can.enable || can.disable || can.restore || can.delete || can.f_delete" />
      <DropdownMenuGroup>
        <DropdownMenuSub v-if="can.enable || can.disable">
          <DropdownMenuSubTrigger>
            <span>Activación</span>
          </DropdownMenuSubTrigger>
          <DropdownMenuPortal>
            <DropdownMenuSubContent>
              <DropdownMenuItem v-if="can.enable" :disabled="row?.disabled_at === null" @click="$emit('enable', row)">
                <ToggleRightIcon />
                <span>Activar</span>
              </DropdownMenuItem>
              <DropdownMenuItem v-if="can.disable" :disabled="row?.disabled_at !== null" @click="$emit('disable', row)">
                <ToggleLeftIcon />
                <span>Desactivar</span>
              </DropdownMenuItem>
            </DropdownMenuSubContent>
          </DropdownMenuPortal>
        </DropdownMenuSub>
        <DropdownMenuSub v-if="can.restore || can.delete || can.f_delete">
          <DropdownMenuSubTrigger> Eliminación </DropdownMenuSubTrigger>
          <DropdownMenuPortal>
            <DropdownMenuSubContent>
              <DropdownMenuItem v-if="can.restore" :disabled="!row?.deleted_at" @click="$emit('restore', row)">
                <RotateCcwIcon />
                <span>Restaurar</span>
              </DropdownMenuItem>
              <DropdownMenuItem
                v-if="can.delete"
                class="text-red-600 transition-colors focus:bg-accent focus:text-accent-foreground"
                :disabled="row?.deleted_at ? true : false"
                @click="$emit('destroy', row)"
              >
                <Trash2Icon class="text-red-600" />
                <span>Eliminar</span>
              </DropdownMenuItem>
              <DropdownMenuItem
                v-if="can.f_delete"
                class="text-red-600 transition-colors focus:bg-accent focus:text-accent-foreground"
                :disabled="!row?.deleted_at"
                @click="$emit('forceDestroy', row)"
              >
                <XIcon class="text-red-600" />
                <span>Eliminar permanentemente</span>
              </DropdownMenuItem>
            </DropdownMenuSubContent>
          </DropdownMenuPortal>
        </DropdownMenuSub>
      </DropdownMenuGroup>
    </DropdownMenuContent>
  </DropdownMenu>
</template>
