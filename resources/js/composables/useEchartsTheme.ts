import { computed, ref } from 'vue';

/**
 * Composable para generar configuraciones de tema de ECharts.
 * Se adapta automáticamente al modo claro/oscuro.
 */
export function useEchartsTheme() {
  // Usar un ref reactivo que se actualiza cuando cambia la clase 'dark' en el HTML
  const isDarkMode = ref(false);

  // Observar cambios en la clase 'dark' del elemento HTML
  if (typeof window !== 'undefined') {
    // Inicializar valor
    isDarkMode.value = document.documentElement.classList.contains('dark');

    // Observar cambios usando MutationObserver
    const observer = new MutationObserver(() => {
      isDarkMode.value = document.documentElement.classList.contains('dark');
    });

    observer.observe(document.documentElement, {
      attributes: true,
      attributeFilter: ['class'],
    });
  }

  const isDark = computed(() => isDarkMode.value);
  const textColor = computed(() => (isDark.value ? '#f5f5f5' : '#373d3f'));
  const backgroundColor = computed(() =>
    isDark.value ? 'transparent' : 'transparent',
  );
  const borderColor = computed(() => (isDark.value ? '#374151' : '#e5e7eb'));

  const defaultColors = [
    '#2e93fa',
    '#e91e63',
    '#66bb6a',
    '#ffa726',
    '#ab47bc',
    '#26c6da',
    '#ef5350',
    '#ffee58',
  ];

  const getThemeConfig = () => ({
    textStyle: {
      color: textColor.value,
      fontFamily: 'Inter, system-ui, sans-serif',
    },
    backgroundColor: backgroundColor.value,
    color: defaultColors,
  });

  const getTooltipConfig = () => ({
    trigger: 'item',
    backgroundColor: isDark.value ? '#1f2937' : '#ffffff',
    borderColor: borderColor.value,
    borderWidth: 1,
    textStyle: {
      color: textColor.value,
    },
  });

  const getLegendConfig = (
    position: 'left' | 'right' | 'top' | 'bottom' = 'left',
  ) => ({
    orient:
      position === 'left' || position === 'right' ? 'vertical' : 'horizontal',
    [position]: position === 'left' || position === 'right' ? 10 : 'center',
    top: position === 'top' ? 10 : position === 'bottom' ? 'auto' : 'middle',
    bottom: position === 'bottom' ? 10 : 'auto',
    textStyle: {
      color: textColor.value,
    },
    formatter: (name: string) => name,
  });

  return {
    isDark,
    textColor,
    backgroundColor,
    borderColor,
    defaultColors,
    getThemeConfig,
    getTooltipConfig,
    getLegendConfig,
  };
}
