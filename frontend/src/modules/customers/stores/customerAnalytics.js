import { defineStore } from 'pinia';
import { ref } from 'vue';
import { customerAnalyticsService } from '@/modules/customers/services/customerAnalyticsService';

export const useCustomerAnalyticsStore = defineStore('customerAnalytics', () => {
  const dashboard = ref(null);
  const loading = ref(false);
  const refreshing = ref(false);
  const error = ref(null);
  const successMessage = ref(null);

  function clearMessages() {
    error.value = null;
    successMessage.value = null;
  }

  function applyError(err, fallback = 'Unexpected error') {
    error.value = err?.message || fallback;
  }

  async function fetchDashboard(customer, params = {}) {
    loading.value = true;
    clearMessages();
    try {
      const { data } = await customerAnalyticsService.dashboard({ customer, ...params });
      dashboard.value = data.data ?? null;
      return data;
    } catch (err) {
      applyError(err, 'Unable to load customer analytics');
      throw err;
    } finally {
      loading.value = false;
    }
  }

  async function refresh(customer) {
    refreshing.value = true;
    clearMessages();
    try {
      await customerAnalyticsService.refresh({ customer });
      successMessage.value = 'Analytics refreshed';
      return fetchDashboard(customer);
    } catch (err) {
      applyError(err, 'Unable to refresh analytics');
      throw err;
    } finally {
      refreshing.value = false;
    }
  }

  return {
    dashboard,
    loading,
    refreshing,
    error,
    successMessage,
    fetchDashboard,
    refresh,
    clearMessages,
  };
});
