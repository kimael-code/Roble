<script setup lang="ts">
import routes from '@/actions/App/Http/Controllers/Organization/OrganizationController';
import ActionAlertDialog from '@/components/ActionAlertDialog.vue';
import ActivityLogs from '@/components/activity-logs/ActivityLogs.vue';
import { AspectRatio } from '@/components/ui/aspect-ratio';
import { Button } from '@/components/ui/button';
import { ButtonGroup } from '@/components/ui/button-group';
import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/card';
import {
  DropdownMenu,
  DropdownMenuContent,
  DropdownMenuGroup,
  DropdownMenuItem,
  DropdownMenuLabel,
  DropdownMenuSeparator,
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
  Organization,
  OrganizationalUnit,
  PaginatedCollection,
  SearchFilter,
} from '@/types';
import { Head } from '@inertiajs/vue3';
import { breakpointsTailwind, useBreakpoints } from '@vueuse/core';
import {
  ArrowLeftIcon,
  Building,
  EllipsisIcon,
  PencilIcon,
  PlusIcon,
  Trash2Icon,
} from 'lucide-vue-next';
import { computed } from 'vue';
import OrganizationalUnits from './partials/OrganizationalUnits.vue';

const props = defineProps<{
  can: Can;
  filters: SearchFilter;
  organization: Organization;
  ous: PaginatedCollection<OrganizationalUnit>;
  logs: PaginatedCollection<ActivityLog>;
}>();

const breadcrumbs: BreadcrumbItem[] = [
  {
    title: 'Entes',
    href: '/organizations',
  },
  {
    title: 'Ver',
    href: '',
  },
];

const { action, requestState, requestAction, resourceID, isProcessing } =
  useRequestActions(routes);

const breakpoints = useBreakpoints(breakpointsTailwind);
const isSmallScreen = breakpoints.smaller('lg');

const resourceName = computed(() => props.organization.name || '');

const { alertOpen, alertAction, alertActionCss, alertTitle, alertDescription } =
  useActionAlerts(action, resourceName);

// ¡14 líneas de watch eliminadas! Ahora usa useActionAlerts
</script>

<template>
  <AppLayout :breadcrumbs>
    <Head title="Entes: Ver" />
    <ContentLayout :title="organization.name" :description="organization.rif">
      <template #icon>
        <Building />
      </template>
      <section class="grid gap-4 md:grid-cols-4">
        <div class="col-span-3 md:col-span-1">
          <Card class="container">
            <CardHeader>
              <CardTitle>Detalles</CardTitle>
            </CardHeader>
            <CardContent>
              <div class="w-full overflow-hidden rounded-xs shadow-sm">
                <AspectRatio :ratio="31 / 8">
                  <img
                    class="h-full w-full object-cover"
                    :src="organization.logo_url"
                    alt="Logo empresarial"
                  />
                </AspectRatio>
              </div>
              <br />
              <p class="text-sm font-medium">Acrónimo</p>
              <p class="text-sm text-muted-foreground">
                {{ organization.acronym }}
              </p>
              <br />
              <p class="text-sm font-medium">Dirección</p>
              <p class="text-sm text-muted-foreground">
                {{ organization.address }}
              </p>
              <br />
              <p class="text- text-sm font-medium">Creado</p>
              <p class="text-sm text-muted-foreground">
                {{ organization.created_at_human }}
              </p>
              <br />
              <p class="text-sm font-medium">Modificado</p>
              <p class="text-sm text-muted-foreground">
                {{ organization.updated_at_human }}
              </p>
              <br />
              <p class="text- text-sm font-medium">Estatus</p>
              <p
                class="text-sm"
                :class="{
                  'text-green-500': !organization.disabled_at,
                  'text-red-500': organization.disabled_at,
                }"
              >
                {{ organization.status }}
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
                <TooltipContent> Regresar al listado de entes </TooltipContent>
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
                              data: { id: organization.id },
                              options: { preserveState: false },
                            })
                          "
                        >
                          <PencilIcon />
                          <span>Editar</span>
                        </DropdownMenuItem>
                        <DropdownMenuItem
                          v-if="can.delete"
                          class="text-red-600 transition-colors focus:bg-accent focus:text-accent-foreground"
                          @click="action = 'destroy'"
                        >
                          <Trash2Icon class="text-red-600" />
                          <span>Eliminar</span>
                        </DropdownMenuItem>
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
                      data: { id: organization.id },
                      options: { preserveState: false },
                    })
                  "
                >
                  <Spinner v-if="resourceID !== null" class="mr-2" />
                  <PencilIcon v-else class="mr-2" />
                  <span>Editar</span>
                </Button>
                <Button
                  v-if="can.delete"
                  variant="outline"
                  :disabled="isProcessing"
                  class="text-red-600 transition-colors focus:bg-accent focus:text-accent-foreground"
                  @click="action = 'destroy'"
                >
                  <Spinner v-if="resourceID !== null" class="mr-2" />
                  <Trash2Icon v-else class="mr-2 text-red-600" />
                  <span>Eliminar</span>
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
          <Tabs default-value="ous" class="w-auto">
            <TabsList class="grid w-full grid-cols-2">
              <TabsTrigger value="ous">Unidades Administrativas</TabsTrigger>
              <TabsTrigger value="logs">Actividad</TabsTrigger>
            </TabsList>
            <TabsContent value="ous">
              <OrganizationalUnits
                :filters
                :resource-id="organization.id"
                :ous
              />
            </TabsContent>
            <TabsContent value="logs">
              <ActivityLogs
                :filters
                :logs
                :route="routes.show(organization.id)"
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
            data: { id: organization.id },
            options: { preserveState: false },
          })
        "
      />
    </ContentLayout>
  </AppLayout>
</template>
