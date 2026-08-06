import { defineStore } from 'pinia';
import { ref } from 'vue';
import { syncService } from '@/modules/sync/services/syncService';

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

export const useSyncStore = defineStore('sync', () => {
  const dashboard = ref(null);
  const configs = ref([]);
  const configsMeta = ref(null);
  const currentConfig = ref(null);
  const runs = ref([]);
  const runsMeta = ref(null);
  const currentRun = ref(null);
  const logs = ref([]);
  const logsMeta = ref(null);
  const filters = ref({
    search: '',
    direction: '',
    trigger_type: '',
    status: '',
    per_page: 10,
    page: 1,
  });
  const state = useAsyncState();

  async function fetchDashboard(params = {}) {
    state.loading.value = true;
    state.clearMessages();
    try {
      const { data } = await syncService.dashboard(params);
      dashboard.value = data.data ?? null;
      return dashboard.value;
    } catch (err) {
      state.applyError(err, 'Unable to load sync dashboard');
      throw err;
    } finally {
      state.loading.value = false;
    }
  }

  async function fetchConfigs(overrides = {}) {
    state.loading.value = true;
    state.clearMessages();
    filters.value = { ...filters.value, ...overrides };
    try {
      const params = Object.fromEntries(
        Object.entries(filters.value).filter(([, v]) => v !== '' && v != null),
      );
      const { data } = await syncService.listConfigs(params);
      configs.value = data.data?.configs?.items ?? [];
      configsMeta.value = data.data?.configs?.meta ?? null;
    } catch (err) {
      state.applyError(err, 'Unable to load sync configs');
      throw err;
    } finally {
      state.loading.value = false;
    }
  }

  async function fetchConfig(id) {
    state.loading.value = true;
    state.clearMessages();
    try {
      const { data } = await syncService.getConfig(id);
      currentConfig.value = data.data?.config ?? null;
      return currentConfig.value;
    } catch (err) {
      state.applyError(err, 'Unable to load sync config');
      throw err;
    } finally {
      state.loading.value = false;
    }
  }

  async function createConfig(payload) {
    state.saving.value = true;
    state.clearMessages();
    try {
      const { data } = await syncService.createConfig(payload);
      state.successMessage.value = data.message;
      return data.data?.config;
    } catch (err) {
      state.applyError(err, 'Unable to create sync config');
      throw err;
    } finally {
      state.saving.value = false;
    }
  }

  async function updateConfig(id, payload) {
    state.saving.value = true;
    state.clearMessages();
    try {
      const { data } = await syncService.updateConfig(id, payload);
      currentConfig.value = data.data?.config ?? currentConfig.value;
      state.successMessage.value = data.message;
      return data.data?.config;
    } catch (err) {
      state.applyError(err, 'Unable to update sync config');
      throw err;
    } finally {
      state.saving.value = false;
    }
  }

  async function deleteConfig(id) {
    state.saving.value = true;
    state.clearMessages();
    try {
      const { data } = await syncService.removeConfig(id);
      state.successMessage.value = data.message;
      return data;
    } catch (err) {
      state.applyError(err, 'Unable to delete sync config');
      throw err;
    } finally {
      state.saving.value = false;
    }
  }

  async function runSync(id, payload = {}) {
    state.saving.value = true;
    state.clearMessages();
    try {
      const { data } = await syncService.run(id, payload);
      currentRun.value = data.data?.run ?? null;
      currentConfig.value = data.data?.config ?? currentConfig.value;
      state.successMessage.value = data.message;
      return data.data;
    } catch (err) {
      state.applyError(err, 'Unable to start sync');
      throw err;
    } finally {
      state.saving.value = false;
    }
  }

  async function fetchRuns(params = {}) {
    state.loading.value = true;
    state.clearMessages();
    try {
      const query = Object.fromEntries(Object.entries(params).filter(([, v]) => v !== '' && v != null));
      const { data } = await syncService.listRuns(query);
      runs.value = data.data?.runs?.items ?? [];
      runsMeta.value = data.data?.runs?.meta ?? null;
    } catch (err) {
      state.applyError(err, 'Unable to load sync history');
      throw err;
    } finally {
      state.loading.value = false;
    }
  }

  async function fetchRun(id) {
    state.loading.value = true;
    try {
      const { data } = await syncService.getRun(id);
      currentRun.value = data.data?.run ?? null;
      return currentRun.value;
    } catch (err) {
      state.applyError(err, 'Unable to load sync run');
      throw err;
    } finally {
      state.loading.value = false;
    }
  }

  async function fetchLogs(params = {}) {
    state.loading.value = true;
    state.clearMessages();
    try {
      const query = Object.fromEntries(Object.entries(params).filter(([, v]) => v !== '' && v != null));
      const { data } = await syncService.listLogs(query);
      logs.value = data.data?.logs?.items ?? [];
      logsMeta.value = data.data?.logs?.meta ?? null;
    } catch (err) {
      state.applyError(err, 'Unable to load sync logs');
      throw err;
    } finally {
      state.loading.value = false;
    }
  }

  return {
    dashboard,
    configs,
    configsMeta,
    currentConfig,
    runs,
    runsMeta,
    currentRun,
    logs,
    logsMeta,
    filters,
    ...state,
    fetchDashboard,
    fetchConfigs,
    fetchConfig,
    createConfig,
    updateConfig,
    deleteConfig,
    runSync,
    fetchRuns,
    fetchRun,
    fetchLogs,
  };
});
