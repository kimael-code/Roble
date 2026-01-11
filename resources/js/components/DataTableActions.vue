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
  MailIcon,
  PencilIcon,
  RotateCcwIcon,
  RotateCcwKey,
  SendIcon,
  ToggleLeftIcon,
  ToggleRightIcon,
  Trash2Icon,
  UserCheckIcon,
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
  resetPassword: [row: object];
  resendActivation: [row: object];
  manuallyActivate: [row: object];
  send: [row: object];
}>();

const menuIsOpen = ref(false);
</script>

<template>
  <DropdownMenu v-model:open="menuIsOpen">
    <DropdownMenuTrigger as-child>
      <Tooltip>
        <TooltipTrigger as-child>
          <Button
            variant="secondary"
            class="h-6 w-6 p-0 shadow-md"
            :disabled="loading"
            @click="menuIsOpen = true"
          >
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
      <DropdownMenuGroup>
        <DropdownMenuItem v-if="can.read" @click="$emit('read', row)">
          <EyeIcon />
          <span>Ver</span>
        </DropdownMenuItem>
        <DropdownMenuItem v-if="can.update" @click="$emit('update', row)">
          <PencilIcon />
          <span>Editar</span>
        </DropdownMenuItem>
        <DropdownMenuItem v-if="can.send" @click="$emit('send', row)">
          <SendIcon />
          <span>Enviar</span>
        </DropdownMenuItem>
        <DropdownMenuItem
          v-if="can.reset_password && row?.is_active"
          @click="$emit('resetPassword', row)"
        >
          <RotateCcwKey />
          <span>Restablecer Contraseña</span>
        </DropdownMenuItem>
        <DropdownMenuItem
          v-if="can.export_record"
          @click="$emit('export', row)"
        >
          <FileDownIcon />
          <span>Exportar</span>
        </DropdownMenuItem>
      </DropdownMenuGroup>

      <DropdownMenuSeparator
        v-if="
          can.enable ||
          can.disable ||
          can.restore ||
          can.delete ||
          can.delete_force
        "
      />
      <DropdownMenuGroup>
        <DropdownMenuSub v-if="can.enable || can.disable || can.reset_password">
          <DropdownMenuSubTrigger>
            <span>Activación</span>
          </DropdownMenuSubTrigger>
          <DropdownMenuPortal>
            <DropdownMenuSubContent>
              <DropdownMenuItem
                v-if="can.enable"
                class="text-green-600 transition-colors focus:bg-accent focus:text-accent-foreground"
                :disabled="row?.disabled_at === null"
                @click="$emit('enable', row)"
              >
                <ToggleRightIcon class="text-green-600" />
                <span>Activar</span>
              </DropdownMenuItem>
              <DropdownMenuItem
                v-if="can.disable"
                class="text-amber-600 transition-colors focus:bg-accent focus:text-accent-foreground"
                :disabled="row?.disabled_at !== null"
                @click="$emit('disable', row)"
              >
                <ToggleLeftIcon class="text-amber-600" />
                <span>Desactivar</span>
              </DropdownMenuItem>
              <DropdownMenuItem
                v-if="can.reset_password && !row?.is_active"
                class="text-blue-600 transition-colors focus:bg-accent focus:text-accent-foreground"
                @click="$emit('resendActivation', row)"
              >
                <MailIcon class="text-blue-600" />
                <span>Reenviar Activación</span>
              </DropdownMenuItem>
              <DropdownMenuItem
                v-if="can.enable && !row?.is_active"
                class="text-orange-600 transition-colors focus:bg-accent focus:text-accent-foreground"
                @click="$emit('manuallyActivate', row)"
              >
                <UserCheckIcon class="text-orange-600" />
                <span>Activar Manualmente</span>
              </DropdownMenuItem>
            </DropdownMenuSubContent>
          </DropdownMenuPortal>
        </DropdownMenuSub>
        <DropdownMenuSub v-if="can.restore || can.delete || can.delete_force">
          <DropdownMenuSubTrigger> Eliminación </DropdownMenuSubTrigger>
          <DropdownMenuPortal>
            <DropdownMenuSubContent>
              <DropdownMenuItem
                v-if="can.restore"
                :disabled="!row?.deleted_at"
                @click="$emit('restore', row)"
              >
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
                v-if="can.delete_force"
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
