<script setup lang="ts">
import UserController from '@/actions/App/Http/Controllers/Security/UserController';
import ActionAlertDialog from '@/components/ActionAlertDialog.vue';
import ActivityLogs from '@/components/activity-logs/ActivityLogs.vue';
import { Button } from '@/components/ui/button';
import { ButtonGroup } from '@/components/ui/button-group';
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
import { Spinner } from '@/components/ui/spinner';
import { Tabs, TabsContent, TabsList, TabsTrigger } from '@/components/ui/tabs';
import {
  Tooltip,
  TooltipContent,
  TooltipProvider,
  TooltipTrigger,
} from '@/components/ui/tooltip';
import { useActionAlerts, useRequestActions } from '@/composables';
import AppLayout from '@/layouts/AppLayout.vue';
import ContentLayout from '@/layouts/ContentLayout.vue';
import {
  ActivityLog,
  BreadcrumbItem,
  Can,
  PaginatedCollection,
  Permission,
  Role,
  SearchFilter,
  User,
} from '@/types';
import { Head, router } from '@inertiajs/vue3';
import { breakpointsTailwind, useBreakpoints } from '@vueuse/core';
import {
  ArrowLeftIcon,
  EllipsisIcon,
  MailIcon,
  PencilIcon,
  PlusIcon,
  RotateCcwIcon,
  RotateCcwKeyIcon,
  ToggleLeftIcon,
  ToggleRightIcon,
  Trash2Icon,
  UserCheckIcon,
  UserIcon,
  XIcon,
} from 'lucide-vue-next';

import { computed, ref } from 'vue';
import ManualActivationDialog from './partials/ManualActivationDialog.vue';
import Permisos from './partials/Permisos.vue';
import ResetPasswordDialog from './partials/ResetPasswordDialog.vue';
import Roles from './partials/Roles.vue';

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
  },
];

const { action, requestState, requestAction, resourceID, isProcessing } =
  useRequestActions(UserController);

const breakpoints = useBreakpoints(breakpointsTailwind);
const isSmallScreen = breakpoints.smaller('lg');

const resourceName = computed(() => props.user.name || '');

const { alertOpen, alertAction, alertActionCss, alertTitle, alertDescription } =
  useActionAlerts(action, resourceName);

const userOUs = computed(() => {
  let result = 'Usuario Externo';

  if (
    !props.user.is_external &&
    props.user.active_organizational_units?.length
  ) {
    result = props.user.active_organizational_units
      ?.map((ou) => ou.name)
      .join(', ');
  } else {
    result = 'SIN ASOCIAR';
  }

  return result;
});

// Estado para activación manual
const manualActivationDialog = ref(false);
const manualActivationData = ref<{
  password: string;
  user: string;
  email: string;
} | null>(null);

// Función para iniciar activación manual
function handleManualActivation() {
  router.post(
    UserController.manuallyActivate(props.user.id).url,
    {},
    {
      preserveScroll: true,
      preserveState: true,
      onSuccess: (page) => {
        const response = page.props as any;

        if (response.flash?.manualActivation) {
          manualActivationData.value = response.flash.manualActivation;
          manualActivationDialog.value = true;
        }
      },
      onError: (errors) => {
        console.error('Error en activación manual:', errors);
      },
    },
  );
}

// Función para confirmar activación
function confirmManualActivation() {
  manualActivationDialog.value = false;
  router.reload({ only: ['user'] });
}

const userPhones = computed(() => {
  if (
    props.user.person?.phones &&
    Object.keys(props.user.person.phones).length
  ) {
    return Object.entries(props.user.person.phones)
      .map(([key, value]) => `${key}: ${value}`)
      .join(', ');
  }

  return '';
});

const userEmails = computed(() => {
  if (
    props.user.person?.emails &&
    Object.keys(props.user.person.emails).length
  ) {
    return Object.entries(props.user.person.emails)
      .map(([key, value]) => `${key}: ${value}`)
      .join(', ');
  }

  return '';
});

