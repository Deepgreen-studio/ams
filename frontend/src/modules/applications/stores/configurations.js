import { defineStore } from 'pinia';
import { ref } from 'vue';
import { configurationService } from '@/modules/applications/services/configurationService';

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

export const useConfigurationsStore = defineStore('applicationConfigurations', () => {
  const application = ref(null);
  const configurations = ref([]);
  const catalog = ref({});
  const currentConfiguration = ref(null);
  const history = ref([]);
  const validationResult = ref(null);
  const state = useAsyncState();

  async function fetchManager(applicationId, environment = null) {
    state.loading.value = true;
    state.clearMessages();
    try {
      const params = environment ? { environment } : {};
      const { data } = await configurationService.manager(applicationId, params);
      application.value = data.data?.application ?? null;
      configurations.value = data.data?.configurations ?? [];
      catalog.value = data.data?.catalog ?? {};
      return data.data;
    } catch (err) {
      state.applyError(err, 'Unable to load configuration manager');
      throw err;
    } finally {
      state.loading.value = false;
    }
  }

  async function fetchConfiguration(applicationId, configurationId) {
    state.loading.value = true;
    state.clearMessages();
    try {
      const { data } = await configurationService.get(applicationId, configurationId);
      currentConfiguration.value = data.data?.configuration ?? null;
      return currentConfiguration.value;
    } catch (err) {
      state.applyError(err, 'Unable to load configuration');
      throw err;
    } finally {
      state.loading.value = false;
    }
  }

  async function createConfiguration(applicationId, payload) {
    state.saving.value = true;
    state.clearMessages();
    try {
      const { data } = await configurationService.create(applicationId, payload);
      state.successMessage.value = data.message;
      return data.data?.configuration;
    } catch (err) {
      state.applyError(err, 'Unable to create configuration');
      throw err;
    } finally {
      state.saving.value = false;
    }
  }

  async function updateConfiguration(applicationId, configurationId, payload) {
    state.saving.value = true;
    state.clearMessages();
    try {
      const { data } = await configurationService.update(applicationId, configurationId, payload);
      currentConfiguration.value = data.data?.configuration ?? currentConfiguration.value;
      state.successMessage.value = data.message;
      return data.data?.configuration;
    } catch (err) {
      state.applyError(err, 'Unable to update configuration');
      throw err;
    } finally {
      state.saving.value = false;
    }
  }

  async function validateConfiguration(applicationId, payload) {
    state.saving.value = true;
    state.clearMessages();
    try {
      const { data } = await configurationService.validate(applicationId, payload);
      validationResult.value = data.data ?? null;
      return validationResult.value;
    } catch (err) {
      state.applyError(err, 'Unable to validate configuration');
      throw err;
    } finally {
      state.saving.value = false;
    }
  }

  async function fetchHistory(applicationId, configurationId) {
    state.loading.value = true;
    state.clearMessages();
    try {
      const { data } = await configurationService.history(applicationId, configurationId);
      history.value = data.data?.history ?? [];
      return history.value;
    } catch (err) {
      state.applyError(err, 'Unable to load configuration history');
      throw err;
    } finally {
      state.loading.value = false;
    }
  }

  async function restoreHistory(applicationId, configurationId, historyId) {
    state.saving.value = true;
    state.clearMessages();
    try {
      const { data } = await configurationService.restoreHistory(applicationId, configurationId, historyId);
      currentConfiguration.value = data.data?.configuration ?? currentConfiguration.value;
      state.successMessage.value = data.message;
      await fetchHistory(applicationId, configurationId);
      return data.data?.configuration;
    } catch (err) {
      state.applyError(err, 'Unable to restore configuration history');
      throw err;
    } finally {
      state.saving.value = false;
    }
  }

  async function upsertFeatureFlag(applicationId, configurationId, payload) {
    state.saving.value = true;
    state.clearMessages();
    try {
      const { data } = await configurationService.upsertFeatureFlag(applicationId, configurationId, payload);
      currentConfiguration.value = data.data?.configuration ?? currentConfiguration.value;
      state.successMessage.value = data.message;
      return data.data?.configuration;
    } catch (err) {
      state.applyError(err, 'Unable to save feature flag');
      throw err;
    } finally {
      state.saving.value = false;
    }
  }

  async function toggleFeatureFlag(applicationId, configurationId, flagKey, enabled) {
    state.saving.value = true;
    state.clearMessages();
    try {
      const { data } = await configurationService.toggleFeatureFlag(applicationId, configurationId, flagKey, enabled);
      currentConfiguration.value = data.data?.configuration ?? currentConfiguration.value;
      state.successMessage.value = data.message;
      return data.data?.configuration;
    } catch (err) {
      state.applyError(err, 'Unable to toggle feature flag');
      throw err;
    } finally {
      state.saving.value = false;
    }
  }

  return {
    application,
    configurations,
    catalog,
    currentConfiguration,
    history,
    validationResult,
    ...state,
    fetchManager,
    fetchConfiguration,
    createConfiguration,
    updateConfiguration,
    validateConfiguration,
    fetchHistory,
    restoreHistory,
    upsertFeatureFlag,
    toggleFeatureFlag,
  };
});
