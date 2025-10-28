<script setup lang="ts">
import { Input } from '@/components/ui/input';
import { ScrollArea } from '@/components/ui/scroll-area';
import { Separator } from '@/components/ui/separator';
import { PaginatedCollection, Permission, SearchFilter } from '@/types';
import { router } from '@inertiajs/vue3';
import { watchDebounced } from '@vueuse/core';
import { Search } from 'lucide-vue-next';
import { ref } from 'vue';
import UserController from "@/actions/App/Http/Controllers/Security/UserController";

interface Props {
  filters: SearchFilter;
  userId: number;
  permissions: PaginatedCollection<Permission>;
  permissionsCount: number;
}

const props = defineProps<Props>();
const search = ref(props.filters.search);

watchDebounced(
  search,
  (s) => {
    if (s === '') search.value = undefined;

    router.visit(UserController.show(props.userId), {
      data: { search: s },
      only: ['permissions'],
      preserveScroll: true,
      preserveState: true,
    });
  },
  { debounce: 500, maxWait: 1000 },
);
</script>

<template>
  <div class="flex items-center justify-start px-2">
    <div class="text-muted-foreground mr-3 text-sm">{{ permissions.data.length }} de {{ permissionsCount }} registros</div>
    <div class="relative w-full max-w-sm items-center p-4">
      <Input id="search" type="text" placeholder="Buscar..." class="pl-10" v-model="search" />
      <span class="absolute inset-y-0 start-0 flex items-center justify-center px-5">
        <Search class="text-muted-foreground size-6" />
      </span>
    </div>
  </div>
    <ScrollArea class="m-3 h-75 rounded-md border">
      <div class="p-4">
        <div v-for="(permission, i) in permissions.data" :key="i">
          <div class="text-sm">{{ permission.description }}</div>
          <Separator class="my-2" />
        </div>
        <div v-if="!permissions.data.length" class="text-muted">No hay registros</div>
      </div>
    </ScrollArea>
</template>
