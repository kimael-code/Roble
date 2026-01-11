import { DateTime } from 'luxon';

/**
 * Composable para manejar la descarga de gráficas.
 * Incluye el título de la gráfica y la fecha de descarga.
 */
export function useChartDownload() {
  /**
   * Descarga una gráfica como imagen PNG.
   * @param chartInstance - Instancia de ECharts
   * @param title - Título de la gráfica
   * @param filename - Nombre del archivo (sin extensión)
   */
  const downloadChart = (
    chartInstance: any,
    title: string,
    filename: string,
  ) => {
    if (!chartInstance) {
      console.error('No se pudo descargar la gráfica: instancia no disponible');
      return;
    }

    try {
      // Obtener la fecha actual
      const currentDate = DateTime.now()
        .setLocale('es')
        .toFormat('dd/MM/yyyy HH:mm');

      // Obtener la imagen base64 de la gráfica
      const imageUrl = chartInstance.getDataURL({
        type: 'png',
        pixelRatio: 2,
        backgroundColor: '#fff',
      });

      // Crear un canvas temporal para agregar el título y la fecha
      const canvas = document.createElement('canvas');
      const ctx = canvas.getContext('2d');

      if (!ctx) {
        console.error('No se pudo crear el contexto del canvas');
        return;
      }

      const img = new Image();
      img.onload = () => {
        // Configurar el tamaño del canvas (imagen + espacio para título y fecha)
        const headerHeight = 80;
        canvas.width = img.width;
        canvas.height = img.height + headerHeight;

        // Fondo blanco
        ctx.fillStyle = '#ffffff';
        ctx.fillRect(0, 0, canvas.width, canvas.height);

        // Dibujar el título
        ctx.fillStyle = '#1f2937';
        ctx.font = 'bold 24px Inter, system-ui, sans-serif';
        ctx.textAlign = 'center';
        ctx.fillText(title, canvas.width / 2, 35);

        // Dibujar la fecha
        ctx.fillStyle = '#6b7280';
        ctx.font = '16px Inter, system-ui, sans-serif';
        ctx.fillText(`Generado el ${currentDate}`, canvas.width / 2, 60);

        // Dibujar la gráfica
        ctx.drawImage(img, 0, headerHeight);

        // Descargar la imagen
        const link = document.createElement('a');
        link.download = `${filename}_${DateTime.now().toFormat('yyyyMMdd_HHmmss')}.png`;
        link.href = canvas.toDataURL('image/png');
        link.click();
      };

      img.src = imageUrl;
    } catch (error) {
      console.error('Error al descargar la gráfica:', error);
    }
  };

  return {
    downloadChart,
  };
}
