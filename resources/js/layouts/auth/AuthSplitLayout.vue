<script setup lang="ts">
import AnimatedStarSky from '@/components/AnimatedStarSky.vue';
import AppLogoIcon from '@/components/AppLogoIcon.vue';
import { home } from '@/routes';
import { Link, usePage } from '@inertiajs/vue3';

const page = usePage();
const name = page.props.name;
const quote = page.props.quote;

defineProps<{
  title?: string;
  description?: string;
}>();
</script>

<template>
  <div
    class="relative grid h-dvh flex-col items-center justify-center bg-white px-8 opacity-100 transition-opacity duration-750 sm:px-0 lg:max-w-none lg:grid-cols-2 lg:px-0 dark:bg-transparent starting:opacity-0"
  >
    <!-- Lado izquierdo con cielo estrellado -->
    <AnimatedStarSky :quote="quote" :app-name="name" :app-logo="true">
      <template #logo>
        <Link
          :href="home()"
          class="flex items-center gap-3 text-4xl font-medium"
        >
          <AppLogoIcon class="mb-1 size-12 fill-current" />
          <span
            class="text-shadow-light dark:text-shadow-dark text-gray-900 dark:text-white"
          >
            {{ name }}
          </span>
        </Link>
      </template>
    </AnimatedStarSky>

    <!-- Lado derecho (formularios) -->
    <div class="lg:p-8">
      <div
        class="mx-auto flex w-full flex-col justify-center space-y-6 sm:w-[350px]"
      >
        <div class="lg:hidden">
          <Link :href="home()" class="flex items-center text-lg font-medium">
            <AppLogoIcon class="mr-2 size-8 fill-current text-primary" />
            <span class="text-foreground">{{ name }}</span>
          </Link>
        </div>
        <div class="flex flex-col space-y-2 text-center">
          <h1 class="text-xl font-medium tracking-tight" v-if="title">
            {{ title }}
          </h1>
          <p class="text-sm text-muted-foreground" v-if="description">
            {{ description }}
          </p>
        </div>
        <slot />
      </div>
    </div>
  </div>
</template>
