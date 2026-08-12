import { defineStore } from 'pinia';
import { computed, ref } from 'vue';
import { authService } from '@/modules/authentication/services/authService';
import { setAuthToken } from '@/services/api';

const TOKEN_KEY = 'mamp_access_token';
const REMEMBER_KEY = 'mamp_remember_me';

function readToken() {
  return localStorage.getItem(TOKEN_KEY) || sessionStorage.getItem(TOKEN_KEY);
}

function persistToken(token, remember) {
  localStorage.removeItem(TOKEN_KEY);
  sessionStorage.removeItem(TOKEN_KEY);

  if (!token) {
    setAuthToken(null);
    return;
  }

  if (remember) {
    localStorage.setItem(TOKEN_KEY, token);
  } else {
    sessionStorage.setItem(TOKEN_KEY, token);
  }

  setAuthToken(token);
}

export const useAuthStore = defineStore('auth', () => {
  const user = ref(null);
  const token = ref(readToken());
  const initialized = ref(false);
  const loading = ref(false);
  const error = ref(null);
  const rememberMe = ref(localStorage.getItem(REMEMBER_KEY) === '1');

  const isAuthenticated = computed(() => Boolean(user.value));
  const isEmailVerified = computed(() => Boolean(user.value?.email_verified));
  const permissions = computed(() => {
    const list = user.value?.permissions;
    return Array.isArray(list) ? list : [];
  });
  const roles = computed(() => {
    const list = user.value?.roles;
    return Array.isArray(list) ? list : [];
  });
  const isSuperAdmin = computed(() => roles.value.includes('super-admin'));
  const isPortalCustomer = computed(() => {
    if (!user.value) return false;
    if (user.value.is_portal_customer) return true;
    return Boolean(user.value.customer_id) && roles.value.includes('customer');
  });

  function hasPermission(permission) {
    if (!permission) {
      return true;
    }

    if (isSuperAdmin.value) {
      return true;
    }

    return permissions.value.includes(permission);
  }

  function hasAnyPermission(...required) {
    const names = required.flat().filter(Boolean);
    if (!names.length) {
      return true;
    }

    if (isSuperAdmin.value) {
      return true;
    }

    return names.some((name) => permissions.value.includes(name));
  }

  function hasAllPermissions(...required) {
    const names = required.flat().filter(Boolean);
    if (!names.length) {
      return true;
    }

    if (isSuperAdmin.value) {
      return true;
    }

    return names.every((name) => permissions.value.includes(name));
  }

  if (token.value) {
    setAuthToken(token.value);
  }

  async function initialize() {
    if (initialized.value) {
      return;
    }

    loading.value = true;
    error.value = null;

    try {
      const { data } = await authService.me();
      user.value = data.data?.user ?? null;
    } catch {
      user.value = null;
      token.value = null;
      persistToken(null, false);
    } finally {
      initialized.value = true;
      loading.value = false;
    }
  }

  async function login(credentials) {
    loading.value = true;
    error.value = null;

    try {
      const remember = Boolean(credentials.remember);
      rememberMe.value = remember;
      localStorage.setItem(REMEMBER_KEY, remember ? '1' : '0');

      const { data } = await authService.login(credentials);
      user.value = data.data?.user ?? null;
      token.value = data.data?.token ?? null;
      persistToken(token.value, remember);
      initialized.value = true;
      return data;
    } catch (err) {
      error.value = err.message || 'Unable to login';
      throw err;
    } finally {
      loading.value = false;
    }
  }

  async function logout() {
    loading.value = true;
    error.value = null;

    try {
      await authService.logout();
    } finally {
      user.value = null;
      token.value = null;
      persistToken(null, false);
      loading.value = false;
    }
  }

  async function logoutAll() {
    loading.value = true;
    try {
      await authService.logoutAll();
    } finally {
      user.value = null;
      token.value = null;
      persistToken(null, false);
      loading.value = false;
    }
  }

  async function refreshSession(rotateToken = false) {
    loading.value = true;
    error.value = null;

    try {
      const { data } = await authService.refresh({ rotate_token: rotateToken });
      user.value = data.data?.user ?? null;

      if (data.data?.token) {
        token.value = data.data.token;
        persistToken(token.value, rememberMe.value);
      }

      return data;
    } catch (err) {
      error.value = err.message || 'Unable to refresh session';
      throw err;
    } finally {
      loading.value = false;
    }
  }

  async function changePassword(payload) {
    loading.value = true;
    error.value = null;

    try {
      const { data } = await authService.changePassword(payload);
      user.value = data.data?.user ?? user.value;
      token.value = null;
      persistToken(null, false);
      return data;
    } catch (err) {
      error.value = err.message || 'Unable to change password';
      throw err;
    } finally {
      loading.value = false;
    }
  }

  return {
    user,
    token,
    initialized,
    loading,
    error,
    rememberMe,
    isAuthenticated,
    isEmailVerified,
    permissions,
    roles,
    isSuperAdmin,
    isPortalCustomer,
    hasPermission,
    hasAnyPermission,
    hasAllPermissions,
    initialize,
    login,
    logout,
    logoutAll,
    refreshSession,
    changePassword,
  };
});
