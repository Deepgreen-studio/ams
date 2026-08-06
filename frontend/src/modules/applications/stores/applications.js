import { defineStore } from 'pinia';
import { ref } from 'vue';
import { applicationService } from '@/modules/applications/services/applicationService';

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

export const useApplicationsStore = defineStore('applications', () => {
  const applications = ref([]);
  const meta = ref(null);
  const currentApplication = ref(null);
  const viewMode = ref('table');
  const filters = ref({
    search: '',
    status: '',
    platform: '',
    category: '',
    visibility: '',
    company: '',
    trashed: '',
    sort_by: 'created_at',
    sort_dir: 'desc',
    per_page: 10,
    page: 1,
  });
  const state = useAsyncState();

  async function fetchApplications(overrides = {}) {
    state.loading.value = true;
    state.clearMessages();
    filters.value = { ...filters.value, ...overrides };
    try {
      const params = Object.fromEntries(Object.entries(filters.value).filter(([, v]) => v !== '' && v != null));
      const { data } = await applicationService.list(params);
      applications.value = data.data?.applications?.items ?? [];
      meta.value = data.data?.applications?.meta ?? null;
    } catch (err) {
      state.applyError(err, 'Unable to load applications');
      throw err;
    } finally {
      state.loading.value = false;
    }
  }

  async function fetchApplication(id) {
    state.loading.value = true;
    state.clearMessages();
    try {
      const { data } = await applicationService.get(id);
      currentApplication.value = data.data?.application ?? null;
      return currentApplication.value;
    } catch (err) {
      state.applyError(err, 'Unable to load application');
      throw err;
    } finally {
      state.loading.value = false;
    }
  }

  async function createApplication(payload) {
    state.saving.value = true;
    state.clearMessages();
    try {
      const { data } = await applicationService.create(payload);
      state.successMessage.value = data.message;
      return data.data?.application;
    } catch (err) {
      state.applyError(err, 'Unable to create application');
      throw err;
    } finally {
      state.saving.value = false;
    }
  }

  async function updateApplication(id, payload) {
    state.saving.value = true;
    state.clearMessages();
    try {
      const { data } = await applicationService.update(id, payload);
      currentApplication.value = data.data?.application ?? currentApplication.value;
      state.successMessage.value = data.message;
      return data.data?.application;
    } catch (err) {
      state.applyError(err, 'Unable to update application');
      throw err;
    } finally {
      state.saving.value = false;
    }
  }

  async function deleteApplication(id) {
    state.saving.value = true;
    state.clearMessages();
    try {
      const { data } = await applicationService.remove(id);
      state.successMessage.value = data.message;
      return data;
    } catch (err) {
      state.applyError(err, 'Unable to delete application');
      throw err;
    } finally {
      state.saving.value = false;
    }
  }

  async function restoreApplication(id) {
    state.saving.value = true;
    state.clearMessages();
    try {
      const { data } = await applicationService.restore(id);
      currentApplication.value = data.data?.application ?? currentApplication.value;
      state.successMessage.value = data.message;
      return data.data?.application;
    } catch (err) {
      state.applyError(err, 'Unable to restore application');
      throw err;
    } finally {
      state.saving.value = false;
    }
  }

  function setViewMode(mode) {
    viewMode.value = mode === 'card' ? 'card' : 'table';
  }

  return {
    applications,
    meta,
    currentApplication,
    viewMode,
    filters,
    ...state,
    fetchApplications,
    fetchApplication,
    createApplication,
    updateApplication,
    deleteApplication,
    restoreApplication,
    setViewMode,
  };
});
