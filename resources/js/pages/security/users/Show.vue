<script setup lang="ts">
import ActivityLogs from '@/components/activity-logs/ActivityLogs.vue';
import {
  AlertDialog,
  AlertDialogAction,
  AlertDialogCancel,
  AlertDialogContent,
  AlertDialogDescription,
  AlertDialogFooter,
  AlertDialogHeader,
  AlertDialogTitle,
} from '@/components/ui/alert-dialog';
import { Button } from '@/components/ui/button';
import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/card';
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
import { Tabs, TabsContent, TabsList, TabsTrigger } from '@/components/ui/tabs';
import { Tooltip, TooltipContent, TooltipProvider, TooltipTrigger } from '@/components/ui/tooltip';
import { useConfirmAction, useRequestActions } from '@/composables';
import AppLayout from '@/layouts/AppLayout.vue';
import ContentLayout from '@/layouts/ContentLayout.vue';
import { ActivityLog, BreadcrumbItem, Can, PaginatedCollection, Permission, Role, SearchFilter, User } from '@/types';
import { Head } from '@inertiajs/vue3';
import {
  ArrowLeftIcon,
  EllipsisIcon,
  LoaderCircleIcon,
  PencilIcon,
  PlusIcon,
  RotateCcwIcon,
  ToggleLeftIcon,
  ToggleRightIcon,
  Trash2Icon,
  UserIcon,
  XIcon,
} from 'lucide-vue-next';
import { computed, watch } from 'vue';
import Permisos from './partials/Permisos.vue';
import Roles from './partials/Roles.vue';
import UserController from "@/actions/App/Http/Controllers/Security/UserController";

const props = defineProps<{
  can: Can;
  filters: SearchFilter;
  user: User;
  permissions: PaginatedCollection<Permission>;
  permissionsCount: number;
  roles: PaginatedCollection<Role>;
  logs: PaginatedCollection<ActivityLog>;
}>();

const breadcrumbs: BreadcrumbItem[] = [
  {
    title: 'Usuarios',
    href: '/users',
  },
  {
    title: 'Ver',
    href: '',
  },
];

const { action, requestState, requestAction, resourceID } = useRequestActions(UserController);
const { alertOpen, alertAction, alertActionCss, alertTitle, alertDescription } = useConfirmAction();

const userOUs = computed(() => {
  let result = 'Usuario Externo';

  if (!props.user.is_external && props.user.active_organizational_units?.length) {
    result = props.user.active_organizational_units?.map((ou) => ou.name).join(', ');
  } else {
    result = 'SIN ASOCIAR';
  }

  return result;
});

watch(action, () => {
  switch (action.value) {
    case 'destroy':
      alertAction.value = 'Eliminar';
      alertActionCss.value = 'bg-destructive text-destructive-foreground hover:bg-destructive/90';
      alertTitle.value = `¿Eliminar usuario(a) «${props.user.name}»?`;
      alertDescription.value = `«${props.user.name}» perderá el acceso al sistema. Sus datos se conservarán.`;
      alertOpen.value = true;
      break;
    case 'restore':
      alertAction.value = 'Restaurar';
      alertActionCss.value = '';
      alertTitle.value = `¿Restaurar usuario(a) «${props.user.name}»?`;
      alertDescription.value = `«${props.user.name}» recuperará el acceso al sistema. Sus datos se restaurarán.`;
      alertOpen.value = true;
      break;
    case 'force_destroy':
      alertAction.value = 'Eliminar permanentemente';
      alertActionCss.value = 'bg-destructive text-destructive-foreground hover:bg-destructive/90';
      alertTitle.value = `¿Eliminar usuario(a) «${props.user.name}» permanentemente?`;
      alertDescription.value = `Esta acción no podrá revertirse. «${props.user.name}» perderá el acceso al sistema. Sus datos se eliminarán.`;
      alertOpen.value = true;
      break;
    case 'enable':
      alertAction.value = 'Activar';
      alertActionCss.value = '';
      alertTitle.value = `Activar usuario(a) «${props.user.name}»?`;
      alertDescription.value = `«${props.user.name}» recuperará el acceso al sistema. Sus datos se restaurarán.`;
      alertOpen.value = true;
      break;
    case 'disable':
      alertAction.value = 'Desactivar';
      alertActionCss.value = 'bg-amber-500 text-foreground hover:bg-amber-500/90';
      alertTitle.value = `Desactivar usuario(a) «${props.user.name}»?`;
      alertDescription.value = `«${props.user.name}» perderá el acceso al sistema. Sus datos se conservarán.`;
      alertOpen.value = true;
      break;

    default:
      break;
  }
});
</script>

