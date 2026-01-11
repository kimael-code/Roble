<script setup lang="ts">
import PieChart from '@/components/charts/PieChart.vue';
import {
  Card,
  CardContent,
  CardDescription,
  CardHeader,
  CardTitle,
} from '@/components/ui/card';

interface Props {
  data: number[];
  labels: string[];
  total: number;
}

defineProps<Props>();
</script>

<template>
  <Card class="bg-accent">
    <CardHeader>
      <CardTitle>Usuarios</CardTitle>
      <CardDescription
        >Estados de usuarios registrados en el sistema.
        <span class="mt-1 block text-sm font-semibold">
          Total: {{ total }} usuarios
        </span>
      </CardDescription>
      <CardContent>
        <PieChart
          title="Usuarios"
          :data="data"
          :labels="labels"
          height="240px"
        />
      </CardContent>
    </CardHeader>
  </Card>
</template>
