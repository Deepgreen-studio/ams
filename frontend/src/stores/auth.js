import { defineStore } from 'pinia';
import { computed, ref } from 'vue';
import { authService } from '@/services/authService';

export const useAuthStore = defineStore('auth', () => {
  const user = ref(null);
  const initialized = ref(false);
  const loading = ref(false);
  const error = ref(null);

  const isAuthenticated = computed(() => Boolean(user.value));

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
    } finally {
      initialized.value = true;
      loading.value = false;
    }
  }

  async function login(credentials) {
    loading.value = true;
    error.value = null;

    try {
      const { data } = await authService.login(credentials);
      user.value = data.data?.user ?? null;
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
      loading.value = false;
    }
  }

  return {
    user,
    initialized,
    loading,
    error,
    isAuthenticated,
    initialize,
    login,
    logout,
  };
});
