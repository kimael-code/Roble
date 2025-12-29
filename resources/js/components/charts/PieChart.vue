<script setup lang="ts">
import { Button } from '@/components/ui/button';
import { useChartDownload } from '@/composables/useChartDownload';
import { useEchartsTheme } from '@/composables/useEchartsTheme';
import { PieChart } from 'echarts/charts';
import {
  LegendComponent,
  TitleComponent,
  TooltipComponent,
} from 'echarts/components';
import { use } from 'echarts/core';
import { CanvasRenderer } from 'echarts/renderers';
import { DownloadIcon } from 'lucide-vue-next';
import { computed, ref, watch } from 'vue';
import VChart from 'vue-echarts';

// Registrar componentes de ECharts
use([
  CanvasRenderer,
  PieChart,
  TitleComponent,
  TooltipComponent,
  LegendComponent,
]);

interface Props {
  title: string;
  data: number[];
  labels: string[];
  colors?: string[];
  height?: string;
  showDownload?: boolean;
  customLabels?: string[]; // Valores personalizados para mostrar en las etiquetas (ej: "200 KB")
}

const props = withDefaults(defineProps<Props>(), {
  colors: () => [
    '#2e93fa',
    '#e91e63',
    '#66bb6a',
    '#ffa726',
    '#ab47bc',
    '#26c6da',
  ],
  height: '240px',
  showDownload: true,
});

const { getThemeConfig, getTooltipConfig, isDark } = useEchartsTheme();
const { downloadChart } = useChartDownload();
const chartRef = ref<InstanceType<typeof VChart> | null>(null);

/**
 * Genera colores dinámicamente si hay más elementos que colores predefinidos.
 * Usa HSL para generar colores visualmente distintos.
 */
const generateColors = (count: number, baseColors: string[]): string[] => {
  if (count <= baseColors.length) {
    return baseColors.slice(0, count);
  }

  const colors: string[] = [...baseColors];
  const additionalCount = count - baseColors.length;

  // Generar colores adicionales usando HSL con saturación y luminosidad fijas
  for (let i = 0; i < additionalCount; i++) {
    const hue = (i * 137.5) % 360; // Ángulo dorado para máxima distinción
    const saturation = 65 + (i % 3) * 10; // Variar saturación: 65%, 75%, 85%
    const lightness = 55 + (i % 2) * 10; // Variar luminosidad: 55%, 65%
    colors.push(`hsl(${hue}, ${saturation}%, ${lightness}%)`);
  }

  return colors;
};

// Generar colores dinámicamente basados en la cantidad de datos
const chartColors = computed(() => {
  return generateColors(props.data.length, props.colors);
});

// Configuración de la gráfica
const option = computed(() => {
  const theme = getThemeConfig();
  const tooltip = getTooltipConfig();

  return {
    ...theme,
    tooltip: {
      ...tooltip,
      trigger: 'item' as const,
      formatter: (params: any) => {
        const { name, value, percent } = params;
        return `<strong>${name}</strong><br/>Cantidad: ${value} (${percent}%)`;
      },
    },
    legend: {
      orient: 'horizontal' as const,
      bottom: 0,
      left: 'center',
      textStyle: {
        color: isDark.value ? '#f5f5f5' : '#373d3f',
      },
      itemGap: 16,
      // Solo mostrar nombres en la leyenda, sin valores
      formatter: (name: string) => name,
    },
    series: [
      {
        type: 'pie' as const,
        radius: ['50%', '80%'],
        center: ['50%', '60%'],
        startAngle: 180,
        endAngle: 360,
        avoidLabelOverlap: true,
        itemStyle: {
          borderRadius: 8,
          borderColor: isDark.value ? '#1f2937' : '#fff',
          borderWidth: 2,
          // Sombra permanente
          shadowBlur: 8,
          shadowOffsetX: 0,
          shadowOffsetY: 2,
          shadowColor: isDark.value
            ? 'rgba(0, 0, 0, 0.4)'
            : 'rgba(0, 0, 0, 0.15)',
        },
        label: {
          show: true,
          position: 'outside' as const,
          formatter: (params: any) => {
            // Usar customLabels si está disponible, sino usar el valor numérico
            const displayValue =
              props.customLabels && props.customLabels[params.dataIndex]
                ? props.customLabels[params.dataIndex]
                : params.value;

            return `{c|${displayValue}}\n{p|(${params.percent}%)}`;
          },
          rich: {
            c: {
              fontSize: 16,
              fontWeight: 'bold',
              color: isDark.value ? '#f5f5f5' : '#373d3f',
              lineHeight: 20,
            },
            p: {
              fontSize: 13,
              color: isDark.value ? '#9ca3af' : '#6b7280',
            },
          },
          distanceToLabelLine: 5,
        },
        labelLine: {
          show: true,
          length: 15,
          length2: 10,
          smooth: true,
          lineStyle: {
            color: isDark.value ? '#6b7280' : '#9ca3af',
          },
        },
        emphasis: {
          label: {
            show: true,
            fontSize: 16,
            fontWeight: 'bold' as const,
          },
          itemStyle: {
            shadowBlur: 12,
            shadowOffsetX: 0,
            shadowOffsetY: 4,
            shadowColor: isDark.value
              ? 'rgba(0, 0, 0, 0.6)'
              : 'rgba(0, 0, 0, 0.25)',
          },
          // Efecto de agrandamiento al hover
          scaleSize: 10,
        },
        data: props.labels.map((label, index) => ({
          name: label,
          value: props.data[index],
          itemStyle: {
            color: chartColors.value[index],
          },
        })),
      },
    ],
  };
});

// Observar cambios en el tema para actualizar la gráfica
watch(isDark, () => {
  if (chartRef.value) {
    chartRef.value.setOption(option.value, true);
  }
});

// Función para descargar la gráfica
const handleDownload = () => {
  if (chartRef.value) {
    const chartInstance = chartRef.value.chart ?? null;
    downloadChart(
      chartInstance,
      props.title,
      props.title.toLowerCase().replace(/\s+/g, '_'),
    );
  }
};
</script>

<template>
  <div class="relative">
    <VChart
      ref="chartRef"
      :option="option"
      :style="{ height: height, minHeight: '250px' }"
      autoresize
      class="w-full"
    />
    <Button
      v-if="showDownload"
      variant="outline"
      size="icon"
      class="absolute top-2 right-2"
      @click="handleDownload"
    >
      <DownloadIcon class="h-4 w-4" />
    </Button>
  </div>
</template>
