<script setup lang="ts">
import { Button } from '@/components/ui/button';
import {
  Command,
  CommandEmpty,
  CommandGroup,
  CommandInput,
  CommandItem,
  CommandList,
} from '@/components/ui/command';
import {
  Popover,
  PopoverContent,
  PopoverTrigger,
} from '@/components/ui/popover';
import { cn } from '@/lib/utils';
import { watchDebounced } from '@vueuse/core';
import { CheckIcon, ChevronsUpDownIcon, XIcon } from 'lucide-vue-next';
import { computed, ref } from 'vue';

interface Option {
  value: string;
  label: string;
}

const props = withDefaults(
  defineProps<{
    id: string;
    modelValue: string[];
    options: Option[];
    placeholder?: string;
  }>(),
  {
    placeholder: 'Select items...',
  },
);

const emit = defineEmits<{
  (e: 'update:modelValue', value: string[]): void;
}>();

const open = ref(false);
const query = ref('');

const selectedLabels = computed(() =>
  props.options.filter((opt) => props.modelValue.includes(opt.value)),
);

const filteredOptions = computed(() => {
  const q = query.value.toLowerCase().trim();
  if (!q) return props.options;
  return props.options.filter(
    (opt) =>
      opt.label.toLowerCase().includes(q) ||
      opt.value.toLowerCase().includes(q),
  );
});

function toggleSelection(value: string) {
  if (props.modelValue.includes(value)) {
    emit(
      'update:modelValue',
      props.modelValue.filter((v) => v !== value),
    );
  } else {
    emit('update:modelValue', [...props.modelValue, value]);
  }
}

function removeTag(value: string) {
  toggleSelection(value);
}

watchDebounced(
  open,
  (o) => {
    if (!o) {
      query.value = '';
    }
  },
  { debounce: 200 },
);
</script>

<template>
  <Popover v-model:open="open">
    <PopoverTrigger as-child>
      <Button
        variant="outline"
        role="combobox"
        aria-expanded="false"
        class="flex h-auto min-h-10 w-full flex-wrap justify-between gap-1 p-2 pr-8"
      >
        <span
          v-if="modelValue.length === 0"
          class="text-sm text-muted-foreground"
        >
          {{ placeholder }}
        </span>
        <div v-else class="flex flex-wrap gap-1">
          <span
            v-for="item in selectedLabels"
            :key="item.value"
            class="inline-flex items-center gap-1 rounded bg-secondary px-2 py-1 text-xs font-light"
          >
            {{ item.label }}
            <button
              type="button"
              @click.stop="removeTag(item.value)"
              class="text-muted-foreground hover:text-foreground focus:outline-none"
            >
              <XIcon class="size-4" />
            </button>
          </span>
        </div>
        <ChevronsUpDownIcon
          class="pointer-events-none absolute top-1/2 md:right-6 size-4 shrink-0 translate-y-0 opacity-50"
        />
      </Button>
    </PopoverTrigger>

    <PopoverContent class="w-full p-0" align="start">
      <Command :filterFunction="filteredOptions" :should-filter="true">
        <CommandInput
          :id="id"
          v-model="query"
          placeholder="Buscar..."
          :value="query"
        />
        <CommandList>
          <CommandEmpty>No results.</CommandEmpty>
          <CommandGroup>
            <CommandItem
              v-for="option in filteredOptions"
              :key="option.value"
              :value="option.value"
              @select.prevent="
                (ev: any) => {
                  toggleSelection(ev.detail.value as string);
                }
              "
              class="cursor-pointer"
            >
              {{ option.label }}
              <CheckIcon
                :class="
                  cn(
                    'ml-auto size-4',
                    modelValue.includes(option.value)
                      ? 'opacity-100'
                      : 'opacity-0',
                  )
                "
              />
            </CommandItem>
          </CommandGroup>
        </CommandList>
      </Command>
    </PopoverContent>
  </Popover>
</template>
