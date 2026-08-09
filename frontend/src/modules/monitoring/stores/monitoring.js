import { defineStore } from 'pinia';
import { ref } from 'vue';
import { monitoringService } from '@/modules/monitoring/services/monitoringService';

function useAsyncState() {
  const loading = ref(false);
  const saving = ref(false);
  const error = ref(null);
  const successMessage = ref(null);

  function clearMessages() {
    error.value = null;
    successMessage.value = null;
  }

  function applyError(err, fallback) {
    error.value = err?.message || fallback;
  }

  return { loading, saving, error, successMessage, clearMessages, applyError };
}

export const useMonitoringStore = defineStore('monitoring', () => {
  const dashboard = ref(null);
  const apiMonitor = ref(null);
  const webhookMonitor = ref(null);
  const queueMonitor = ref(null);
  const realtime = ref(null);
  const integrations = ref(null);
  const timeline = ref([]);
  const timelineMeta = ref(null);
  const history = ref([]);
  const alerts = ref([]);
  const alertsMeta = ref(null);
  const alertEvents = ref([]);
  const alertEventsMeta = ref(null);
  const state = useAsyncState();

  async function fetchDashboard(params = {}) {
    state.loading.value = true;
    state.clearMessages();
    try {
      const { data } = await monitoringService.dashboard(params);
      dashboard.value = data.data ?? null;
      return dashboard.value;
    } catch (err) {
      state.applyError(err, 'Unable to load health dashboard');
      throw err;
    } finally {
      state.loading.value = false;
    }
  }

  async function fetchApiMonitor(params = {}) {
    state.loading.value = true;
    try {
      const { data } = await monitoringService.apiMonitor(params);
      apiMonitor.value = data.data ?? null;
    } catch (err) {
      state.applyError(err, 'Unable to load API monitor');
      throw err;
    } finally {
      state.loading.value = false;
    }
  }

  async function fetchWebhookMonitor(params = {}) {
    state.loading.value = true;
    try {
      const { data } = await monitoringService.webhookMonitor(params);
      webhookMonitor.value = data.data ?? null;
    } catch (err) {
      state.applyError(err, 'Unable to load webhook monitor');
      throw err;
    } finally {
      state.loading.value = false;
    }
  }

  async function fetchQueueMonitor() {
    state.loading.value = true;
    try {
      const { data } = await monitoringService.queueHealth();
      queueMonitor.value = data.data ?? null;
    } catch (err) {
      state.applyError(err, 'Unable to load queue monitor');
      throw err;
    } finally {
      state.loading.value = false;
    }
  }

  async function fetchRealtime(params = {}) {
    state.loading.value = true;
    state.clearMessages();
    try {
      const { data } = await monitoringService.realtime(params);
      realtime.value = data.data ?? null;
      return realtime.value;
    } catch (err) {
      state.applyError(err, 'Unable to load real-time status');
      throw err;
    } finally {
      state.loading.value = false;
    }
  }

  async function fetchIntegrations(params = {}) {
    state.loading.value = true;
    try {
      const { data } = await monitoringService.integrations(params);
      integrations.value = data.data ?? null;
    } catch (err) {
      state.applyError(err, 'Unable to load integration status');
      throw err;
    } finally {
      state.loading.value = false;
    }
  }

  async function fetchTimeline(params = {}) {
    state.loading.value = true;
    try {
      const { data } = await monitoringService.timeline(params);
      timeline.value = data.data?.items ?? [];
      timelineMeta.value = data.data?.meta ?? null;
    } catch (err) {
      state.applyError(err, 'Unable to load incident timeline');
      throw err;
    } finally {
      state.loading.value = false;
    }
  }

  async function fetchHistory(params = {}) {
    state.loading.value = true;
    try {
      const { data } = await monitoringService.responseHistory(params);
      history.value = data.data?.history ?? [];
    } catch (err) {
      state.applyError(err, 'Unable to load response history');
      throw err;
    } finally {
      state.loading.value = false;
    }
  }

  async function capture(payload = {}) {
    state.saving.value = true;
    state.clearMessages();
    try {
      const { data } = await monitoringService.capture(payload);
      state.successMessage.value = data.message;
      return data.data;
    } catch (err) {
      state.applyError(err, 'Unable to capture snapshot');
      throw err;
    } finally {
      state.saving.value = false;
    }
  }

  async function fetchAlerts(params = {}) {
    state.loading.value = true;
    try {
      const { data } = await monitoringService.listAlerts(params);
      alerts.value = data.data?.alerts?.items ?? [];
      alertsMeta.value = data.data?.alerts?.meta ?? null;
    } catch (err) {
      state.applyError(err, 'Unable to load alerts');
      throw err;
    } finally {
      state.loading.value = false;
    }
  }

  async function createAlert(payload) {
    state.saving.value = true;
    state.clearMessages();
    try {
      const { data } = await monitoringService.createAlert(payload);
      state.successMessage.value = data.message;
      return data.data?.alert;
    } catch (err) {
      state.applyError(err, 'Unable to create alert');
      throw err;
    } finally {
      state.saving.value = false;
    }
  }

  async function updateAlert(id, payload) {
    state.saving.value = true;
    state.clearMessages();
    try {
      const { data } = await monitoringService.updateAlert(id, payload);
      state.successMessage.value = data.message;
      return data.data?.alert;
    } catch (err) {
      state.applyError(err, 'Unable to update alert');
      throw err;
    } finally {
      state.saving.value = false;
    }
  }

  async function deleteAlert(id) {
    state.saving.value = true;
    state.clearMessages();
    try {
      const { data } = await monitoringService.removeAlert(id);
      state.successMessage.value = data.message;
    } catch (err) {
      state.applyError(err, 'Unable to delete alert');
      throw err;
    } finally {
      state.saving.value = false;
    }
  }

  async function fetchAlertEvents(params = {}) {
    state.loading.value = true;
    try {
      const { data } = await monitoringService.listAlertEvents(params);
      alertEvents.value = data.data?.events?.items ?? [];
      alertEventsMeta.value = data.data?.events?.meta ?? null;
    } catch (err) {
      state.applyError(err, 'Unable to load alert events');
      throw err;
    } finally {
      state.loading.value = false;
    }
  }

  return {
    dashboard,
    apiMonitor,
    webhookMonitor,
    queueMonitor,
    realtime,
    integrations,
    timeline,
    timelineMeta,
    history,
    alerts,
    alertsMeta,
    alertEvents,
    alertEventsMeta,
    ...state,
    fetchDashboard,
    fetchApiMonitor,
    fetchWebhookMonitor,
    fetchQueueMonitor,
    fetchRealtime,
    fetchIntegrations,
    fetchTimeline,
    fetchHistory,
    capture,
    fetchAlerts,
    createAlert,
    updateAlert,
    deleteAlert,
    fetchAlertEvents,
  };
});