<template>
  <AppLayout :breadcrumbs>
    <Head title="Usuarios: Ver" />
    <ContentLayout :title="user.name" :description="user.email">
      <template #icon>
        <UserIcon />
      </template>
      <section class="grid gap-4 md:grid-cols-4">
        <div class="col-span-3 md:col-span-1">
          <Card class="container">
            <CardHeader>
              <CardTitle>Detalles</CardTitle>
            </CardHeader>
            <CardContent>
              <template v-if="user.person">
                <p class="text-sm text-muted-foreground">{{ user.person?.id_card }}</p>
                <p class="text-sm text-muted-foreground">{{ `${user.person?.names} ${user.person?.surnames}` }}</p>
                <p class="text-sm text-muted-foreground">{{ user.person?.position }}</p>
                <p class="text-sm text-muted-foreground">{{ user.person?.staff_type }}</p>
                <p class="text-sm text-muted-foreground">{{ user.person?.emails?.join(', ') }}</p>
                <p class="text-sm text-muted-foreground">{{ user.person?.phones?.join(', ') }}</p>
                <br />
              </template>
              <p class="text-sm font-medium">Unidad Administrativa</p>
              <p class="text-sm text-muted-foreground">{{ userOUs }}</p>
              <br />
              <p class="text-sm font-medium">Creado</p>
              <p class="text-sm text-muted-foreground">{{ user.created_at_human }}</p>
              <br />
              <p class="text-sm font-medium">Modificado</p>
              <p class="text-sm text-muted-foreground">{{ user.updated_at_human }}</p>
              <br />
              <p class="text-sm font-medium">Estatus</p>
              <p v-if="user.disabled_at" class="text-sm text-amber-500">DESACTIVADO</p>
              <p v-if="user.disabled_at" class="text-sm text-amber-500">{{ user.disabled_at_human }}</p>
              <p v-if="user.deleted_at" class="text-sm text-red-500">ELIMINADO</p>
              <p v-if="user.deleted_at" class="text-sm text-red-500">{{ user.deleted_at_human }}</p>
              <p v-if="!user.disabled_at && !user.deleted_at" class="text-sm text-green-500">ACTIVO</p>
            </CardContent>
          </Card>
        </div>
        <div class="col-span-3">
          <div class="flex items-center justify-between pb-3">
            <TooltipProvider>
              <Tooltip>
                <TooltipTrigger as-child>
                  <Button variant="secondary" @click="requestAction({ operation: 'read_all' })" :disabled="requestState.readAll">
                    <LoaderCircleIcon v-if="requestState.readAll" class="h-4 w-4 animate-spin" />
                    <ArrowLeftIcon v-else class="mr-2 h-4 w-4" />
                    Regresar
                  </Button>
                </TooltipTrigger>
                <TooltipContent> Regresar al listado de usuarios </TooltipContent>
              </Tooltip>
            </TooltipProvider>
            <div class="flex items-center">
              <DropdownMenu>
                <TooltipProvider>
                  <Tooltip>
                    <TooltipTrigger as-child>
                      <DropdownMenuTrigger as-child>
                        <Button variant="outline" :disabled="resourceID !== null">
                          <EllipsisIcon v-if="resourceID === null" />
                          <LoaderCircleIcon v-else class="animate-spin" />
                        </Button>
                      </DropdownMenuTrigger>
                    </TooltipTrigger>
                    <TooltipContent> Editar, exportar y otras acciones </TooltipContent>
                    <DropdownMenuContent>
                      <DropdownMenuLabel>Acciones</DropdownMenuLabel>
                      <DropdownMenuSeparator />
                      <DropdownMenuGroup>
                        <DropdownMenuItem
                          v-if="can.update"
                          class="flex items-center gap-2"
                          @click="requestAction({ operation: 'edit', data: { id: user.id }, options: { preserveState: false } })"
                        >
                          <PencilIcon />
                          <span>Editar</span>
                        </DropdownMenuItem>
                        <DropdownMenuSub v-if="can.enable || can.disable">
                          <DropdownMenuSubTrigger>
                            <span>Activación</span>
                          </DropdownMenuSubTrigger>
                          <DropdownMenuPortal>
                            <DropdownMenuSubContent>
                              <DropdownMenuItem v-if="can.enable" :disabled="user.disabled_at === null" @click="action = 'enable'">
                                <ToggleRightIcon />
                                <span>Activar</span>
                              </DropdownMenuItem>
                              <DropdownMenuItem v-if="can.disable" :disabled="user.disabled_at !== null" @click="action = 'disable'">
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
                              <DropdownMenuItem v-if="can.restore" :disabled="!user.deleted_at" @click="action = 'restore'">
                                <RotateCcwIcon />
                                <span>Restaurar</span>
                              </DropdownMenuItem>
                              <DropdownMenuItem v-if="can.delete" :disabled="user.deleted_at ? true : false" @click="action = 'destroy'">
                                <Trash2Icon />
                                <span>Eliminar</span>
                              </DropdownMenuItem>
                              <DropdownMenuItem v-if="can.f_delete" :disabled="!user.deleted_at" @click="action = 'force_destroy'">
                                <XIcon />
                                <span>Eliminar permanentemente</span>
                              </DropdownMenuItem>
                            </DropdownMenuSubContent>
                          </DropdownMenuPortal>
                        </DropdownMenuSub>
                      </DropdownMenuGroup>
                    </DropdownMenuContent>
                  </Tooltip>
                </TooltipProvider>
              </DropdownMenu>
              <TooltipProvider>
                <Tooltip>
                  <TooltipTrigger as-child>
                    <Button v-if="can.create" class="ml-3" @click="requestAction({ operation: 'create' })" :disabled="requestState.create">
                      <LoaderCircleIcon v-if="requestState.create" class="h-4 w-4 animate-spin" />
                      <PlusIcon v-else class="mr-2 h-4 w-4" />
                      Nuevo
                    </Button>
                  </TooltipTrigger>
                  <TooltipContent>
                    <p>Crear nuevo registro</p>
                  </TooltipContent>
                </Tooltip>
              </TooltipProvider>
            </div>
          </div>
          <Tabs default-value="roles" class="w-auto">
            <TabsList class="grid w-full grid-cols-3">
              <TabsTrigger value="roles">Roles</TabsTrigger>
              <TabsTrigger value="permissions">Permisos</TabsTrigger>
              <TabsTrigger value="logs">Actividad</TabsTrigger>
            </TabsList>
            <TabsContent value="roles">
              <Roles :filters :user-id="user.id" :roles></Roles>
            </TabsContent>
            <TabsContent value="permissions">
              <Permisos :filters :user-id="user.id" :permissions :permissions-count></Permisos>
            </TabsContent>
            <TabsContent value="logs">
              <ActivityLogs :filters :logs :route="UserController.show(user.id)" />
            </TabsContent>
          </Tabs>
        </div>
      </section>

      <AlertDialog v-model:open="alertOpen">
        <AlertDialogContent>
          <AlertDialogHeader>
            <AlertDialogTitle>{{ alertTitle }}</AlertDialogTitle>
            <AlertDialogDescription>{{ alertDescription }}</AlertDialogDescription>
          </AlertDialogHeader>
          <AlertDialogFooter>
            <AlertDialogCancel @click="action = null">Cancelar</AlertDialogCancel>
            <AlertDialogAction :class="alertActionCss" @click="requestAction({ data: { id: user.id }, options: { preserveState: false } })">
              {{ alertAction }}
            </AlertDialogAction>
          </AlertDialogFooter>
        </AlertDialogContent>
      </AlertDialog>
    </ContentLayout>
  </AppLayout>
</template>
