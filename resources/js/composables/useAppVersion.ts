import { usePage } from '@inertiajs/vue3';

export default function useAppVersion() {
  const page = usePage();
  const version = page.props.version as string;

  return {
    version,
    shortVersion: version?.split('+')[0] || version, // Solo MAJOR.MINOR.PATCH
    fullVersion: version, // Incluye hash si existe
  };
}
