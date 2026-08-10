/**
 * Resolve public storage / media URLs for the SPA.
 *
 * Backend often returns absolute APP_URL paths (e.g. http://ams.test/storage/...).
 * In local Vite dev, /storage is proxied to the API — absolute APP_URL hosts may be unreachable.
 */
export function resolveMediaUrl(url) {
  if (url == null) {
    return '';
  }

  const trimmed = String(url).trim();
  if (!trimmed) {
    return '';
  }

  if (trimmed.startsWith('blob:') || trimmed.startsWith('data:')) {
    return trimmed;
  }

  const apiBase = import.meta.env.VITE_API_BASE_URL || '';

  try {
    const parsed = new URL(trimmed, typeof window !== 'undefined' ? window.location.origin : 'http://localhost');
    const isStoragePath = parsed.pathname.startsWith('/storage/');

    if (!isStoragePath) {
      return trimmed.startsWith('http://') || trimmed.startsWith('https://')
        ? trimmed
        : `${parsed.pathname}${parsed.search}`;
    }

    // Dev (empty API base): use same-origin path so Vite /storage proxy can serve the file.
    if (!apiBase) {
      return `${parsed.pathname}${parsed.search}`;
    }

    // Production / remote API: point storage at the API origin (not the /api/v1 suffix).
    const apiOrigin = new URL(apiBase, typeof window !== 'undefined' ? window.location.origin : apiBase).origin;
    return `${apiOrigin}${parsed.pathname}${parsed.search}`;
  } catch {
    return trimmed;
  }
}
