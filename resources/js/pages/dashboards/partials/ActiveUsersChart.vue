<script setup lang="ts">
import PieChart from '@/components/charts/PieChart.vue';
import {
  Card,
  CardContent,
  CardDescription,
  CardHeader,
  CardTitle,
} from '@/components/ui/card';
import { computed } from 'vue';

interface ActiveUser {
  user: {
    id: number;
    name: string;
    email: string;
    avatar?: string;
    created_at_human?: string;
  };
  ip_address: string;
  last_active: string;
}

interface Props {
  activeUsers: ActiveUser[];
}

const props = defineProps<Props>();

// Preparar datos para la gráfica
const chartData = computed(() => {
  const userCount = props.activeUsers.length;

  // Mostrar solo usuarios conectados, sin inventar usuarios desconectados
  if (userCount === 0) {
    return {
      data: [1],
      labels: ['Sin usuarios conectados'],
    };
  }

  return {
    data: [userCount],
    labels: ['Conectados'],
  };
});

// Preparar nombres de usuarios para la leyenda
const userNames = computed(() => {
  return props.activeUsers.map((u) => u.user.name).join(', ');
});
</script>

<template>
  <Card class="bg-accent">
    <CardHeader>
      <CardTitle>Usuarios Activos</CardTitle>
      <CardDescription>
        Usuarios que actualmente han iniciado sesión.
        <span v-if="activeUsers.length > 0" class="mt-1 block text-sm">
          Conectados: {{ userNames }}
        </span>
      </CardDescription>
      <CardContent>
        <PieChart
          title="Usuarios Activos"
          :data="chartData.data"
          :labels="chartData.labels"
          height="240px"
        />
      </CardContent>
    </CardHeader>
  </Card>
</template>
