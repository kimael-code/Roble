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

interface LogFile {
  logName: string;
  sizeHuman: string;
  sizeRaw: number;
}

interface LogFilesData {
  logs: LogFile[];
  totalSize: number;
  totalSizeHuman: string;
}

interface Props {
  logFiles: LogFilesData;
}

const props = defineProps<Props>();

// Preparar datos para la gráfica
const chartData = computed(() => {
  if (!props.logFiles.logs || props.logFiles.logs.length === 0) {
    return {
      data: [1],
      labels: ['Sin archivos de logs'],
      colors: ['#e0e0e0'],
      customLabels: ['0 B'],
    };
  }

  const data = props.logFiles.logs.map((log) => log.sizeRaw);
  const labels = props.logFiles.logs.map((log) => log.logName);
  const customLabels = props.logFiles.logs.map((log) => log.sizeHuman);

  // Colores basados en el tamaño (verde < 17MB, amarillo < 50MB, rojo >= 50MB)
  const colors = props.logFiles.logs.map((log) => {
    if (log.sizeRaw < 17476266) return '#66bb6a'; // Verde
    if (log.sizeRaw < 52428800) return '#ffa726'; // Amarillo
    return '#ef5350'; // Rojo
  });

  return { data, labels, colors, customLabels };
});
</script>

<template>
  <Card class="bg-accent">
    <CardHeader>
      <CardTitle>Archivos de Logs</CardTitle>
      <CardDescription>
        Tamaño de los archivos de depuración (logs) de la aplicación.
        <span
          v-if="logFiles.totalSize > 0"
          class="mt-1 block text-sm font-semibold"
        >
          Tamaño total: {{ logFiles.totalSizeHuman }}
        </span>
      </CardDescription>
      <CardContent>
        <PieChart
          title="Archivos de Logs"
          :data="chartData.data"
          :labels="chartData.labels"
          :colors="chartData.colors"
          :custom-labels="chartData.customLabels"
          height="240px"
        />
      </CardContent>
    </CardHeader>
  </Card>
</template>
