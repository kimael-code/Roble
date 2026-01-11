import { toQueryString } from '@/lib/utils';
import { computed, type ComputedRef } from 'vue';

/**
 * Genera una URL de exportación reactiva a partir de una URL base y un objeto de filtros.
 *
 * La URL generada incluirá una cadena de consulta (query string) con los filtros que no sean nulos,
 * indefinidos, vacíos, o un objeto/array vacío.
 *
 * @param exporterUrl - La URL base para el punto de enlace (endpoint) de exportación.
 * @param filters - Un objeto que contiene los filtros a aplicar. Las claves son los nombres de los
 *   filtros y los valores son sus, bueno, valores.
 * @returns Una propiedad computada (`ComputedRef<string>`) que contiene la URL de exportación final.
 *   Esta URL se actualiza automáticamente cuando cambian los filtros.
 */
export function useExportUrl(
  exporterUrl: string,
  filters: Record<string, any>,
): ComputedRef<string> {
  return computed(() => {
    const cleanFilters = Object.fromEntries(
      Object.entries(filters).filter(([, v]) => {
        if (v === null || v === undefined || v === '') return false;
        if (Array.isArray(v) && v.length === 0) return false;
        if (
          typeof v === 'object' &&
          !Array.isArray(v) &&
          Object.keys(v).length === 0
        )
          return false;
        return true;
      }),
    );

    const queryString = toQueryString(cleanFilters);
    return queryString ? `${exporterUrl}?${queryString}` : exporterUrl;
  });
}
