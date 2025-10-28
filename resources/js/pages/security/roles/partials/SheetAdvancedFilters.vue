<script setup lang="ts">
import { Button } from '@/components/ui/button';
import { Sheet, SheetClose, SheetContent, SheetDescription, SheetFooter, SheetHeader, SheetTitle } from '@/components/ui/sheet';
import { Tabs, TabsContent, TabsList, TabsTrigger } from '@/components/ui/tabs';
import { Permission } from '@/types';
import { router } from '@inertiajs/vue3';
import { Ref, ref } from 'vue';
import ComboboxPermissions from './ComboboxPermissions.vue';
import RoleController from "@/actions/App/Http/Controllers/Security/RoleController";

defineProps<{
  show: boolean;
  permissions?: Array<Permission>;
}>();

const emit = defineEmits(['close', 'advancedSearch']);

const form: Ref<{ [key: string]: any }> = ref({});

function submitSearch() {
  router.visit(RoleController.index(), {
    data: form.value,
    only: ['roles'],
    preserveScroll: true,
    preserveState: true,
    preserveUrl: false,
    onSuccess: () => emit('advancedSearch', form.value),
  });
}
</script>

<template>
  <div class="grid grid-cols-2 gap-2">
    <Sheet :open="show" @update:open="$emit('close')">
      <SheetContent side="top">
        <SheetHeader>
          <SheetTitle>Roles: Filtros de Búsqueda Avanzados</SheetTitle>
          <SheetDescription>Parametrice la consulta de registros haciendo uso de los siguientes controles.</SheetDescription>
        </SheetHeader>
        <Tabs default-value="permissions" class="pr-4 pl-4" :unmount-on-hide="false">
          <TabsList class="grid w-full grid-cols-3">
            <TabsTrigger value="permissions">Permisos</TabsTrigger>
          </TabsList>
          <TabsContent value="permissions">
            <ComboboxPermissions :permissions @selected="(p) => (form.permissions = p)" />
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
