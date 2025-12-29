<script setup lang="ts">
import { Input } from '@/components/ui/input';
import { ScrollArea } from '@/components/ui/scroll-area';
import { Separator } from '@/components/ui/separator';
import { PaginatedCollection, Permission, SearchFilter } from '@/types';
import { router } from '@inertiajs/vue3';
import { watchDebounced } from '@vueuse/core';
import { DeleteIcon, Search } from 'lucide-vue-next';
import { ref } from 'vue';

interface Props {
  filters: SearchFilter;
  userId: number;
  permissions: PaginatedCollection<Permission>;
  permissionsCount: number;
}

const props = defineProps<Props>();
const search = ref(props.filters.search);

function clearSearch() {
  search.value = undefined;
}

watchDebounced(
  search,
  (s) => {
    if (s === '') search.value = undefined;

    router.reload({
      data: { search: s },
      only: ['permissions'],
      preserveUrl: true,
    });
  },
  { debounce: 500, maxWait: 1000 },
);
</script>

<template>
  <div class="flex items-center justify-start px-2">
    <div class="mr-3 text-sm text-muted-foreground">
      {{ permissions.data.length }} de {{ permissionsCount }} registros
    </div>
    <div class="relative w-full max-w-sm items-center p-4">
      <Input
        id="search"
        type="text"
        placeholder="Buscar..."
        class="pl-10 pr-10"
        v-model="search"
      />
      <span
        class="absolute inset-y-0 start-0 flex items-center justify-center px-5"
      >
        <Search class="size-6 text-muted-foreground" />
      </span>
      <span
        v-if="search"
        class="absolute inset-y-0 right-0 flex cursor-pointer items-center justify-center px-5 opacity-100 transition-opacity duration-750 starting:opacity-0"
        @click="clearSearch()"
      >
        <DeleteIcon class="size-6 text-muted-foreground" />
      </span>
    </div>
  </div>
  <ScrollArea class="m-3 h-75 rounded-md border">
    <div class="p-4">
      <div v-for="(permission, i) in permissions.data" :key="i">
        <div class="text-sm">{{ permission.description }}</div>
        <Separator class="my-2" />
      </div>
      <div v-if="!permissions.data.length" class="text-muted">
        No hay registros
      </div>
    </div>
  </ScrollArea>
</template>

