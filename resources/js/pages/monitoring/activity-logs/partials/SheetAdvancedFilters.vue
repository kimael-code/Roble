<script setup lang="ts">
import { Button } from '@/components/ui/button';
import { Sheet, SheetClose, SheetContent, SheetDescription, SheetFooter, SheetHeader, SheetTitle } from '@/components/ui/sheet';
import { Tabs, TabsContent, TabsList, TabsTrigger } from '@/components/ui/tabs';
import { TagsInput, TagsInputInput, TagsInputItem, TagsInputItemDelete, TagsInputItemText } from '@/components/ui/tags-input';
import { User } from '@/types';
import { router } from '@inertiajs/vue3';
import { ref, Ref } from 'vue';
import ComboboxEvents from './ComboboxEvents.vue';
import ComboboxLogNames from './ComboboxLogNames.vue';
import ComboboxUsers from './ComboboxUsers.vue';
import DateTimePickers from './DateTimePickers.vue';

defineProps<{
  show: boolean;
  users?: Array<User>;
  events?: Array<string>;
  logNames?: Array<string>;
}>();

const emit = defineEmits(['close', 'advancedSearch']);

const form: Ref<{ [key: string]: any }> = ref({});

function submitSearch() {
  router.reload({
    data: form.value,
    only: ['logs'],
    onSuccess: () => emit('advancedSearch', form.value),
  });
}
</script>

<template>
  <div class="grid grid-cols-2 gap-2">
    <Sheet :open="show" @update:open="$emit('close')">
      <SheetContent side="top">
        <SheetHeader>
          <SheetTitle>Filtros de Búsqueda Avanzados</SheetTitle>
          <SheetDescription>Parametrice la consulta de registros haciendo uso de los siguientes controles.</SheetDescription>
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
            <ComboboxUsers :users @selected="(u) => (form.users = u)" />
          </TabsContent>
          <TabsContent value="events">
            <ComboboxEvents :events @selected="(e) => (form.events = e)" />
          </TabsContent>
          <TabsContent value="logNames">
            <ComboboxLogNames :log-names="logNames" @selected="(l) => (form.modules = l)" />
          </TabsContent>
          <TabsContent value="dateTime">
            <DateTimePickers
              @date-range-set="(dr) => (form.date_range = dr)"
              @date-set="(d) => (form.date = d)"
              @time-from-set="(tf) => (form.time_from = tf)"
              @time-set="(t) => (form.time = t)"
              @time-until-set="(tu) => (form.time_until = tu)"
            />
          </TabsContent>
          <TabsContent value="ipAddrs">
            <TagsInput v-model="form.ips">
              <TagsInputItem v-for="item in form.ips" :key="item" :value="item">
                <TagsInputItemText />
                <TagsInputItemDelete />
              </TagsInputItem>

              <TagsInputInput id="ip-addrs" :auto-focus="true" placeholder="Direcciones IP..." />
            </TagsInput>
          </TabsContent>
        </Tabs>
        <SheetFooter>
          <SheetClose as-child>
            <Button type="button" @click="submitSearch"> Aplicar Filtros </Button>
          </SheetClose>
        </SheetFooter>
      </SheetContent>
    </Sheet>
  </div>
</template>
