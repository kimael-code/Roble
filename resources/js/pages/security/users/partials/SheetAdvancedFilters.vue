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
import { Permission, Role } from '@/types';
import { router } from '@inertiajs/vue3';
import { computed, ref } from 'vue';

const props = defineProps<{
  show: boolean;
  permissions?: Permission[];
  roles?: Role[];
  statuses?: { label: string; value: string }[];
}>();

const emit = defineEmits(['close', 'advancedSearch']);

const selectedStatuses = ref<string[]>([]);
const selectedRoles = ref<string[]>([]);
const selectedPermissions = ref<string[]>([]);

// Mapeo de props a formato {label, value} requerido por el componente MultiSelectCombobox
const statusOptions = computed(() =>
  (props.statuses ?? []).map((s) => ({ label: s.label, value: s.value })),
);
const roleOptions = computed(() =>
  (props.roles ?? []).map((r) => ({ label: r.name, value: r.name })),
);
const permissionOptions = computed(() =>
  (props.permissions ?? []).map((p) => ({
    value: p.name,
    label: p.description,
  })),
);

function clearFilters() {
  selectedStatuses.value = [];
  selectedRoles.value = [];
  selectedPermissions.value = [];
}

function handleSubmit() {
  const form = {
    statuses: selectedStatuses.value.length
      ? selectedStatuses.value
      : undefined,
    roles: selectedRoles.value.length ? selectedRoles.value : undefined,
    permissions: selectedPermissions.value.length
      ? selectedPermissions.value
      : undefined,
  };
  router.reload({
    data: form,
    only: ['users'],
    preserveUrl: true,
    onSuccess: () => emit('advancedSearch', form),
  });
}
</script>

<template>
  <Sheet :open="show" @update:open="$emit('close')">
    <SheetContent side="top" class="overflow-y-auto">
      <SheetHeader>
        <SheetTitle>Usuarios: Filtros de Búsqueda Avanzados</SheetTitle>
        <SheetDescription>
          Parametrice la consulta de registros haciendo uso de los siguientes
          controles. Navegue entre las pestañas para seleccionar los filtros
          deseados.
        </SheetDescription>
      </SheetHeader>
      <Tabs
        default-value="permissions"
        class="pr-4 pl-4"
        :unmount-on-hide="false"
      >
        <TabsList class="grid w-full grid-cols-3">
          <TabsTrigger value="permissions">Permisos</TabsTrigger>
          <TabsTrigger value="roles">Roles</TabsTrigger>
          <TabsTrigger value="statuses">Estatus</TabsTrigger>
        </TabsList>
        <TabsContent value="permissions">
          <MultiSelectCombobox
            id="permissions"
            v-model="selectedPermissions"
            :options="permissionOptions"
            placeholder="Seleccione uno o más permisos"
          />
        </TabsContent>
        <TabsContent value="roles">
          <MultiSelectCombobox
            id="roles"
            v-model="selectedRoles"
            :options="roleOptions"
            placeholder="Seleccione uno o más roles"
          />
        </TabsContent>
        <TabsContent value="statuses">
          <MultiSelectCombobox
            id="statuses"
            v-model="selectedStatuses"
            :options="statusOptions"
            placeholder="Seleccione uno o más estatus"
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
