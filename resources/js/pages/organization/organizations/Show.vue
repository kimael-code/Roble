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
import { AspectRatio } from '@/components/ui/aspect-ratio';
import { Button } from '@/components/ui/button';
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
import { Tabs, TabsContent, TabsList, TabsTrigger } from '@/components/ui/tabs';
import { Tooltip, TooltipContent, TooltipProvider, TooltipTrigger } from '@/components/ui/tooltip';
import { useConfirmAction, useRequestActions } from '@/composables';
import AppLayout from '@/layouts/AppLayout.vue';
import ContentLayout from '@/layouts/ContentLayout.vue';
import { ActivityLog, BreadcrumbItem, Can, Organization, OrganizationalUnit, PaginatedCollection, SearchFilter } from '@/types';
import { Head } from '@inertiajs/vue3';
import { ArrowLeftIcon, Building, EllipsisIcon, LoaderCircleIcon, PencilIcon, PlusIcon, Trash2Icon } from 'lucide-vue-next';
import { watch } from 'vue';
import OrganizationalUnits from './partials/OrganizationalUnits.vue';
import routes from "@/actions/App/Http/Controllers/Organization/OrganizationController";

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

const { action, requestState, requestAction, resourceID } = useRequestActions(routes);
const { alertOpen, alertAction, alertActionCss, alertTitle, alertDescription } = useConfirmAction();

watch(action, () => {
  switch (action.value) {
    case 'destroy':
      alertAction.value = 'Eliminar permanentemente';
      alertActionCss.value = 'bg-destructive text-destructive-foreground hover:bg-destructive/90';
      alertTitle.value = `¿Eliminar ente «${props.organization.name}» permanentemente?`;
      alertDescription.value = `Esta acción no podrá revertirse. Los datos de «${props.organization.name}» se perderán permanentemente.`;
      alertOpen.value = true;
      break;

    default:
      break;
  }
});
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
                  <img class="h-full w-full object-cover" :src="organization.logo_url" alt="Logo empresarial" />
                </AspectRatio>
              </div>
              <br />
              <p class="text-sm font-medium">Acrónimo</p>
              <p class="text-sm text-muted-foreground">{{ organization.acronym }}</p>
              <br />
              <p class="text-sm font-medium">Dirección</p>
              <p class="text-sm text-muted-foreground">{{ organization.address }}</p>
              <br />
              <p class="text- text-sm font-medium">Creado</p>
              <p class="text-sm text-muted-foreground">{{ organization.created_at_human }}</p>
              <br />
              <p class="text-sm font-medium">Modificado</p>
              <p class="text-sm text-muted-foreground">{{ organization.updated_at_human }}</p>
              <br />
              <p class="text- text-sm font-medium">Estatus</p>
              <p class="text-sm" :class="{ 'text-green-500': !organization.disabled_at, 'text-red-500': organization.disabled_at }">
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
                  <Button variant="secondary" @click="requestAction({ operation: 'read_all' })" :disabled="requestState.readAll">
                    <LoaderCircleIcon v-if="requestState.readAll" class="h-4 w-4 animate-spin" />
                    <ArrowLeftIcon v-else class="mr-2 h-4 w-4" />
                    Regresar
                  </Button>
                </TooltipTrigger>
                <TooltipContent> Regresar al listado de entes </TooltipContent>
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
                          @click="requestAction({ operation: 'edit', data: { id: organization.id }, options: { preserveState: false } })"
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
          <Tabs default-value="ous" class="w-auto">
            <TabsList class="grid w-full grid-cols-2">
              <TabsTrigger value="ous">Unidades Administrativas</TabsTrigger>
              <TabsTrigger value="logs">Actividad</TabsTrigger>
            </TabsList>
            <TabsContent value="ous">
              <OrganizationalUnits :filters :resource-id="organization.id" :ous />
            </TabsContent>
            <TabsContent value="logs">
              <ActivityLogs :filters :logs :route="routes.show(organization.id)" />
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
            <AlertDialogAction :class="alertActionCss" @click="requestAction({ data: { id: organization.id }, options: { preserveState: false } })">
              {{ alertAction }}
            </AlertDialogAction>
          </AlertDialogFooter>
        </AlertDialogContent>
      </AlertDialog>
    </ContentLayout>
  </AppLayout>
</template>