// ¡44 líneas de watch eliminadas! Ahora usa useActionAlerts
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
                <p class="text-sm text-muted-foreground">
                  {{ user.person?.id_card }}
                </p>
                <p class="text-sm text-muted-foreground">
                  {{ `${user.person?.names} ${user.person?.surnames}` }}
                </p>
                <p class="text-sm text-muted-foreground">
                  {{ user.person?.position }}
                </p>
                <p class="text-sm text-muted-foreground">
                  {{ user.person?.staff_type }}
                </p>
                <p class="text-sm text-muted-foreground">
                  {{ userEmails }}
                </p>
                <p class="text-sm text-muted-foreground">
                  {{ userPhones }}
                </p>
                <br />
              </template>
              <p class="text-sm font-medium">Unidad Administrativa</p>
              <p class="text-sm text-muted-foreground">{{ userOUs }}</p>
              <br />
              <p class="text-sm font-medium">Creado</p>
              <p class="text-sm text-muted-foreground">
                {{ user.created_at_human }}
              </p>
              <br />
              <p class="text-sm font-medium">Modificado</p>
              <p class="text-sm text-muted-foreground">
                {{ user.updated_at_human }}
              </p>
              <br />
              <p class="text-sm font-medium">Estatus</p>
              <p v-if="user.disabled_at" class="text-sm text-amber-500">
                DESACTIVADO
              </p>
              <p v-if="user.disabled_at" class="text-sm text-amber-500">
                {{ user.disabled_at_human }}
              </p>
              <p v-if="user.deleted_at" class="text-sm text-red-500">
                ELIMINADO
              </p>
              <p v-if="user.deleted_at" class="text-sm text-red-500">
                {{ user.deleted_at_human }}
              </p>
              <p
                v-if="!user.disabled_at && !user.deleted_at"
                class="text-sm text-green-500"
              >
                ACTIVO
              </p>
            </CardContent>
          </Card>
        </div>
        <div class="col-span-3">
          <div class="flex items-center justify-between pb-3">
            <TooltipProvider>
              <Tooltip>
                <TooltipTrigger as-child>
                  <Button
                    variant="secondary"
                    @click="requestAction({ operation: 'read_all' })"
                    :disabled="requestState.readAll"
                  >
                    <Spinner v-if="requestState.readAll" class="mr-2" />
                    <ArrowLeftIcon v-else class="mr-2 h-4 w-4" />
                    Regresar
                  </Button>
                </TooltipTrigger>
                <TooltipContent>
                  Regresar al listado de usuarios
                </TooltipContent>
              </Tooltip>
            </TooltipProvider>
            <div class="flex items-center">
              <!-- Mobile View: Dropdown Menu -->
              <DropdownMenu v-if="isSmallScreen">
                <TooltipProvider>
                  <Tooltip>
                    <TooltipTrigger as-child>
                      <DropdownMenuTrigger as-child>
                        <Button variant="outline" :disabled="isProcessing">
                          <EllipsisIcon v-if="resourceID === null" />
                          <Spinner v-else />
                        </Button>
                      </DropdownMenuTrigger>
                    </TooltipTrigger>
                    <TooltipContent>
                      Editar, exportar y otras acciones
                    </TooltipContent>
                    <DropdownMenuContent>
                      <DropdownMenuLabel>Acciones</DropdownMenuLabel>
                      <DropdownMenuSeparator />
                      <DropdownMenuGroup>
                        <DropdownMenuItem
                          v-if="can.update"
                          class="flex items-center gap-2"
                          @click="
                            requestAction({
                              operation: 'edit',
                              data: { id: user.id },
                              options: { preserveState: false },
                            })
                          "
                        >
                          <PencilIcon />
                          <span>Editar</span>
                        </DropdownMenuItem>
                        <DropdownMenuItem
                          v-if="can.reset_password && user.is_active"
                          class="flex items-center gap-2"
                          @click="
                            () =>
                              router.visit(
                                UserController.resetPassword(user.id),
                                {
                                  preserveScroll: true,
                                  preserveState: true,
                                },
                              )
                          "
                        >
                          <RotateCcwKeyIcon />
                          <span>Restablecer Contraseña</span>
                        </DropdownMenuItem>
                        <DropdownMenuItem
                          v-if="can.reset_password && !user.is_active"
                          class="flex items-center gap-2 text-blue-600 transition-colors focus:bg-accent focus:text-accent-foreground"
                          @click="
                            () =>
                              router.visit(
                                UserController.resendActivation(user.id),
                                {
                                  method: 'post',
                                  preserveScroll: true,
                                  preserveState: false,
                                },
                              )
                          "
                        >
                          <MailIcon class="text-blue-600" />
                          <span>Reenviar Activación</span>
                        </DropdownMenuItem>
                        <DropdownMenuItem
                          v-if="can.enable && !user.is_active"
                          class="flex items-center gap-2 text-orange-600 transition-colors focus:bg-accent focus:text-accent-foreground"
                          :disabled="isProcessing"
                          @click="handleManualActivation"
                        >
                          <UserCheckIcon class="text-orange-600" />
                          <span>Activar Manualmente</span>
                        </DropdownMenuItem>
                        <DropdownMenuSub v-if="can.enable || can.disable">
                          <DropdownMenuSubTrigger>
                            <span>Activación</span>
                          </DropdownMenuSubTrigger>
                          <DropdownMenuPortal>
                            <DropdownMenuSubContent>
                              <DropdownMenuItem
                                v-if="can.enable"
                                class="text-green-600 transition-colors focus:bg-accent focus:text-accent-foreground"
                                :disabled="user.disabled_at === null"
                                @click="action = 'enable'"
                              >
                                <ToggleRightIcon class="text-green-600" />
                                <span>Activar</span>
                              </DropdownMenuItem>
                              <DropdownMenuItem
                                v-if="can.disable"
                                class="text-amber-600 transition-colors focus:bg-accent focus:text-accent-foreground"
                                :disabled="user.disabled_at !== null"
                                @click="action = 'disable'"
                              >
                                <ToggleLeftIcon class="text-amber-600" />
                                <span>Desactivar</span>
                              </DropdownMenuItem>
                            </DropdownMenuSubContent>
                          </DropdownMenuPortal>
                        </DropdownMenuSub>

                        <DropdownMenuSub
                          v-if="can.restore || can.delete || can.delete_force"
                        >
                          <DropdownMenuSubTrigger>
                            Eliminación
                          </DropdownMenuSubTrigger>
                          <DropdownMenuPortal>
                            <DropdownMenuSubContent>
                              <DropdownMenuItem
                                v-if="can.restore"
                                :disabled="!user.deleted_at"
                                @click="action = 'restore'"
                              >
                                <RotateCcwIcon />
                                <span>Restaurar</span>
                              </DropdownMenuItem>
                              <DropdownMenuItem
                                v-if="can.delete"
                                class="text-red-600 transition-colors focus:bg-accent focus:text-accent-foreground"
                                :disabled="user.deleted_at ? true : false"
                                @click="action = 'destroy'"
                              >
                                <Trash2Icon class="text-red-600" />
                                <span>Eliminar</span>
                              </DropdownMenuItem>
                              <DropdownMenuItem
                                v-if="can.delete_force"
                                class="text-red-600 transition-colors focus:bg-accent focus:text-accent-foreground"
                                :disabled="!user.deleted_at"
                                @click="action = 'force_destroy'"
                              >
                                <XIcon class="text-red-600" />
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

              <!-- Desktop View: Button Group -->
              <ButtonGroup v-else>
                <Button
                  v-if="can.update"
                  variant="outline"
                  :disabled="isProcessing"
                  @click="
                    requestAction({
                      operation: 'edit',
                      data: { id: user.id },
                      options: { preserveState: false },
                    })
                  "
                >
                  <Spinner v-if="resourceID !== null" class="mr-2" />
                  <PencilIcon v-else class="mr-2" />
                  <span>Editar</span>
                </Button>
                <Button
                  v-if="can.reset_password && user.is_active"
                  variant="outline"
                  :disabled="isProcessing"
                  @click="
                    () =>
                      router.visit(UserController.resetPassword(user.id), {
                        preserveScroll: true,
                        preserveState: true,
                      })
                  "
                >
                  <Spinner v-if="resourceID !== null" class="mr-2" />
                  <RotateCcwKeyIcon v-else class="mr-2" />
                  <span>Restablecer Contraseña</span>
                </Button>
                <Button
                  v-if="can.reset_password && !user.is_active"
                  variant="outline"
                  class="text-blue-600 transition-colors focus:bg-accent focus:text-accent-foreground"
                  :disabled="isProcessing"
                  @click="
                    () =>
                      router.visit(UserController.resendActivation(user.id), {
                        method: 'post',
                        preserveScroll: true,
                        preserveState: false,
                      })
                  "
                >
                  <Spinner v-if="resourceID !== null" class="mr-2" />
                  <MailIcon v-else class="mr-2 text-blue-600" />
                  <span>Reenviar Activación</span>
                </Button>
                <Button
                  v-if="can.enable && !user.is_active"
                  variant="outline"
                  class="text-orange-600 transition-colors focus:bg-accent focus:text-accent-foreground"
                  :disabled="isProcessing"
                  @click="handleManualActivation"
                >
                  <Spinner v-if="isProcessing" class="mr-2" />
                  <UserCheckIcon v-else class="mr-2 text-orange-600" />
                  <span>Activar Manualmente</span>
                </Button>
                <Button
                  v-if="can.enable && user.disabled_at"
                  variant="outline"
                  class="text-green-600 transition-colors focus:bg-accent focus:text-accent-foreground"
                  :disabled="isProcessing"
                  @click="action = 'enable'"
                >
                  <Spinner v-if="resourceID !== null" class="mr-2" />
                  <ToggleRightIcon v-else class="mr-2 text-green-600" />
                  <span>Activar</span>
                </Button>
                <Button
                  v-if="can.disable && !user.disabled_at"
                  variant="outline"
                  class="text-amber-600 transition-colors focus:bg-accent focus:text-accent-foreground"
                  :disabled="isProcessing"
                  @click="action = 'disable'"
                >
                  <Spinner v-if="resourceID !== null" class="mr-2" />
                  <ToggleLeftIcon v-else class="mr-2 text-amber-600" />
                  <span>Desactivar</span>
                </Button>
                <Button
                  v-if="can.restore && user.deleted_at"
                  variant="outline"
                  :disabled="isProcessing"
                  @click="action = 'restore'"
                >
                  <Spinner v-if="resourceID !== null" class="mr-2" />
                  <RotateCcwIcon v-else class="mr-2" />
                  <span>Restaurar</span>
                </Button>
                <Button
                  v-if="can.delete && !user.deleted_at"
                  variant="outline"
                  class="text-red-600 transition-colors focus:bg-accent focus:text-accent-foreground"
                  :disabled="isProcessing"
                  @click="action = 'destroy'"
                >
                  <Spinner v-if="resourceID !== null" class="mr-2" />
                  <Trash2Icon v-else class="mr-2 text-red-600" />
                  <span>Eliminar</span>
                </Button>
                <Button
                  v-if="can.delete_force && user.deleted_at"
                  variant="outline"
                  class="text-red-600 transition-colors focus:bg-accent focus:text-accent-foreground"
                  :disabled="isProcessing"
                  @click="action = 'force_destroy'"
                >
                  <Spinner v-if="resourceID !== null" class="mr-2" />
                  <XIcon v-else class="mr-2 text-red-600" />
                  <span>Eliminar permanentemente</span>
                </Button>
              </ButtonGroup>
              <TooltipProvider>
                <Tooltip>
                  <TooltipTrigger as-child>
                    <Button
                      v-if="can.create"
                      class="ml-3"
                      @click="requestAction({ operation: 'create' })"
                      :disabled="requestState.create"
                    >
                      <Spinner v-if="requestState.create" class="mr-2" />
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
              <Permisos
                :filters
                :user-id="user.id"
                :permissions
                :permissions-count
              ></Permisos>
            </TabsContent>
            <TabsContent value="logs">
              <ActivityLogs
                :filters
                :logs
                :route="UserController.show(user.id)"
              />
            </TabsContent>
          </Tabs>
        </div>
      </section>

      <ActionAlertDialog
        :open="alertOpen"
        :title="alertTitle"
        :description="alertDescription"
        :action-text="alertAction"
        :action-css="alertActionCss"
        :is-processing="isProcessing"
        @cancel="action = null"
        @confirm="
          requestAction({
            data: { id: user.id },
            options: { preserveState: false },
          })
        "
      />

      <ResetPasswordDialog />

      <ManualActivationDialog
        v-if="manualActivationData"
        :open="manualActivationDialog"
        :user-name="manualActivationData.user"
        :user-email="manualActivationData.email"
        :password="manualActivationData.password"
        @confirm="confirmManualActivation"
      />
    </ContentLayout>
  </AppLayout>
</template>
