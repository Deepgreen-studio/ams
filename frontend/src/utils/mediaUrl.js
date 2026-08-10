/**
 * Resolve public storage / media URLs for the SPA.
 *
 * Backend may return absolute APP_URL paths (e.g. http://ams.test/storage/...).
 * Those hosts are often unreachable from the Vite dev server — rewrite storage
 * URLs to same-origin /storage so the Vite proxy can serve them.
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
    const parsed = new URL(
      trimmed,
      typeof window !== 'undefined' ? window.location.origin : 'http://localhost',
    );
    const isStoragePath = parsed.pathname.startsWith('/storage/');

    if (!isStoragePath) {
      return trimmed.startsWith('http://') || trimmed.startsWith('https://')
        ? trimmed
        : `${parsed.pathname}${parsed.search}`;
    }

    // Always prefer same-origin storage paths in local Vite (empty API base).
    if (!apiBase) {
      return `${parsed.pathname}${parsed.search}`;
    }

    // Production / remote API: point storage at the API origin (not the /api/v1 suffix).
    const apiOrigin = new URL(
      apiBase,
      typeof window !== 'undefined' ? window.location.origin : apiBase,
    ).origin;

    // If the URL already points at the API origin, keep it; otherwise rewrite.
    if (parsed.origin === apiOrigin) {
      return trimmed;
    }

    return `${apiOrigin}${parsed.pathname}${parsed.search}`;
  } catch {
    // Raw disk path: avatars/foo.png
    if (!trimmed.startsWith('/') && !trimmed.startsWith('http')) {
      return `/storage/${trimmed.replace(/^\/+/, '')}`;
    }
    return trimmed;
  }
}
