import { defineStore } from 'pinia';
import { ref } from 'vue';
import { monitoringService } from '@/modules/applications/services/monitoringService';

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

export const useMonitoringStore = defineStore('applicationMonitoring', () => {
  const application = ref(null);
  const crashSummary = ref(null);
  const recentCrashes = ref([]);
  const crashChart = ref(null);
  const currentCrash = ref(null);
  const healthLatest = ref(null);
  const healthMetrics = ref([]);
  const healthChart = ref(null);
  const devices = ref([]);
  const charts = ref(null);
  const alerts = ref([]);
  const alertEvents = ref([]);
  const state = useAsyncState();

  async function fetchCrashDashboard(applicationId, params = {}) {
    state.loading.value = true;
    state.clearMessages();
    try {
      const { data } = await monitoringService.crashDashboard(applicationId, params);
      application.value = data.data?.application ?? null;
      crashSummary.value = data.data?.summary ?? null;
      recentCrashes.value = data.data?.recent ?? [];
      crashChart.value = data.data?.chart ?? null;
      return data.data;
    } catch (err) {
      state.applyError(err, 'Unable to load crash dashboard');
      throw err;
    } finally {
      state.loading.value = false;
    }
  }

  async function fetchHealthDashboard(applicationId, params = {}) {
    state.loading.value = true;
    state.clearMessages();
    try {
      const { data } = await monitoringService.healthDashboard(applicationId, params);
      application.value = data.data?.application ?? null;
      healthLatest.value = data.data?.latest ?? null;
      healthMetrics.value = data.data?.metrics ?? [];
      healthChart.value = data.data?.chart ?? null;
      return data.data;
    } catch (err) {
      state.applyError(err, 'Unable to load health dashboard');
      throw err;
    } finally {
      state.loading.value = false;
    }
  }

  async function fetchCrash(applicationId, crashId) {
    state.loading.value = true;
    state.clearMessages();
    try {
      const { data } = await monitoringService.getCrash(applicationId, crashId);
      currentCrash.value = data.data?.crash ?? null;
      return currentCrash.value;
    } catch (err) {
      state.applyError(err, 'Unable to load crash details');
      throw err;
    } finally {
      state.loading.value = false;
    }
  }

  async function updateCrash(applicationId, crashId, payload) {
    state.saving.value = true;
    state.clearMessages();
    try {
      const { data } = await monitoringService.updateCrash(applicationId, crashId, payload);
      currentCrash.value = data.data?.crash ?? currentCrash.value;
      state.successMessage.value = data.message;
      return data.data?.crash;
    } catch (err) {
      state.applyError(err, 'Unable to update crash');
      throw err;
    } finally {
      state.saving.value = false;
    }
  }

  async function fetchDevices(applicationId) {
    state.loading.value = true;
    state.clearMessages();
    try {
      const { data } = await monitoringService.deviceStatistics(applicationId);
      devices.value = data.data?.devices ?? [];
      return devices.value;
    } catch (err) {
      state.applyError(err, 'Unable to load device statistics');
      throw err;
    } finally {
      state.loading.value = false;
    }
  }

  async function fetchCharts(applicationId, params = {}) {
    state.loading.value = true;
    state.clearMessages();
    try {
      const { data } = await monitoringService.charts(applicationId, params);
      charts.value = data.data ?? null;
      return charts.value;
    } catch (err) {
      state.applyError(err, 'Unable to load charts');
      throw err;
    } finally {
      state.loading.value = false;
    }
  }

  async function fetchAlerts(applicationId) {
    state.loading.value = true;
    state.clearMessages();
    try {
      const { data } = await monitoringService.alerts(applicationId);
      alerts.value = data.data?.alerts ?? [];
      alertEvents.value = data.data?.events ?? [];
      return data.data;
    } catch (err) {
      state.applyError(err, 'Unable to load alerts');
      throw err;
    } finally {
      state.loading.value = false;
    }
  }

  async function createAlert(applicationId, payload) {
    state.saving.value = true;
    state.clearMessages();
    try {
      const { data } = await monitoringService.createAlert(applicationId, payload);
      state.successMessage.value = data.message;
      await fetchAlerts(applicationId);
      return data.data?.alert;
    } catch (err) {
      state.applyError(err, 'Unable to create alert');
      throw err;
    } finally {
      state.saving.value = false;
    }
  }

  async function acknowledgeEvent(applicationId, eventId) {
    state.saving.value = true;
    state.clearMessages();
    try {
      const { data } = await monitoringService.acknowledgeAlertEvent(applicationId, eventId);
      state.successMessage.value = data.message;
      await fetchAlerts(applicationId);
      return data.data?.event;
    } catch (err) {
      state.applyError(err, 'Unable to acknowledge alert');
      throw err;
    } finally {
      state.saving.value = false;
    }
  }

  async function refreshHealth(applicationId) {
    state.saving.value = true;
    state.clearMessages();
    try {
      const { data } = await monitoringService.refreshHealth(applicationId);
      state.successMessage.value = data.message;
      await fetchHealthDashboard(applicationId);
      return data.data?.metric;
    } catch (err) {
      state.applyError(err, 'Unable to refresh health');
      throw err;
    } finally {
      state.saving.value = false;
    }
  }

  return {
    application,
    crashSummary,
    recentCrashes,
    crashChart,
    currentCrash,
    healthLatest,
    healthMetrics,
    healthChart,
    devices,
    charts,
    alerts,
    alertEvents,
    ...state,
    fetchCrashDashboard,
    fetchHealthDashboard,
    fetchCrash,
    updateCrash,
    fetchDevices,
    fetchCharts,
    fetchAlerts,
    createAlert,
    acknowledgeEvent,
    refreshHealth,
  };
});
