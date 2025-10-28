<script setup lang="ts">
import { Button } from '@/components/ui/button';
import { Sheet, SheetClose, SheetContent, SheetDescription, SheetFooter, SheetHeader, SheetTitle } from '@/components/ui/sheet';
import { Tabs, TabsContent, TabsList, TabsTrigger } from '@/components/ui/tabs';
import { Role, User } from '@/types';
import { router } from '@inertiajs/vue3';
import { ref } from 'vue';
import ComboboxRoles from './ComboboxRoles.vue';
import ComboboxUsers from './ComboboxUsers.vue';

defineProps<{
  show: boolean;
  roles?: Array<Role>;
  users?: Array<User>;
}>();

const emit = defineEmits(['close', 'advancedSearch']);

const form = ref({
  operations: [],
  roles: [],
  users: [],
});

function submitSearch() {
  router.reload({
    data: form.value,
    only: ['permissions'],
    onSuccess: () => emit('advancedSearch', form.value),
  });
}
</script>

<template>
  <div class="grid grid-cols-2 gap-2">
    <Sheet :open="show" @update:open="$emit('close')">
      <SheetContent side="top">
        <SheetHeader>
          <SheetTitle>Permisos: Filtros de Búsqueda Avanzados</SheetTitle>
          <SheetDescription>Parametrice la consulta de registros haciendo uso de los siguientes controles.</SheetDescription>
        </SheetHeader>
        <Tabs default-value="users" class="pr-4 pl-4" :unmount-on-hide="false">
          <TabsList class="grid w-full grid-cols-2">
            <TabsTrigger value="users">Usuarios</TabsTrigger>
            <TabsTrigger value="roles">Roles</TabsTrigger>
          </TabsList>
          <TabsContent value="users">
            <ComboboxUsers :users @selected="(u) => (form.users = u)" />
          </TabsContent>
          <TabsContent value="roles">
            <ComboboxRoles :roles @selected="(r) => (form.roles = r)" />
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
