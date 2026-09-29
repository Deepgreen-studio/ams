const PUBLIC_AUTH_PATHS = [
  '/auth/login',
  '/auth/forgot-password',
  '/auth/reset-password',
];

export const AUTH_NOTICE_KEY = 'ams_auth_notice';

const SESSION_AUTH_MESSAGES = new Set([
  'Unauthorized',
  'Unauthenticated',
  'Unauthenticated.',
]);

let handlingSessionExpiry = false;

export function isPublicAuthRequest(config) {
  const url = `${config?.url || ''}`;
  return PUBLIC_AUTH_PATHS.some((path) => url.includes(path));
}

export function isSessionAuthenticationFailure(error) {
  const status = error.response?.status;

  if (status === 419) {
    return true;
  }

  const data = error.response?.data ?? {};

  if (status === 403 && data.code === 'COMPANY_INACTIVE') {
    return true;
  }

  if (status !== 401) {
    return false;
  }

  if (data.code === 'UNAUTHENTICATED') {
    return true;
  }

  return SESSION_AUTH_MESSAGES.has(String(data.message || ''));
}

export async function expireClientSession(message) {
  if (handlingSessionExpiry) {
    return;
  }

  handlingSessionExpiry = true;

  if (typeof message === 'string' && message.trim() !== '') {
    sessionStorage.setItem(AUTH_NOTICE_KEY, message.trim());
  }

  try {
    const { useAuthStore } = await import('@/modules/authentication/stores/auth');
    const { default: router } = await import('@/router');
    const authStore = useAuthStore();
    const hadSession = Boolean(authStore.user || authStore.token);

    authStore.clearSession({ expired: hadSession });

    if (!hadSession) {
      return;
    }

    const current = router.currentRoute.value;
    const query = { ...current.query, reason: 'session' };
    const isProtected = current.matched.some((record) => record.meta.requiresAuth);

    if (isProtected && current.fullPath && !current.path.startsWith('/auth')) {
      query.redirect = current.fullPath;
    } else if (!isProtected) {
      delete query.redirect;
    }

    if (current.name === 'login' && current.query.reason === 'session') {
      return;
    }

    await router.replace({ name: 'login', query });
  } finally {
    handlingSessionExpiry = false;
  }
}
