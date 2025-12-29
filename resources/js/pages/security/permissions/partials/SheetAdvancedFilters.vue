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
import { Role, User } from '@/types';
import { router } from '@inertiajs/vue3';
import { computed, ref } from 'vue';

const props = defineProps<{
  show: boolean;
  roles?: Role[];
  users?: User[];
}>();

const emit = defineEmits(['close', 'advancedSearch']);

const selectedRoles = ref<string[]>([]);
const selectedUsers = ref<string[]>([]);

// Mapeo de props a formato {label, value} requerido por el componente MultiSelectCombobox
const roleOptions = computed(() =>
  (props.roles ?? []).map((r) => ({ label: r.name, value: r.name })),
);
const userOptions = computed(() =>
  (props.users ?? []).map((r) => ({ label: r.name, value: r.name })),
);

function clearFilters() {
  selectedRoles.value = [];
  selectedUsers.value = [];
}

function handleSubmit() {
  const form = {
    roles: selectedRoles.value.length ? selectedRoles.value : undefined,
    users: selectedUsers.value.length ? selectedUsers.value : undefined,
  };
  router.reload({
    data: form,
    only: ['permissions'],
    preserveUrl: true,
    onSuccess: () => emit('advancedSearch', form),
  });
}
</script>

<template>
  <Sheet :open="show" @update:open="$emit('close')">
    <SheetContent side="top" class="overflow-y-auto">
      <SheetHeader>
        <SheetTitle>Permisos: Filtros de Búsqueda Avanzados</SheetTitle>
        <SheetDescription>
          Parametrice la consulta de registros haciendo uso de los siguientes
          controles. Navegue entre las pestañas para seleccionar los filtros
          deseados.
        </SheetDescription>
      </SheetHeader>
      <Tabs default-value="users" class="pr-4 pl-4" :unmount-on-hide="false">
        <TabsList class="grid w-full grid-cols-2">
          <TabsTrigger value="users">Usuarios</TabsTrigger>
          <TabsTrigger value="roles">Roles</TabsTrigger>
        </TabsList>
        <TabsContent value="users">
          <MultiSelectCombobox
            id="permissions"
            v-model="selectedUsers"
            :options="userOptions"
            placeholder="Seleccione uno o más usuarios"
          />
        </TabsContent>
        <TabsContent value="roles">
          <MultiSelectCombobox
            id="permissions"
            v-model="selectedRoles"
            :options="roleOptions"
            placeholder="Seleccione uno o más roles"
          />
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
