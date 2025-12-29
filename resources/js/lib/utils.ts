import { InertiaLinkProps } from '@inertiajs/vue3';
import { clsx, type ClassValue } from 'clsx';
import { twMerge } from 'tailwind-merge';

export function cn(...inputs: ClassValue[]) {
  return twMerge(clsx(inputs));
}

export function urlIsActive(
  urlToCheck: NonNullable<InertiaLinkProps['href']>,
  currentUrl: string,
) {
  return (
    toUrl(urlToCheck) === currentUrl ||
    currentUrl.search(
      new RegExp(`(${toUrl(urlToCheck)})/?[^-]|(${toUrl(urlToCheck)})$`),
    ) === 0
  );
}

export function toUrl(href: NonNullable<InertiaLinkProps['href']>) {
  return typeof href === 'string' ? href : href?.url;
}

export function appendToSearchParams(
  params: URLSearchParams,
  key: string,
  value: any,
): void {
  if (Array.isArray(value)) {
    value.forEach((item) => {
      params.append(`${key}[]`, String(item));
    });
  } else if (value !== null && typeof value === 'object') {
    Object.entries(value).forEach(([nestedKey, nestedValue]) => {
      appendToSearchParams(params, `${key}[${nestedKey}]`, nestedValue);
    });
  } else if (value !== null && value !== undefined && value !== '') {
    params.append(key, String(value));
  }
}

export function toQueryString(obj: Record<string, any>): string {
  const params = new URLSearchParams();
  Object.entries(obj).forEach(([key, value]) => {
    appendToSearchParams(params, key, value);
  });
  return params.toString();
}
