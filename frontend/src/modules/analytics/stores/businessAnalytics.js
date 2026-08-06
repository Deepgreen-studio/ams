import { defineStore } from 'pinia';
import { ref } from 'vue';
import { analyticsService } from '@/modules/analytics/services/analyticsService';

function extractError(error, fallback = 'Request failed') {
  return error?.response?.data?.message || error?.message || fallback;
}

export const useBusinessAnalyticsStore = defineStore('businessAnalytics', () => {
  const overview = ref(null);
  const customers = ref(null);
  const revenue = ref(null);
  const applications = ref(null);
  const growth = ref(null);
  const forecast = ref(null);
  const loading = ref(false);
  const saving = ref(false);
  const error = ref(null);
  const successMessage = ref(null);

  function clearMessages() {
    error.value = null;
    successMessage.value = null;
  }

  async function fetchOverview(params = {}) {
    loading.value = true;
    clearMessages();
    try {
      const { data } = await analyticsService.businessOverview(params);
      overview.value = data.data ?? null;
      return overview.value;
    } catch (err) {
      error.value = extractError(err, 'Unable to load business overview');
      throw err;
    } finally {
      loading.value = false;
    }
  }

  async function fetchCustomers(params = {}) {
    loading.value = true;
    clearMessages();
    try {
      const { data } = await analyticsService.businessCustomers(params);
      customers.value = data.data ?? null;
      return customers.value;
    } catch (err) {
      error.value = extractError(err, 'Unable to load customer analytics');
      throw err;
    } finally {
      loading.value = false;
    }
  }

  async function fetchRevenue(params = {}) {
    loading.value = true;
    clearMessages();
    try {
      const { data } = await analyticsService.businessRevenue(params);
      revenue.value = data.data ?? null;
      return revenue.value;
    } catch (err) {
      error.value = extractError(err, 'Unable to load revenue analytics');
      throw err;
    } finally {
      loading.value = false;
    }
  }

  async function fetchApplications(params = {}) {
    loading.value = true;
    clearMessages();
    try {
      const { data } = await analyticsService.businessApplications(params);
      applications.value = data.data ?? null;
      return applications.value;
    } catch (err) {
      error.value = extractError(err, 'Unable to load application analytics');
      throw err;
    } finally {
      loading.value = false;
    }
  }

  async function fetchGrowth(params = {}) {
    loading.value = true;
    clearMessages();
    try {
      const { data } = await analyticsService.businessGrowth(params);
      growth.value = data.data ?? null;
      return growth.value;
    } catch (err) {
      error.value = extractError(err, 'Unable to load growth charts');
      throw err;
    } finally {
      loading.value = false;
    }
  }

  async function fetchForecast(params = {}) {
    loading.value = true;
    clearMessages();
    try {
      const { data } = await analyticsService.businessForecast(params);
      forecast.value = data.data ?? null;
      return forecast.value;
    } catch (err) {
      error.value = extractError(err, 'Unable to load forecast charts');
      throw err;
    } finally {
      loading.value = false;
    }
  }

  async function capture(payload = {}) {
    saving.value = true;
    clearMessages();
    try {
      const { data } = await analyticsService.businessCapture(payload);
      successMessage.value = data.message || 'Snapshot captured.';
      return data.data;
    } catch (err) {
      error.value = extractError(err, 'Unable to capture snapshot');
      throw err;
    } finally {
      saving.value = false;
    }
  }

  return {
    overview,
    customers,
    revenue,
    applications,
    growth,
    forecast,
    loading,
    saving,
    error,
    successMessage,
    fetchOverview,
    fetchCustomers,
    fetchRevenue,
    fetchApplications,
    fetchGrowth,
    fetchForecast,
    capture,
  };
});
