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
}

defineProps<Props>();
</script>

<template>
  <Card class="bg-accent">
    <CardHeader>
      <CardTitle>Roles</CardTitle>
      <CardDescription>Cantidad de usuarios por rol.</CardDescription>
      <CardContent>
        <PieChart title="Roles" :data="data" :labels="labels" height="240px" />
      </CardContent>
    </CardHeader>
  </Card>
</template>
