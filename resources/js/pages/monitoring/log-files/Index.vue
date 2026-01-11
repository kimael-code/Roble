<script setup lang="ts">
import LogFileController from '@/actions/App/Http/Controllers/Monitoring/LogFileController';
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
import { ScrollArea } from '@/components/ui/scroll-area';
import {
  Sheet,
  SheetClose,
  SheetContent,
  SheetDescription,
  SheetFooter,
  SheetHeader,
  SheetTitle,
} from '@/components/ui/sheet';
import {
  Table,
  TableBody,
  TableCaption,
  TableCell,
  TableHead,
  TableHeader,
  TableRow,
} from '@/components/ui/table';
import { Tabs, TabsList, TabsTrigger } from '@/components/ui/tabs';
import TabsContent from '@/components/ui/tabs/TabsContent.vue';
import {
  Tooltip,
  TooltipContent,
  TooltipProvider,
  TooltipTrigger,
} from '@/components/ui/tooltip';
import AppLayout from '@/layouts/AppLayout.vue';
import ContentLayout from '@/layouts/ContentLayout.vue';
import { BreadcrumbItem, Can } from '@/types';
import { Head, router, useForm } from '@inertiajs/vue3';
import { FlexRender } from '@tanstack/vue-table';
import { BugIcon, FileDownIcon, ShredderIcon } from 'lucide-vue-next';
import { computed, ref } from 'vue';

interface LogContent {
  context: string;
  level: string;
  levelClass: string;
  levelIcon: string;
  date: string;
  text: string;
  inFile: string;
  stack: string;
}

const props = defineProps<{
  can: Can;
  logFiles: Array<string>;
  logs: Array<LogContent>;
  selectedFile: string;
}>();

const breadcrumbs: BreadcrumbItem[] = [
  {
    title: 'Logs',
    href: '',
  },
];

const deleteForm = useForm({});

const stackTrace = ref('');
const fileToDelete = ref('');
const openAlertDialog = ref(false);
const openTraceStack = ref(false);

// Computed property for responsive tabs layout
const tabsGridClass = computed(() => {
  const fileCount = props.logFiles.length;
  // For mobile: flex vertical, for desktop: grid horizontal
  return `w-full grid grid-cols-1 md:grid-cols-${Math.min(fileCount, 4)}`;
});

function handleTabChange(fileName: string) {
  if (fileName === props.selectedFile) return;

  router.visit(LogFileController.index().url, {
    data: { file: fileName },
    only: ['logs', 'selectedFile'],
    preserveScroll: true,
    preserveState: true,
    preserveUrl: true,
  });
}

function handleConfirm(fileName: string) {
  fileToDelete.value = fileName;
  openAlertDialog.value = true;
}

function handleStackTrace(stack: string) {
  stackTrace.value = stack;
  openTraceStack.value = true;
}

function download(fileName: string) {
  const url = LogFileController.export({ file: fileName }).url;
  window.location.href = url;
}

function deleteLog() {
  deleteForm.delete(LogFileController.delete(fileToDelete.value).url, {
    preserveScroll: true,
    preserveState: true,
    onFinish: () => (fileToDelete.value = ''),
  });
}
</script>

