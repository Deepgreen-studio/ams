import { defineStore } from 'pinia';
import { ref } from 'vue';
import { privacyRequestService } from '@/modules/compliance/services/privacyRequestService';

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

export const usePrivacyRequestsStore = defineStore('privacyRequests', () => {
  const requests = ref([]);
  const meta = ref(null);
  const current = ref(null);
  const timeline = ref([]);
  const statistics = ref(null);
  const recentActive = ref([]);
  const awaitingVerification = ref([]);
  const filters = ref({
    search: '',
    status: '',
    request_type: '',
    identity_verification_status: '',
    overdue: '',
    sort_by: 'created_at',
    sort_dir: 'desc',
    per_page: 10,
    page: 1,
  });
  const state = useAsyncState();

  async function fetchDashboard(company = '') {
    state.loading.value = true;
    state.clearMessages();
    try {
      const params = company ? { company } : {};
      const { data } = await privacyRequestService.dashboard(params);
      statistics.value = data.data?.statistics ?? null;
      recentActive.value = data.data?.recent_active ?? [];
      awaitingVerification.value = data.data?.awaiting_verification ?? [];
    } catch (err) {
      state.applyError(err, 'Unable to load privacy dashboard');
      throw err;
    } finally {
      state.loading.value = false;
    }
  }

  async function fetchRequests(overrides = {}) {
    state.loading.value = true;
    state.clearMessages();
    filters.value = { ...filters.value, ...overrides };
    try {
      const params = Object.fromEntries(
        Object.entries(filters.value).filter(([, v]) => v !== '' && v != null)
      );
      const { data } = await privacyRequestService.list(params);
      requests.value = data.data?.privacy_requests?.items ?? [];
      meta.value = data.data?.privacy_requests?.meta ?? null;
    } catch (err) {
      state.applyError(err, 'Unable to load privacy requests');
      throw err;
    } finally {
      state.loading.value = false;
    }
  }

  async function fetchRequest(id) {
    state.loading.value = true;
    state.clearMessages();
    try {
      const { data } = await privacyRequestService.get(id);
      current.value = data.data?.privacy_request ?? null;
      return current.value;
    } catch (err) {
      state.applyError(err, 'Unable to load privacy request');
      throw err;
    } finally {
      state.loading.value = false;
    }
  }

  async function fetchTimeline(id) {
    try {
      const { data } = await privacyRequestService.timeline(id);
      timeline.value = data.data?.timeline ?? [];
      return timeline.value;
    } catch (err) {
      state.applyError(err, 'Unable to load timeline');
      throw err;
    }
  }

  async function createRequest(payload) {
    state.saving.value = true;
    state.clearMessages();
    try {
      const { data } = await privacyRequestService.create(payload);
      state.successMessage.value = data.message;
      return data.data?.privacy_request;
    } catch (err) {
      state.applyError(err, 'Unable to create privacy request');
      throw err;
    } finally {
      state.saving.value = false;
    }
  }

  async function updateRequest(id, payload) {
    state.saving.value = true;
    state.clearMessages();
    try {
      const { data } = await privacyRequestService.update(id, payload);
      current.value = data.data?.privacy_request ?? current.value;
      state.successMessage.value = data.message;
      return data.data?.privacy_request;
    } catch (err) {
      state.applyError(err, 'Unable to update privacy request');
      throw err;
    } finally {
      state.saving.value = false;
    }
  }

  async function deleteRequest(id) {
    state.saving.value = true;
    state.clearMessages();
    try {
      const { data } = await privacyRequestService.remove(id);
      state.successMessage.value = data.message;
      return data;
    } catch (err) {
      state.applyError(err, 'Unable to delete privacy request');
      throw err;
    } finally {
      state.saving.value = false;
    }
  }

  async function runAction(action, id, payload = {}) {
    state.saving.value = true;
    state.clearMessages();
    try {
      const { data } = await action(id, payload);
      current.value = data.data?.privacy_request ?? current.value;
      state.successMessage.value = data.message;
      return data.data?.privacy_request;
    } catch (err) {
      state.applyError(err, 'Unable to update privacy request');
      throw err;
    } finally {
      state.saving.value = false;
    }
  }

  async function verifyIdentity(id, payload) {
    return runAction(privacyRequestService.verifyIdentity, id, payload);
  }

  async function approve(id, payload) {
    return runAction(privacyRequestService.approve, id, payload);
  }

  async function reject(id, payload) {
    return runAction(privacyRequestService.reject, id, payload);
  }

  async function generateExport(id) {
    return runAction(privacyRequestService.export, id);
  }

  async function confirmDeletion(id, payload) {
    return runAction(privacyRequestService.confirmDeletion, id, payload);
  }

  async function complete(id, payload = {}) {
    return runAction(privacyRequestService.complete, id, payload);
  }

  async function downloadExport(id) {
    state.saving.value = true;
    state.clearMessages();
    try {
      const response = await privacyRequestService.downloadExport(id);
      const blob = new Blob([response.data], { type: 'application/json' });
      const url = window.URL.createObjectURL(blob);
      const link = document.createElement('a');
      link.href = url;
      link.download = `${current.value?.request_number || 'privacy'}-export.json`;
      link.click();
      window.URL.revokeObjectURL(url);
    } catch (err) {
      state.applyError(err, 'Unable to download export');
      throw err;
    } finally {
      state.saving.value = false;
    }
  }

  return {
    requests,
    meta,
    current,
    timeline,
    statistics,
    recentActive,
    awaitingVerification,
    filters,
    ...state,
    fetchDashboard,
    fetchRequests,
    fetchRequest,
    fetchTimeline,
    createRequest,
    updateRequest,
    deleteRequest,
    verifyIdentity,
    approve,
    reject,
    generateExport,
    confirmDeletion,
    complete,
    downloadExport,
  };
});
