<script setup lang="ts">
import { Button } from '@/components/ui/button';
import { LoaderCircleIcon } from 'lucide-vue-next';
import type { Component } from 'vue';

const props = defineProps<{
  loading?: boolean;
  icon?: Component;
  variant?: 'default' | 'destructive' | 'outline' | 'secondary' | 'ghost' | 'link';
  size?: 'default' | 'sm' | 'lg' | 'icon';
}>();
</script>

<template>
  <Button :disabled="loading" :variant="variant" :size="size">
    <LoaderCircleIcon v-if="loading" class="mr-2 h-4 w-4 animate-spin" />
    <component v-else-if="icon" :is="icon" class="mr-2 h-4 w-4" />
    <slot />
  </Button>
</template>
