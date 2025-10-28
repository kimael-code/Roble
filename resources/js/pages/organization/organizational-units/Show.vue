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
  DropdownMenuSeparator,
  DropdownMenuTrigger,
} from '@/components/ui/dropdown-menu';
import { Tabs, TabsContent, TabsList, TabsTrigger } from '@/components/ui/tabs';
import { Tooltip, TooltipContent, TooltipProvider, TooltipTrigger } from '@/components/ui/tooltip';
import { useConfirmAction, useRequestActions } from '@/composables';
import AppLayout from '@/layouts/AppLayout.vue';
import ContentLayout from '@/layouts/ContentLayout.vue';
import { ActivityLog, BreadcrumbItem, Can, OrganizationalUnit, PaginatedCollection, SearchFilter } from '@/types';
import { Head } from '@inertiajs/vue3';
import { ArrowLeftIcon, EllipsisIcon, LoaderCircleIcon, PencilIcon, PlusIcon, Trash2Icon, Workflow } from 'lucide-vue-next';
import { watch } from 'vue';
import CardOrganization from './partials/CardOrganization.vue';
import OrganizationalUnits from './partials/OrganizationalUnits.vue';
import OrganizationalUnitController from "@/actions/App/Http/Controllers/Organization/OrganizationalUnitController";

const props = defineProps<{
  can: Can;
  filters: SearchFilter;
  organizationalUnit: OrganizationalUnit;
  organizationalUnits: PaginatedCollection<OrganizationalUnit>;
  logs: PaginatedCollection<ActivityLog>;
}>();

const breadcrumbs: BreadcrumbItem[] = [
  {
    title: 'Unidades Administrativas',
    href: '/organizational-units',
  },
  {
    title: 'Ver',
    href: '',
  },
];

const { action, requestState, requestAction, resourceID } = useRequestActions(OrganizationalUnitController);
const { alertOpen, alertAction, alertActionCss, alertTitle, alertDescription } = useConfirmAction();

watch(action, () => {
  switch (action.value) {
    case 'destroy':
      alertAction.value = 'Eliminar permanentemente';
      alertActionCss.value = 'bg-destructive text-destructive-foreground hover:bg-destructive/90';
      alertTitle.value = `¿Eliminar unidad administrativa «${props.organizationalUnit.name}» permanentemente?`;
      alertDescription.value = `Esta acción no podrá revertirse. Los datos de «${props.organizationalUnit.name}» se perderán permanentemente.`;
      alertOpen.value = true;
      break;

    default:
      break;
  }
});
</script>

<template>
  <AppLayout :breadcrumbs>
    <Head title="Unidades Administrativas: Ver" />
    <ContentLayout :title="organizationalUnit.name" :description="`${organizationalUnit.acronym ?? '-'}`">
      <template #icon>
        <Workflow />
      </template>
      <section class="grid gap-4 md:grid-cols-4">
        <div class="col-span-3 md:col-span-1">
          <Card class="container">
            <CardHeader>
              <CardTitle>Detalles</CardTitle>
            </CardHeader>
            <CardContent>
              <p class="text-sm font-medium">Piso</p>
              <p class="text-sm text-muted-foreground">{{ organizationalUnit.floor ?? '-' }}</p>
              <br />
              <p v-if="organizationalUnit.code" class="text-sm font-medium">Código</p>
              <p v-if="organizationalUnit.code" class="text-sm text-muted-foreground">{{ organizationalUnit.code }}</p>
              <br v-if="organizationalUnit.code" />
              <p class="text- text-sm font-medium">Creado</p>
              <p class="text-sm text-muted-foreground">{{ organizationalUnit.created_at_human }}</p>
              <br />
              <p class="text-sm font-medium">Modificado</p>
              <p class="text-sm text-muted-foreground">{{ organizationalUnit.updated_at_human }}</p>
              <br />
              <p class="text- text-sm font-medium">Estatus</p>
              <p class="text-sm" :class="{ 'text-green-500': !organizationalUnit.disabled_at, 'text-red-500': organizationalUnit.disabled_at }">
                {{ organizationalUnit.status }}
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
                <TooltipContent> Regresar al listado de unidades administrativas </TooltipContent>
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
                          @click="requestAction({ operation: 'edit', data: { id: organizationalUnit.id }, options: { preserveState: false } })"
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
          <Tabs default-value="organization" class="w-auto">
            <TabsList class="grid w-full grid-cols-3">
              <TabsTrigger value="organization">Ente</TabsTrigger>
              <TabsTrigger value="ous">Unidades Administrativas</TabsTrigger>
              <TabsTrigger value="logs">Actividad</TabsTrigger>
            </TabsList>
            <TabsContent value="organization">
              <CardOrganization :organization="organizationalUnit.organization" :ou="organizationalUnit.organizational_unit" />
            </TabsContent>
            <TabsContent value="ous">
              <OrganizationalUnits :filters :resource-id="organizationalUnit.id" :ous="organizationalUnits" />
            </TabsContent>
            <TabsContent value="logs">
              <ActivityLogs :filters :logs :route="OrganizationalUnitController.show(organizationalUnit.id)" />
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
            <AlertDialogAction
              :class="alertActionCss"
              @click="requestAction({ data: { id: organizationalUnit.id }, options: { preserveState: false } })"
            >
              {{ alertAction }}
            </AlertDialogAction>
          </AlertDialogFooter>
        </AlertDialogContent>
      </AlertDialog>
    </ContentLayout>
  </AppLayout>
</template>
