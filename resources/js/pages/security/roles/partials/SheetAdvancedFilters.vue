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
import { Permission } from '@/types';
import { router } from '@inertiajs/vue3';
import { computed, ref } from 'vue';

const props = defineProps<{
  show: boolean;
  permissions?: Permission[];
}>();

const emit = defineEmits(['close', 'advancedSearch']);

const selectedPermissions = ref<string[]>([]);

// Mapeo de props a formato {label, value} requerido por el componente MultiSelectCombobox
const permissionOptions = computed(() =>
  (props.permissions ?? []).map((p) => ({
    value: p.name,
    label: p.description,
  })),
);

function clearFilters() {
  selectedPermissions.value = [];
}

function handleSubmit() {
  const form = {
    permissions: selectedPermissions.value.length
      ? selectedPermissions.value
      : undefined,
  };
  router.reload({
    data: form,
    only: ['roles'],
    preserveUrl: true,
    onSuccess: () => emit('advancedSearch', form),
  });
}
</script>

<template>
  <div class="grid grid-cols-2 gap-2">
    <Sheet :open="show" @update:open="$emit('close')">
      <SheetContent side="top" class="overflow-y-auto">
        <SheetHeader>
          <SheetTitle>Roles: Filtros de Búsqueda Avanzados</SheetTitle>
          <SheetDescription
            >Parametrice la consulta de registros haciendo uso de los siguientes
            controles.</SheetDescription
          >
        </SheetHeader>
        <Tabs
          default-value="permissions"
          class="pr-4 pl-4"
          :unmount-on-hide="false"
        >
          <TabsList class="grid w-full grid-cols-1">
            <TabsTrigger value="permissions">Permisos</TabsTrigger>
          </TabsList>
          <TabsContent value="permissions">
            <MultiSelectCombobox
              id="permissions"
              v-model="selectedPermissions"
              :options="permissionOptions"
              placeholder="Seleccione uno o más permisos"
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
  </div>
</template>
