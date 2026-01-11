<script setup lang="ts">
import ActivityLogController from '@/actions/App/Http/Controllers/Monitoring/ActivityLogController';
import { Button } from '@/components/ui/button';
import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/card';
import { Tabs, TabsContent, TabsList, TabsTrigger } from '@/components/ui/tabs';
import {
  Tooltip,
  TooltipContent,
  TooltipProvider,
  TooltipTrigger,
} from '@/components/ui/tooltip';
import { useRequestActions } from '@/composables';
import AppLayout from '@/layouts/AppLayout.vue';
import ContentLayout from '@/layouts/ContentLayout.vue';
import { ActivityLog, BreadcrumbItem, Can, UserAgent } from '@/types';
import { Head } from '@inertiajs/vue3';
import { ArrowLeftIcon, LoaderCircleIcon, LogsIcon } from 'lucide-vue-next';
import CardActivityDetails from './partials/CardActivityDetails.vue';
import CardUserDetails from './partials/CardUserDetails.vue';

defineProps<{
  can: Can;
  log: ActivityLog;
  userAgent: UserAgent;
}>();

const breadcrumbs: BreadcrumbItem[] = [
  {
    title: 'Trazas',
    href: '/activity-logs',
  },
  {
    title: 'Ver',
  },
];

const { requestState, requestAction } = useRequestActions(
  ActivityLogController,
);
</script>

<template>
  <AppLayout :breadcrumbs>
    <Head title="Trazas: Ver" />
    <ContentLayout
      :title="log.description"
      :description="`Tipo de evento: ${log.event}`"
    >
      <template #icon>
        <LogsIcon />
      </template>
      <section class="grid gap-4 md:grid-cols-4">
        <div class="col-span-3 md:col-span-1">
          <Card class="container">
            <CardHeader>
              <CardTitle>Detalles de la Solicitud</CardTitle>
            </CardHeader>
            <CardContent>
              <p class="text-sm font-medium">Marca de tiempo</p>
              <p class="text-sm text-muted-foreground">
                {{ log.created_at_human ?? '-' }}
              </p>
              <br />
              <p class="text-sm font-medium">Dirección IP</p>
              <p class="font-mono text-sm text-muted-foreground">
                {{ log.properties.request.ip_address }}
              </p>
              <br />
              <p class="text- text-sm font-medium">Ruta HTTP Solicitada</p>
              <p class="text-sm text-muted-foreground">
                {{ log.properties.request.request_url }}
              </p>
              <br />
              <p class="text- text-sm font-medium">Ruta HTTP Origen</p>
              <p class="text-sm text-muted-foreground">
                {{ log.properties.request.referer }}
              </p>
              <br />
              <p class="text-sm font-medium">Método HTTP Ejecutado</p>
              <p class="text-sm text-muted-foreground">
                {{ log.properties.request.http_method }}
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
                    <LoaderCircleIcon
                      v-if="requestState.readAll"
                      class="h-4 w-4 animate-spin"
                    />
                    <ArrowLeftIcon v-else class="mr-2 h-4 w-4" />
                    Regresar
                  </Button>
                </TooltipTrigger>
                <TooltipContent> Regresar al listado de trazas </TooltipContent>
              </Tooltip>
            </TooltipProvider>
          </div>
          <Tabs default-value="user" class="w-auto">
            <TabsList class="grid w-full grid-cols-2">
              <TabsTrigger value="user">REALIZADO POR</TabsTrigger>
              <TabsTrigger value="ous">DATOS DEL EVENTO</TabsTrigger>
            </TabsList>
            <TabsContent value="user">
              <CardUserDetails
                :causer="log.causer ?? log.properties.causer"
                :user-agent="userAgent"
              />
            </TabsContent>
            <TabsContent value="ous">
              <CardActivityDetails :log />
            </TabsContent>
          </Tabs>
        </div>
      </section>
    </ContentLayout>
  </AppLayout>
</template>
