import { MaybeRefOrGetter, toValue } from 'vue';

/**
 * Composable con utilidades para manipulación de cadenas de texto
 */
export function useStringUtils() {
  /**
   * Remueve el punto final de una cadena de texto
   * @param text - Texto a procesar (puede ser ref, getter o string plano)
   * @returns Texto sin el punto final si existía
   */
  const removeEndDot = (text: MaybeRefOrGetter<string>): string => {
    const value = toValue(text);
    return value.replace(/\.$/, '');
  };

  /**
   * Remueve el punto final solo si existe y opcionalmente agrega otro texto
   * @param text - Texto a procesar
   * @param replaceWith - Texto opcional para reemplazar el punto
   * @returns Texto procesado
   */
  const removeEndDotAndReplace = (text: MaybeRefOrGetter<string>, replaceWith: string = ''): string => {
    const value = toValue(text);
    return value.replace(/\.$/, replaceWith);
  };

  /**
   * Verifica si una cadena termina con punto
   * @param text - Texto a verificar
   * @returns true si termina con punto
   */
  const endsWithDot = (text: MaybeRefOrGetter<string>): boolean => {
    const value = toValue(text);
    return value.endsWith('.');
  };

  /**
   * Capitaliza la primera letra de una cadena y convierte el resto a minúsculas
   * @param text - Texto a procesar (puede ser ref, getter o string plano)
   * @returns Texto con primera letra en mayúscula y el resto en minúscula
   */
  const capitalizeFirstLetter = (text: MaybeRefOrGetter<string>): string => {
    const value = toValue(text);
    if (!value) return '';
    return value.charAt(0).toUpperCase() + value.slice(1).toLowerCase();
  };

  return {
    removeEndDot,
    removeEndDotAndReplace,
    endsWithDot,
    capitalizeFirstLetter,
  };
}

export default useStringUtils;
