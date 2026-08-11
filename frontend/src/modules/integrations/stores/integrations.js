import { defineStore } from 'pinia';
import { ref } from 'vue';
import { integrationService } from '@/modules/integrations/services/integrationService';

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

export const useIntegrationsStore = defineStore('integrations', () => {
  const integrations = ref([]);
  const meta = ref(null);
  const currentIntegration = ref(null);
  const lastResponse = ref(null);
  const history = ref([]);
  const historyMeta = ref(null);
  const filters = ref({
    search: '',
    status: '',
    type: '',
    authentication_type: '',
    health_status: '',
    company: '',
    trashed: '',
    sort_by: 'created_at',
    sort_dir: 'desc',
    per_page: 10,
    page: 1,
  });
  const state = useAsyncState();

  async function fetchIntegrations(overrides = {}) {
    state.loading.value = true;
    state.clearMessages();
    filters.value = { ...filters.value, ...overrides };
    try {
      const params = Object.fromEntries(Object.entries(filters.value).filter(([, v]) => v !== '' && v != null));
      const { data } = await integrationService.list(params);
      integrations.value = data.data?.integrations?.items ?? [];
      meta.value = data.data?.integrations?.meta ?? null;
    } catch (err) {
      state.applyError(err, 'Unable to load integrations');
      throw err;
    } finally {
      state.loading.value = false;
    }
  }

  async function fetchIntegration(id) {
    state.loading.value = true;
    state.clearMessages();
    try {
      const { data } = await integrationService.get(id);
      currentIntegration.value = data.data?.integration ?? null;
      return currentIntegration.value;
    } catch (err) {
      state.applyError(err, 'Unable to load integration');
      throw err;
    } finally {
      state.loading.value = false;
    }
  }

  async function createIntegration(payload) {
    state.saving.value = true;
    state.clearMessages();
    try {
      const { data } = await integrationService.create(payload);
      state.successMessage.value = data.message;
      return data.data?.integration;
    } catch (err) {
      state.applyError(err, 'Unable to create integration');
      throw err;
    } finally {
      state.saving.value = false;
    }
  }

  async function updateIntegration(id, payload) {
    state.saving.value = true;
    state.clearMessages();
    try {
      const { data } = await integrationService.update(id, payload);
      currentIntegration.value = data.data?.integration ?? currentIntegration.value;
      state.successMessage.value = data.message;
      return data.data?.integration;
    } catch (err) {
      state.applyError(err, 'Unable to update integration');
      throw err;
    } finally {
      state.saving.value = false;
    }
  }

  async function deleteIntegration(id) {
    state.saving.value = true;
    state.clearMessages();
    try {
      const { data } = await integrationService.remove(id);
      state.successMessage.value = data.message;
      return data;
    } catch (err) {
      state.applyError(err, 'Unable to delete integration');
      throw err;
    } finally {
      state.saving.value = false;
    }
  }

  async function restoreIntegration(id) {
    state.saving.value = true;
    state.clearMessages();
    try {
      const { data } = await integrationService.restore(id);
      currentIntegration.value = data.data?.integration ?? currentIntegration.value;
      state.successMessage.value = data.message;
      return data.data?.integration;
    } catch (err) {
      state.applyError(err, 'Unable to restore integration');
      throw err;
    } finally {
      state.saving.value = false;
    }
  }

  async function updateConfiguration(id, payload) {
    state.saving.value = true;
    state.clearMessages();
    try {
      const { data } = await integrationService.updateConfiguration(id, payload);
      currentIntegration.value = data.data?.integration ?? currentIntegration.value;
      state.successMessage.value = data.message;
      return data.data?.integration;
    } catch (err) {
      state.applyError(err, 'Unable to update configuration');
      throw err;
    } finally {
      state.saving.value = false;
    }
  }

  async function testConnection(id) {
    state.saving.value = true;
    state.clearMessages();
    try {
      const { data } = await integrationService.testConnection(id);
      currentIntegration.value = data.data?.integration ?? currentIntegration.value;
      lastResponse.value = data.data?.response ?? null;
      state.successMessage.value = data.message;
      return data.data;
    } catch (err) {
      state.applyError(err, 'Connection test failed');
      throw err;
    } finally {
      state.saving.value = false;
    }
  }

  async function testAuthentication(id) {
    state.saving.value = true;
    state.clearMessages();
    try {
      const { data } = await integrationService.testAuthentication(id);
      currentIntegration.value = data.data?.integration ?? currentIntegration.value;
      lastResponse.value = data.data?.response ?? null;
      state.successMessage.value = data.message;
      return data.data;
    } catch (err) {
      state.applyError(err, 'Authentication test failed');
      throw err;
    } finally {
      state.saving.value = false;
    }
  }

  async function executeRequest(id, payload, file = null) {
    state.saving.value = true;
    state.clearMessages();
    try {
      const { data } = await integrationService.execute(id, payload, file);
      lastResponse.value = data.data?.response ?? null;
      state.successMessage.value = data.message;
      return data.data;
    } catch (err) {
      state.applyError(err, 'Request execution failed');
      throw err;
    } finally {
      state.saving.value = false;
    }
  }

  async function fetchHistory(id, params = {}) {
    state.loading.value = true;
    state.clearMessages();
    try {
      const { data } = await integrationService.history(id, params);
      history.value = data.data?.history?.items ?? [];
      historyMeta.value = data.data?.history?.meta ?? null;
      return history.value;
    } catch (err) {
      state.applyError(err, 'Unable to load connection history');
      throw err;
    } finally {
      state.loading.value = false;
    }
  }

  function resetFilters() {
    filters.value = {
      search: '',
      status: '',
      type: '',
      authentication_type: '',
      health_status: '',
      company: '',
      trashed: '',
      sort_by: 'created_at',
      sort_dir: 'desc',
      per_page: 10,
      page: 1,
    };
  }

  return {
    integrations,
    meta,
    currentIntegration,
    lastResponse,
    history,
    historyMeta,
    filters,
    ...state,
    fetchIntegrations,
    fetchIntegration,
    createIntegration,
    updateIntegration,
    deleteIntegration,
    restoreIntegration,
    updateConfiguration,
    testConnection,
    testAuthentication,
    executeRequest,
    fetchHistory,
    resetFilters,
  };
});
