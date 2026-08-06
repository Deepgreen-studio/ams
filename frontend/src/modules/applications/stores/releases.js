import { defineStore } from 'pinia';
import { ref } from 'vue';
import { releaseService } from '@/modules/applications/services/releaseService';

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

export const useReleasesStore = defineStore('applicationReleases', () => {
  const application = ref(null);
  const releases = ref([]);
  const currentRelease = ref(null);
  const summary = ref(null);
  const calendarReleases = ref([]);
  const timelineReleases = ref([]);
  const meta = ref(null);
  const state = useAsyncState();

  async function fetchDashboard(applicationId) {
    state.loading.value = true;
    state.clearMessages();
    try {
      const { data } = await releaseService.dashboard(applicationId);
      application.value = data.data?.application ?? null;
      releases.value = data.data?.releases ?? [];
      summary.value = data.data?.summary ?? null;
      return data.data;
    } catch (err) {
      state.applyError(err, 'Unable to load release dashboard');
      throw err;
    } finally {
      state.loading.value = false;
    }
  }

  async function fetchCalendar(applicationId, params = {}) {
    state.loading.value = true;
    state.clearMessages();
    try {
      const { data } = await releaseService.calendar(applicationId, params);
      application.value = data.data?.application ?? application.value;
      calendarReleases.value = data.data?.releases ?? [];
      return calendarReleases.value;
    } catch (err) {
      state.applyError(err, 'Unable to load release calendar');
      throw err;
    } finally {
      state.loading.value = false;
    }
  }

  async function fetchTimeline(applicationId, params = {}) {
    state.loading.value = true;
    state.clearMessages();
    try {
      const { data } = await releaseService.timeline(applicationId, params);
      application.value = data.data?.application ?? application.value;
      timelineReleases.value = data.data?.releases ?? [];
      return timelineReleases.value;
    } catch (err) {
      state.applyError(err, 'Unable to load release timeline');
      throw err;
    } finally {
      state.loading.value = false;
    }
  }

  async function fetchRelease(applicationId, releaseId) {
    state.loading.value = true;
    state.clearMessages();
    try {
      const { data } = await releaseService.get(applicationId, releaseId);
      currentRelease.value = data.data?.release ?? null;
      return currentRelease.value;
    } catch (err) {
      state.applyError(err, 'Unable to load release');
      throw err;
    } finally {
      state.loading.value = false;
    }
  }

  async function createRelease(applicationId, payload) {
    state.saving.value = true;
    state.clearMessages();
    try {
      const { data } = await releaseService.create(applicationId, payload);
      state.successMessage.value = data.message;
      return data.data?.release;
    } catch (err) {
      state.applyError(err, 'Unable to create release');
      throw err;
    } finally {
      state.saving.value = false;
    }
  }

  async function updateRelease(applicationId, releaseId, payload) {
    state.saving.value = true;
    state.clearMessages();
    try {
      const { data } = await releaseService.update(applicationId, releaseId, payload);
      currentRelease.value = data.data?.release ?? currentRelease.value;
      state.successMessage.value = data.message;
      return data.data?.release;
    } catch (err) {
      state.applyError(err, 'Unable to update release');
      throw err;
    } finally {
      state.saving.value = false;
    }
  }

  async function scheduleRelease(applicationId, releaseId, payload) {
    state.saving.value = true;
    state.clearMessages();
    try {
      const { data } = await releaseService.schedule(applicationId, releaseId, payload);
      currentRelease.value = data.data?.release ?? currentRelease.value;
      state.successMessage.value = data.message;
      return data.data?.release;
    } catch (err) {
      state.applyError(err, 'Unable to schedule release');
      throw err;
    } finally {
      state.saving.value = false;
    }
  }

  async function submitApproval(applicationId, releaseId) {
    state.saving.value = true;
    state.clearMessages();
    try {
      const { data } = await releaseService.submitApproval(applicationId, releaseId);
      currentRelease.value = data.data?.release ?? currentRelease.value;
      state.successMessage.value = data.message;
      return data.data?.release;
    } catch (err) {
      state.applyError(err, 'Unable to submit release for approval');
      throw err;
    } finally {
      state.saving.value = false;
    }
  }

  async function approveRelease(applicationId, releaseId, payload = {}) {
    state.saving.value = true;
    state.clearMessages();
    try {
      const { data } = await releaseService.approve(applicationId, releaseId, payload);
      currentRelease.value = data.data?.release ?? currentRelease.value;
      state.successMessage.value = data.message;
      return data.data?.release;
    } catch (err) {
      state.applyError(err, 'Unable to approve release');
      throw err;
    } finally {
      state.saving.value = false;
    }
  }

  async function rejectRelease(applicationId, releaseId, payload) {
    state.saving.value = true;
    state.clearMessages();
    try {
      const { data } = await releaseService.reject(applicationId, releaseId, payload);
      currentRelease.value = data.data?.release ?? currentRelease.value;
      state.successMessage.value = data.message;
      return data.data?.release;
    } catch (err) {
      state.applyError(err, 'Unable to reject release');
      throw err;
    } finally {
      state.saving.value = false;
    }
  }

  async function deployRelease(applicationId, releaseId, payload = {}) {
    state.saving.value = true;
    state.clearMessages();
    try {
      const { data } = await releaseService.deploy(applicationId, releaseId, payload);
      currentRelease.value = data.data?.release ?? currentRelease.value;
      state.successMessage.value = data.message;
      return data.data?.release;
    } catch (err) {
      state.applyError(err, 'Unable to deploy release');
      throw err;
    } finally {
      state.saving.value = false;
    }
  }

  async function rollbackRelease(applicationId, releaseId, payload = {}) {
    state.saving.value = true;
    state.clearMessages();
    try {
      const { data } = await releaseService.rollback(applicationId, releaseId, payload);
      currentRelease.value = data.data?.release ?? currentRelease.value;
      state.successMessage.value = data.message;
      return data.data?.release;
    } catch (err) {
      state.applyError(err, 'Unable to rollback release');
      throw err;
    } finally {
      state.saving.value = false;
    }
  }

  return {
    application,
    releases,
    currentRelease,
    summary,
    calendarReleases,
    timelineReleases,
    meta,
    ...state,
    fetchDashboard,
    fetchCalendar,
    fetchTimeline,
    fetchRelease,
    createRelease,
    updateRelease,
    scheduleRelease,
    submitApproval,
    approveRelease,
    rejectRelease,
    deployRelease,
    rollbackRelease,
  };
});
