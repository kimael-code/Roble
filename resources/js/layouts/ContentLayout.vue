<script setup lang="ts">
defineProps<{
  title: string;
  description?: string;
}>();
</script>

<template>
  <div
    class="flex-1 overflow-y-auto px-4 py-6 opacity-100 transition-opacity duration-750 starting:opacity-0"
  >
    <div class="mb-4 space-y-0.5">
      <div class="flex items-start space-x-2">
        <slot v-if="$slots.icon" name="icon"></slot>
        <h2 class="text-xl font-semibold tracking-tight">{{ title }}</h2>
      </div>
      <p v-if="description" class="text-sm text-muted-foreground">
        {{ description }}
      </p>
    </div>
    <div
      class="flex flex-col space-y-8 md:space-y-0 lg:flex-row lg:space-y-0 lg:space-x-12"
    >
      <!-- Responsividad en tablas -->
      <div class="flex-1 sm:max-sm:max-w-2xl md:w-2xl">
        <section class="space-y-12 sm:max-sm:max-w-xl md:w-full">
          <slot />
        </section>
      </div>
    </div>
  </div>
</template>
