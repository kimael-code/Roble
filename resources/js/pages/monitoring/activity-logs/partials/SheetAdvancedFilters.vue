<script setup lang="ts">
import MultiSelectCombobox from '@/components/MultiSelectCombobox.vue';
import { Button } from '@/components/ui/button';
import {
  Sheet,
  SheetClose,
  SheetContent,
  SheetDescription,
  SheetFooter,
  SheetHeader,
  SheetTitle,
} from '@/components/ui/sheet';
import { Tabs, TabsContent, TabsList, TabsTrigger } from '@/components/ui/tabs';
import {
  TagsInput,
  TagsInputInput,
  TagsInputItem,
  TagsInputItemDelete,
  TagsInputItemText,
} from '@/components/ui/tags-input';
import { User } from '@/types';
import { router } from '@inertiajs/vue3';
import { computed, ref } from 'vue';
import DateTimePickers from './DateTimePickers.vue';

const props = defineProps<{
  show: boolean;
  users?: Array<User>;
  events?: Array<string>;
  logNames?: Array<string>;
}>();

const emit = defineEmits(['close', 'advancedSearch']);

const selectedUsers = ref<string[]>([]);
const selectedEvents = ref<string[]>([]);
const selectedModules = ref<string[]>([]);
const selectedIps = ref<string[]>([]);
const dateRange = ref<any>(undefined);
const date = ref<any>(undefined);
const timeFrom = ref<any>(undefined);
const time = ref<any>(undefined);
const timeUntil = ref<any>(undefined);

// Mapeo de props a formato {label, value} requerido por el componente MultiSelectCombobox
const userOptions = computed(() =>
  (props.users ?? []).map((u) => ({ label: u.name, value: u.name })),
);
const eventOptions = computed(() =>
  (props.events ?? []).map((e) => ({ label: e, value: e })),
);
const moduleOptions = computed(() =>
  (props.logNames ?? []).map((l) => ({ label: l, value: l })),
);

function clearFilters() {
  selectedUsers.value = [];
  selectedEvents.value = [];
  selectedModules.value = [];
  selectedIps.value = [];
  dateRange.value = undefined;
  date.value = undefined;
  timeFrom.value = undefined;
  time.value = undefined;
  timeUntil.value = undefined;
}

function handleSubmit() {
  const form = {
    users: selectedUsers.value.length ? selectedUsers.value : undefined,
    events: selectedEvents.value.length ? selectedEvents.value : undefined,
    modules: selectedModules.value.length ? selectedModules.value : undefined,
    ips: selectedIps.value.length ? selectedIps.value : undefined,
    date_range: dateRange.value,
    date: date.value,
    time_from: timeFrom.value,
    time: time.value,
    time_until: timeUntil.value,
  };
  router.reload({
    data: form,
    only: ['logs'],
    preserveUrl: true,
    onSuccess: () => emit('advancedSearch', form),
  });
}
</script>

<template>
  <Sheet :open="show" @update:open="$emit('close')">
    <SheetContent side="top" class="overflow-y-auto">
      <SheetHeader>
        <SheetTitle>Trazas: Filtros de Búsqueda Avanzados</SheetTitle>
        <SheetDescription>
          Parametrice la consulta de registros haciendo uso de los siguientes
          controles. Navegue entre las pestañas para seleccionar los filtros
          deseados.
        </SheetDescription>
      </SheetHeader>
      <Tabs default-value="users" class="pr-4 pl-4" :unmount-on-hide="false">
        <TabsList class="grid w-full grid-cols-5">
          <TabsTrigger value="users">Usuarios</TabsTrigger>
          <TabsTrigger value="events">Eventos</TabsTrigger>
          <TabsTrigger value="logNames">Módulos</TabsTrigger>
          <TabsTrigger value="dateTime">Tiempo</TabsTrigger>
          <TabsTrigger value="ipAddrs">Direcciones IP</TabsTrigger>
        </TabsList>
        <TabsContent value="users">
          <MultiSelectCombobox
            id="activity-logs-users"
            v-model="selectedUsers"
            :options="userOptions"
            placeholder="Seleccione uno o más usuarios"
          />
        </TabsContent>
        <TabsContent value="events">
          <MultiSelectCombobox
            id="activity-logs-events"
            v-model="selectedEvents"
            :options="eventOptions"
            placeholder="Seleccione uno o más eventos"
          />
        </TabsContent>
        <TabsContent value="logNames">
          <MultiSelectCombobox
            id="activity-logs-modules"
            v-model="selectedModules"
            :options="moduleOptions"
            placeholder="Seleccione uno o más módulos"
          />
        </TabsContent>
        <TabsContent value="dateTime">
          <DateTimePickers
            @date-range-set="(dr) => (dateRange = dr)"
            @date-set="(d) => (date = d)"
            @time-from-set="(tf) => (timeFrom = tf)"
            @time-set="(t) => (time = t)"
            @time-until-set="(tu) => (timeUntil = tu)"
          />
        </TabsContent>
        <TabsContent value="ipAddrs">
          <TagsInput v-model="selectedIps">
            <TagsInputItem
              v-for="item in selectedIps"
              :key="item"
              :value="item"
            >
              <TagsInputItemText />
              <TagsInputItemDelete />
            </TagsInputItem>

            <TagsInputInput
              id="ip-addrs"
              :auto-focus="true"
              placeholder="Direcciones IP..."
            />
          </TagsInput>
        </TabsContent>
      </Tabs>
      <SheetFooter>
        <Button type="button" @click="handleSubmit"> Filtrar </Button>
        <SheetClose as-child>
          <Button type="button" variant="outline" @click="clearFilters">
            Cerrar
          </Button>
        </SheetClose>
      </SheetFooter>
    </SheetContent>
  </Sheet>
</template>
