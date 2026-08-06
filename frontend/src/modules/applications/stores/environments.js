import { defineStore } from 'pinia';
import { ref } from 'vue';
import { environmentService } from '@/modules/applications/services/environmentService';

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

export const useEnvironmentsStore = defineStore('applicationEnvironments', () => {
  const application = ref(null);
  const environments = ref([]);
  const currentEnvironment = ref(null);
  const selectedEnvironment = ref(null);
  const summary = ref(null);
  const lastHealthCheck = ref(null);
  const meta = ref(null);
  const filters = ref({
    search: '',
    status: '',
    type: '',
    health_status: '',
    sort_by: 'type',
    sort_dir: 'asc',
    per_page: 20,
    page: 1,
  });
  const state = useAsyncState();

  async function fetchDashboard(applicationId) {
    state.loading.value = true;
    state.clearMessages();
    try {
      const { data } = await environmentService.dashboard(applicationId);
      application.value = data.data?.application ?? null;
      environments.value = data.data?.environments ?? [];
      currentEnvironment.value = data.data?.current_environment ?? null;
      summary.value = data.data?.summary ?? null;
      return data.data;
    } catch (err) {
      state.applyError(err, 'Unable to load environment dashboard');
      throw err;
    } finally {
      state.loading.value = false;
    }
  }

  async function fetchEnvironments(applicationId, overrides = {}) {
    state.loading.value = true;
    state.clearMessages();
    filters.value = { ...filters.value, ...overrides };
    try {
      const params = Object.fromEntries(Object.entries(filters.value).filter(([, v]) => v !== '' && v != null));
      const { data } = await environmentService.list(applicationId, params);
      environments.value = data.data?.environments?.items ?? [];
      meta.value = data.data?.environments?.meta ?? null;
      application.value = data.data?.application ?? application.value;
      return environments.value;
    } catch (err) {
      state.applyError(err, 'Unable to load environments');
      throw err;
    } finally {
      state.loading.value = false;
    }
  }

  async function fetchEnvironment(applicationId, environmentId) {
    state.loading.value = true;
    state.clearMessages();
    try {
      const { data } = await environmentService.get(applicationId, environmentId);
      selectedEnvironment.value = data.data?.environment ?? null;
      return selectedEnvironment.value;
    } catch (err) {
      state.applyError(err, 'Unable to load environment');
      throw err;
    } finally {
      state.loading.value = false;
    }
  }

  async function createEnvironment(applicationId, payload) {
    state.saving.value = true;
    state.clearMessages();
    try {
      const { data } = await environmentService.create(applicationId, payload);
      state.successMessage.value = data.message;
      return data.data?.environment;
    } catch (err) {
      state.applyError(err, 'Unable to create environment');
      throw err;
    } finally {
      state.saving.value = false;
    }
  }

  async function updateEnvironment(applicationId, environmentId, payload) {
    state.saving.value = true;
    state.clearMessages();
    try {
      const { data } = await environmentService.update(applicationId, environmentId, payload);
      selectedEnvironment.value = data.data?.environment ?? selectedEnvironment.value;
      state.successMessage.value = data.message;
      return data.data?.environment;
    } catch (err) {
      state.applyError(err, 'Unable to update environment');
      throw err;
    } finally {
      state.saving.value = false;
    }
  }

  async function deleteEnvironment(applicationId, environmentId) {
    state.saving.value = true;
    state.clearMessages();
    try {
      const { data } = await environmentService.remove(applicationId, environmentId);
      state.successMessage.value = data.message;
      return data;
    } catch (err) {
      state.applyError(err, 'Unable to delete environment');
      throw err;
    } finally {
      state.saving.value = false;
    }
  }

  async function switchEnvironment(applicationId, environmentId) {
    state.saving.value = true;
    state.clearMessages();
    try {
      const { data } = await environmentService.switchTo(applicationId, environmentId);
      currentEnvironment.value = data.data?.environment ?? null;
      state.successMessage.value = data.message;
      await fetchDashboard(applicationId);
      return data.data?.environment;
    } catch (err) {
      state.applyError(err, 'Unable to switch environment');
      throw err;
    } finally {
      state.saving.value = false;
    }
  }

  async function runHealthCheck(applicationId, environmentId) {
    state.saving.value = true;
    state.clearMessages();
    try {
      const { data } = await environmentService.healthCheck(applicationId, environmentId);
      lastHealthCheck.value = data.data?.check ?? null;
      selectedEnvironment.value = data.data?.environment ?? selectedEnvironment.value;
      state.successMessage.value = data.message;
      await fetchDashboard(applicationId);
      return data.data;
    } catch (err) {
      state.applyError(err, 'Health check failed');
      throw err;
    } finally {
      state.saving.value = false;
    }
  }

  return {
    application,
    environments,
    currentEnvironment,
    selectedEnvironment,
    summary,
    lastHealthCheck,
    meta,
    filters,
    ...state,
    fetchDashboard,
    fetchEnvironments,
    fetchEnvironment,
    createEnvironment,
    updateEnvironment,
    deleteEnvironment,
    switchEnvironment,
    runHealthCheck,
  };
});