<template>
  <AppLayout :breadcrumbs>
    <Head title="Logs" />
    <ContentLayout
      title="Logs"
      description="Registros de los archivos de depuración generados en la aplicación."
    >
      <template #icon>
        <BugIcon />
      </template>

      <!-- Empty State: No Log Files -->
      <div
        v-if="!logFiles || logFiles.length === 0"
        class="flex flex-col items-center justify-center py-16 text-center"
      >
        <BugIcon class="h-16 w-16 text-muted-foreground/50 mb-4" />
        <h3 class="text-lg font-semibold mb-2">No hay archivos de log</h3>
        <p class="text-sm text-muted-foreground max-w-md">
          No se han generado archivos de depuración en la aplicación. Los logs
          aparecerán aquí cuando se registren eventos o errores.
        </p>
      </div>

      <!-- Log Files Tabs -->
      <Tabs
        v-else
        :key="props.selectedFile"
        :model-value="props.selectedFile"
        class="w-auto"
      >
        <TabsList :class="tabsGridClass">
          <TabsTrigger
            v-for="(logFile, i) in logFiles"
            :value="logFile"
            :key="i"
            @click="handleTabChange(logFile)"
          >
            {{ logFile }}
          </TabsTrigger>
        </TabsList>
        <TabsContent :value="props.selectedFile">
          <div class="flex items-center justify-between px-2 py-4">
            <div class="mr-3 text-sm text-muted-foreground">
              {{ `${logs.length || 0} entradas` }}
            </div>
            <div class="flex items-center">
              <TooltipProvider>
                <Tooltip>
                  <TooltipTrigger as-child>
                    <Button
                      variant="destructive"
                      v-if="can && can.delete"
                      class="ml-3"
                      @click="handleConfirm(props.selectedFile)"
                    >
                      <ShredderIcon class="mr-2 h-4 w-4" />
                      Eliminar
                    </Button>
                  </TooltipTrigger>
                  <TooltipContent>
                    <p>
                      El archivo será eliminado permanentemente. Conviene de
                      descargalo antes de eliminarlo.
                    </p>
                  </TooltipContent>
                </Tooltip>
                <Tooltip>
                  <TooltipTrigger as-child>
                    <Button
                      v-if="can && can.export_collection"
                      class="ml-3"
                      @click="download(props.selectedFile)"
                    >
                      <FileDownIcon class="mr-2 h-4 w-4" />
                      Descargar
                    </Button>
                  </TooltipTrigger>
                  <TooltipContent>
                    <p>
                      Guardar una copia del archivo en este dispositivo. La
                      marca de tiempo es prefijada al nombre del archivo.
                    </p>
                  </TooltipContent>
                </Tooltip>
              </TooltipProvider>
            </div>
          </div>
          <br />
          <Table>
            <TableCaption>Registros del log</TableCaption>
            <TableHeader>
              <TableRow>
                <TableHead class="w-[100px]">Nivel</TableHead>
                <TableHead class="w-[175px]">Marca de Tiempo</TableHead>
                <TableHead class="w-[250px]">Contenido</TableHead>
                <TableHead class="w-[150px]">Traza</TableHead>
              </TableRow>
            </TableHeader>
            <TableBody>
              <TableRow v-for="(row, i) in logs" :key="i">
                <TableCell class="font-medium">
                  {{ row.level }}
                </TableCell>
                <TableCell>{{ row.date }}</TableCell>
                <TableCell class="whitespace-pre-wrap"
                  ><FlexRender :render="row.text"
                /></TableCell>
                <TableCell>
                  <Button
                    type="button"
                    variant="link"
                    @click="handleStackTrace(row.stack)"
                    >Ver Traza</Button
                  >
                </TableCell>
              </TableRow>
            </TableBody>
          </Table>
        </TabsContent>
      </Tabs>
    </ContentLayout>

    <Sheet v-model:open="openTraceStack">
      <SheetContent side="bottom">
        <div class="mx-auto w-full">
          <SheetHeader>
            <SheetTitle>Traza de la Pila</SheetTitle>
            <SheetDescription>Detalles del log</SheetDescription>
          </SheetHeader>
          <ScrollArea class="h-72 rounded-md border">
            <pre>{{ stackTrace }}</pre>
          </ScrollArea>
          <SheetFooter>
            <SheetClose>
              <Button variant="outline" @click="stackTrace = ''">Cerrar</Button>
            </SheetClose>
          </SheetFooter>
        </div>
      </SheetContent>
    </Sheet>

    <AlertDialog v-model:open="openAlertDialog">
      <AlertDialogContent>
        <AlertDialogHeader>
          <AlertDialogTitle>{{
            `¿Eliminar archivo ${fileToDelete}?`
          }}</AlertDialogTitle>
          <AlertDialogDescription>
            Antes de eliminarlo asegúrese de haberlo descargado previamente.
          </AlertDialogDescription>
        </AlertDialogHeader>
        <AlertDialogFooter>
          <AlertDialogCancel @click="fileToDelete = ''">
            Cancelar
          </AlertDialogCancel>
          <AlertDialogAction
            class="bg-destructive text-destructive-foreground hover:bg-destructive/90"
            @click="deleteLog"
          >
            Eliminar
          </AlertDialogAction>
        </AlertDialogFooter>
      </AlertDialogContent>
    </AlertDialog>
  </AppLayout>
</template>

<style lang="css" scoped>
@media all and (orientation: landscape) {
  pre {
    white-space: pre-wrap;
    word-wrap: break-word;
    text-align: start;
  }
}
</style>
