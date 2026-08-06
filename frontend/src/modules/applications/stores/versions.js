import { defineStore } from 'pinia';
import { ref } from 'vue';
import { versionService } from '@/modules/applications/services/versionService';

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

export const useVersionsStore = defineStore('applicationVersions', () => {
  const application = ref(null);
  const versions = ref([]);
  const meta = ref(null);
  const currentVersion = ref(null);
  const timeline = ref([]);
  const history = ref([]);
  const comparison = ref(null);
  const filters = ref({
    search: '',
    status: '',
    sort_by: 'semver',
    sort_dir: 'desc',
    per_page: 10,
    page: 1,
    trashed: '',
  });
  const state = useAsyncState();

  async function fetchVersions(applicationId, overrides = {}) {
    state.loading.value = true;
    state.clearMessages();
    filters.value = { ...filters.value, ...overrides };
    try {
      const params = Object.fromEntries(Object.entries(filters.value).filter(([, v]) => v !== '' && v != null));
      const { data } = await versionService.list(applicationId, params);
      versions.value = data.data?.versions?.items ?? [];
      meta.value = data.data?.versions?.meta ?? null;
      application.value = data.data?.application ?? null;
      return versions.value;
    } catch (err) {
      state.applyError(err, 'Unable to load versions');
      throw err;
    } finally {
      state.loading.value = false;
    }
  }

  async function fetchVersion(applicationId, versionId) {
    state.loading.value = true;
    state.clearMessages();
    try {
      const { data } = await versionService.get(applicationId, versionId);
      currentVersion.value = data.data?.version ?? null;
      return currentVersion.value;
    } catch (err) {
      state.applyError(err, 'Unable to load version');
      throw err;
    } finally {
      state.loading.value = false;
    }
  }

  async function createVersion(applicationId, payload) {
    state.saving.value = true;
    state.clearMessages();
    try {
      const { data } = await versionService.create(applicationId, payload);
      state.successMessage.value = data.message;
      return data.data?.version;
    } catch (err) {
      state.applyError(err, 'Unable to create version');
      throw err;
    } finally {
      state.saving.value = false;
    }
  }

  async function updateVersion(applicationId, versionId, payload) {
    state.saving.value = true;
    state.clearMessages();
    try {
      const { data } = await versionService.update(applicationId, versionId, payload);
      currentVersion.value = data.data?.version ?? currentVersion.value;
      state.successMessage.value = data.message;
      return data.data?.version;
    } catch (err) {
      state.applyError(err, 'Unable to update version');
      throw err;
    } finally {
      state.saving.value = false;
    }
  }

  async function deleteVersion(applicationId, versionId) {
    state.saving.value = true;
    state.clearMessages();
    try {
      const { data } = await versionService.remove(applicationId, versionId);
      state.successMessage.value = data.message;
      return data;
    } catch (err) {
      state.applyError(err, 'Unable to delete version');
      throw err;
    } finally {
      state.saving.value = false;
    }
  }

  async function compareVersions(applicationId, from, to) {
    state.loading.value = true;
    state.clearMessages();
    try {
      const { data } = await versionService.compare(applicationId, { from, to });
      comparison.value = data.data ?? null;
      return comparison.value;
    } catch (err) {
      state.applyError(err, 'Unable to compare versions');
      throw err;
    } finally {
      state.loading.value = false;
    }
  }

  async function fetchTimeline(applicationId) {
    state.loading.value = true;
    state.clearMessages();
    try {
      const { data } = await versionService.timeline(applicationId);
      timeline.value = data.data?.timeline ?? [];
      application.value = data.data?.application ?? application.value;
      return timeline.value;
    } catch (err) {
      state.applyError(err, 'Unable to load timeline');
      throw err;
    } finally {
      state.loading.value = false;
    }
  }

  async function fetchHistory(applicationId) {
    state.loading.value = true;
    state.clearMessages();
    try {
      const { data } = await versionService.history(applicationId);
      history.value = data.data?.history ?? [];
      application.value = data.data?.application ?? application.value;
      return history.value;
    } catch (err) {
      state.applyError(err, 'Unable to load version history');
      throw err;
    } finally {
      state.loading.value = false;
    }
  }

  return {
    application,
    versions,
    meta,
    currentVersion,
    timeline,
    history,
    comparison,
    filters,
    ...state,
    fetchVersions,
    fetchVersion,
    createVersion,
    updateVersion,
    deleteVersion,
    compareVersions,
    fetchTimeline,
    fetchHistory,
  };
});
