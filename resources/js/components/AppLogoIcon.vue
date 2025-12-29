<script setup lang="ts">
import type { HTMLAttributes } from 'vue';

defineOptions({
  inheritAttrs: false,
});

interface Props {
  className?: HTMLAttributes['class'];
}

defineProps<Props>();
</script>

<template>
  <svg
    xmlns="http://www.w3.org/2000/svg"
    viewBox="0 0 64 64"
    :class="className"
    v-bind="$attrs"
    fill="none"
    stroke="currentColor"
    stroke-width="1.8"
    stroke-linecap="round"
    stroke-linejoin="round"
  >
    <!-- Raíz central (origen del sistema) -->
    <circle cx="32" cy="52" r="2.2" fill="currentColor" />

    <!-- Tronco (vía principal vertical) -->
    <path d="M32 52 L32 40" />

    <!-- Primer nivel de bifurcación (2 ramas) -->
    <path d="M32 40 L26 34" />
    <path d="M32 40 L38 34" />

    <!-- Segundo nivel (4 ramas) -->
    <path d="M26 34 L22 28" />
    <path d="M26 34 L30 28" />
    <path d="M38 34 L34 28" />
    <path d="M38 34 L42 28" />

    <!-- Tercer nivel (6 nodos terminales) -->
    <path d="M22 28 L20 22" />
    <path d="M22 28 L24 22" />
    <path d="M30 28 L28 22" />
    <path d="M34 28 L36 22" />
    <path d="M42 28 L40 22" />
    <path d="M42 28 L44 22" />

    <!-- Nodos terminales (hojas del sistema) -->
    <circle cx="20" cy="22" r="1.4" fill="currentColor" />
    <circle cx="24" cy="22" r="1.4" fill="currentColor" />
    <circle cx="28" cy="22" r="1.4" fill="currentColor" />
    <circle cx="36" cy="22" r="1.4" fill="currentColor" />
    <circle cx="40" cy="22" r="1.4" fill="currentColor" />
    <circle cx="44" cy="22" r="1.4" fill="currentColor" />

    <!-- Refuerzo visual: conexión en zigzag sutil como "cobre" -->
    <!-- (Opcional: da textura de PCB sin distraer) -->
    <path d="M32 56 L30 56 L30 54 L34 54 L34 56 L32 56" stroke-width="1.2" opacity="0.4" />
  </svg>
</template>