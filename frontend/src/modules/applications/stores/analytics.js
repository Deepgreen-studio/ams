import { defineStore } from 'pinia';
import { ref } from 'vue';
import { analyticsService } from '@/modules/applications/services/analyticsService';

function useAsyncState() {
  const loading = ref(false);
  const saving = ref(false);
  const error = ref(null);
  const fieldErrors = ref({});
  const successMessage = ref(null);

  function clearMessages() {
    error.value = null;
    fieldErrors.value = {};
    successMessage.value = null;
  }

  function applyError(err, fallback) {
    error.value = err?.message || fallback;
    fieldErrors.value = err?.errors || {};
  }

  return { loading, saving, error, fieldErrors, successMessage, clearMessages, applyError };
}

export const useAnalyticsStore = defineStore('applicationAnalytics', () => {
  const application = ref(null);
  const summary = ref(null);
  const latest = ref(null);
  const trend = ref(null);
  const topCountries = ref([]);
  const topDevices = ref([]);
  const trends = ref(null);
  const heatmap = ref(null);
  const countries = ref([]);
  const devices = ref([]);
  const osVersions = ref([]);
  const state = useAsyncState();

  async function fetchDashboard(applicationId, params = {}) {
    state.loading.value = true;
    state.clearMessages();
    try {
      const { data } = await analyticsService.dashboard(applicationId, params);
      application.value = data.data?.application ?? null;
      summary.value = data.data?.summary ?? null;
      latest.value = data.data?.latest ?? null;
      trend.value = data.data?.trend ?? null;
      topCountries.value = data.data?.top_countries ?? [];
      topDevices.value = data.data?.top_devices ?? [];
      return data.data;
    } catch (err) {
      state.applyError(err, 'Unable to load analytics dashboard');
      throw err;
    } finally {
      state.loading.value = false;
    }
  }

  async function fetchTrends(applicationId, params = {}) {
    state.loading.value = true;
    state.clearMessages();
    try {
      const { data } = await analyticsService.trends(applicationId, params);
      trends.value = data.data ?? null;
      return trends.value;
    } catch (err) {
      state.applyError(err, 'Unable to load trend analysis');
      throw err;
    } finally {
      state.loading.value = false;
    }
  }

  async function fetchHeatmap(applicationId, params = {}) {
    state.loading.value = true;
    state.clearMessages();
    try {
      const { data } = await analyticsService.heatmap(applicationId, params);
      heatmap.value = data.data ?? null;
      return heatmap.value;
    } catch (err) {
      state.applyError(err, 'Unable to load heatmap');
      throw err;
    } finally {
      state.loading.value = false;
    }
  }

  async function fetchCountries(applicationId, params = {}) {
    state.loading.value = true;
    state.clearMessages();
    try {
      const { data } = await analyticsService.countries(applicationId, params);
      countries.value = data.data?.countries ?? [];
      return countries.value;
    } catch (err) {
      state.applyError(err, 'Unable to load country statistics');
      throw err;
    } finally {
      state.loading.value = false;
    }
  }

  async function fetchDevices(applicationId, params = {}) {
    state.loading.value = true;
    state.clearMessages();
    try {
      const { data } = await analyticsService.devices(applicationId, params);
      devices.value = data.data?.devices ?? [];
      osVersions.value = data.data?.os_versions ?? [];
      return data.data;
    } catch (err) {
      state.applyError(err, 'Unable to load device statistics');
      throw err;
    } finally {
      state.loading.value = false;
    }
  }

  return {
    application,
    summary,
    latest,
    trend,
    topCountries,
    topDevices,
    trends,
    heatmap,
    countries,
    devices,
    osVersions,
    ...state,
    fetchDashboard,
    fetchTrends,
    fetchHeatmap,
    fetchCountries,
    fetchDevices,
  };
});
