import { defineStore } from 'pinia';
import { ref } from 'vue';
import { licenseService } from '@/modules/customers/services/licenseService';

const defaultFilters = () => ({
  search: '',
  status: '',
  customer: '',
  subscription: '',
  trashed: '',
  sort_by: 'created_at',
  sort_dir: 'desc',
  per_page: 10,
  page: 1,
});

export const useLicensesStore = defineStore('customerLicenses', () => {
  const licenses = ref([]);
  const history = ref([]);
  const statistics = ref(null);
  const meta = ref(null);
  const historyMeta = ref(null);
  const currentLicense = ref(null);
  const timeline = ref([]);
  const filters = ref(defaultFilters());
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

  function applyError(err, fallback = 'Unexpected error') {
    error.value = err?.message || fallback;
    fieldErrors.value = err?.errors || {};
  }

  async function fetchLicenses(overrides = {}) {
    loading.value = true;
    clearMessages();
    filters.value = { ...filters.value, ...overrides };

    try {
      const params = Object.fromEntries(
        Object.entries(filters.value).filter(([, value]) => value !== '' && value !== null && value !== undefined)
      );
      const { data } = await licenseService.list(params);
      licenses.value = data.data?.licenses?.items ?? [];
      meta.value = data.data?.licenses?.meta ?? null;
      statistics.value = data.data?.statistics ?? null;
      return data;
    } catch (err) {
      applyError(err, 'Unable to load licenses');
      throw err;
    } finally {
      loading.value = false;
    }
  }

  async function fetchHistory(overrides = {}) {
    loading.value = true;
    clearMessages();

    try {
      const merged = { ...filters.value, ...overrides, trashed: overrides.trashed ?? 'with' };
      const params = Object.fromEntries(
        Object.entries(merged).filter(([, value]) => value !== '' && value !== null && value !== undefined)
      );
      const { data } = await licenseService.history(params);
      history.value = data.data?.history?.items ?? [];
      historyMeta.value = data.data?.history?.meta ?? null;
      return data;
    } catch (err) {
      applyError(err, 'Unable to load license history');
      throw err;
    } finally {
      loading.value = false;
    }
  }

  async function fetchLicense(id) {
    loading.value = true;
    clearMessages();

    try {
      const { data } = await licenseService.get(id);
      currentLicense.value = data.data?.license ?? null;
      return currentLicense.value;
    } catch (err) {
      applyError(err, 'Unable to load license');
      throw err;
    } finally {
      loading.value = false;
    }
  }

  async function fetchTimeline(id, limit = 50) {
    try {
      const { data } = await licenseService.timeline(id, { limit });
      timeline.value = data.data?.timeline ?? [];
      return timeline.value;
    } catch (err) {
      applyError(err, 'Unable to load license timeline');
      throw err;
    }
  }

  async function issueLicense(payload) {
    saving.value = true;
    clearMessages();

    try {
      const { data } = await licenseService.create(payload);
      successMessage.value = data.message || 'License issued successfully.';
      return data.data?.license;
    } catch (err) {
      applyError(err, 'Unable to issue license');
      throw err;
    } finally {
      saving.value = false;
    }
  }

  async function updateLicense(id, payload) {
    saving.value = true;
    clearMessages();

    try {
      const { data } = await licenseService.update(id, payload);
      currentLicense.value = data.data?.license ?? currentLicense.value;
      successMessage.value = data.message || 'License updated successfully.';
      return data.data?.license;
    } catch (err) {
      applyError(err, 'Unable to update license');
      throw err;
    } finally {
      saving.value = false;
    }
  }

  async function revokeLicense(id, reason = '') {
    saving.value = true;
    clearMessages();

    try {
      const { data } = await licenseService.revoke(id, { reason: reason || null });
      currentLicense.value = data.data?.license ?? currentLicense.value;
      successMessage.value = data.message || 'License revoked successfully.';
      return data.data?.license;
    } catch (err) {
      applyError(err, 'Unable to revoke license');
      throw err;
    } finally {
      saving.value = false;
    }
  }

  async function archiveLicense(id) {
    saving.value = true;
    clearMessages();

    try {
      const { data } = await licenseService.remove(id);
      successMessage.value = data.message || 'License archived successfully.';
      return data;
    } catch (err) {
      applyError(err, 'Unable to archive license');
      throw err;
    } finally {
      saving.value = false;
    }
  }

  async function restoreLicense(id) {
    saving.value = true;
    clearMessages();

    try {
      const { data } = await licenseService.restore(id);
      currentLicense.value = data.data?.license ?? currentLicense.value;
      successMessage.value = data.message || 'License restored successfully.';
      return data.data?.license;
    } catch (err) {
      applyError(err, 'Unable to restore license');
      throw err;
    } finally {
      saving.value = false;
    }
  }

  function resetFilters(customer = '') {
    filters.value = { ...defaultFilters(), customer };
  }

  return {
    licenses,
    history,
    statistics,
    meta,
    historyMeta,
    currentLicense,
    timeline,
    filters,
    loading,
    saving,
    error,
    fieldErrors,
    successMessage,
    fetchLicenses,
    fetchHistory,
    fetchLicense,
    fetchTimeline,
    issueLicense,
    updateLicense,
    revokeLicense,
    archiveLicense,
    restoreLicense,
    resetFilters,
    clearMessages,
  };
});
