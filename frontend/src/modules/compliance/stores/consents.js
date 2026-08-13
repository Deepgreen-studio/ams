import { defineStore } from 'pinia';
import { ref } from 'vue';
import { consentService } from '@/modules/compliance/services/consentService';

const defaultFilters = () => ({
  search: '',
  status: '',
  channel: '',
  source: '',
  granted: '',
  company: '',
  sort_by: 'created_at',
  sort_dir: 'desc',
  per_page: 10,
  page: 1,
});

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

export const useConsentStore = defineStore('consents', () => {
  const consents = ref([]);
  const meta = ref(null);
  const current = ref(null);
  const timeline = ref([]);
  const history = ref([]);
  const historyMeta = ref(null);
  const types = ref([]);
  const statistics = ref(null);
  const recent = ref([]);
  const preferences = ref([]);
  const preferenceSubject = ref(null);
  const filters = ref(defaultFilters());
  const state = useAsyncState();

  function resetFilters() {
    filters.value = defaultFilters();
  }

  async function fetchDashboard(company = '') {
    state.loading.value = true;
    state.clearMessages();
    try {
      const params = company ? { company } : {};
      const { data } = await consentService.dashboard(params);
      statistics.value = data.data?.statistics ?? null;
      recent.value = data.data?.recent ?? [];
      types.value = data.data?.types ?? [];
    } catch (err) {
      state.applyError(err, 'Unable to load consent dashboard');
      throw err;
    } finally {
      state.loading.value = false;
    }
  }

  async function fetchTypes(params = { all: 1 }) {
    try {
      const { data } = await consentService.types(params);
      types.value = Array.isArray(data.data?.consent_types)
        ? data.data.consent_types
        : data.data?.consent_types?.items ?? [];
      return types.value;
    } catch (err) {
      state.applyError(err, 'Unable to load consent types');
      throw err;
    }
  }

  async function fetchConsents(overrides = {}) {
    state.loading.value = true;
    state.clearMessages();
    filters.value = { ...filters.value, ...overrides };
    try {
      const params = Object.fromEntries(
        Object.entries(filters.value).filter(([, v]) => v !== '' && v != null)
      );
      const { data } = await consentService.list(params);
      consents.value = data.data?.consents?.items ?? [];
      meta.value = data.data?.consents?.meta ?? null;
      statistics.value = data.data?.statistics ?? statistics.value;
    } catch (err) {
      state.applyError(err, 'Unable to load consents');
      throw err;
    } finally {
      state.loading.value = false;
    }
  }

  async function fetchConsent(id) {
    state.loading.value = true;
    state.clearMessages();
    try {
      const { data } = await consentService.get(id);
      current.value = data.data?.consent ?? null;
      return current.value;
    } catch (err) {
      state.applyError(err, 'Unable to load consent');
      throw err;
    } finally {
      state.loading.value = false;
    }
  }

  async function fetchTimeline(id) {
    try {
      const { data } = await consentService.timeline(id);
      timeline.value = data.data?.timeline ?? [];
      return timeline.value;
    } catch (err) {
      state.applyError(err, 'Unable to load consent timeline');
      throw err;
    }
  }

  async function fetchHistory(overrides = {}) {
    state.loading.value = true;
    state.clearMessages();
    try {
      const params = Object.fromEntries(
        Object.entries(overrides).filter(([, v]) => v !== '' && v != null)
      );
      const { data } = await consentService.history(params);
      history.value = data.data?.history?.items ?? [];
      historyMeta.value = data.data?.history?.meta ?? null;
    } catch (err) {
      state.applyError(err, 'Unable to load consent history');
      throw err;
    } finally {
      state.loading.value = false;
    }
  }

  async function createConsent(payload) {
    state.saving.value = true;
    state.clearMessages();
    try {
      const { data } = await consentService.create(payload);
      state.successMessage.value = data.message;
      return data.data?.consent;
    } catch (err) {
      state.applyError(err, 'Unable to record consent');
      throw err;
    } finally {
      state.saving.value = false;
    }
  }

  async function withdrawConsent(id, payload = {}) {
    state.saving.value = true;
    state.clearMessages();
    try {
      const { data } = await consentService.withdraw(id, payload);
      current.value = data.data?.consent ?? current.value;
      state.successMessage.value = data.message;
      return data.data?.consent;
    } catch (err) {
      state.applyError(err, 'Unable to withdraw consent');
      throw err;
    } finally {
      state.saving.value = false;
    }
  }

  async function loadPreferences(params) {
    state.loading.value = true;
    state.clearMessages();
    try {
      const { data } = await consentService.preferences(params);
      preferences.value = data.data?.preferences ?? [];
      preferenceSubject.value = data.data?.subject ?? null;
      return data.data;
    } catch (err) {
      state.applyError(err, 'Unable to load preference center');
      throw err;
    } finally {
      state.loading.value = false;
    }
  }

  async function savePreferences(payload) {
    state.saving.value = true;
    state.clearMessages();
    try {
      const { data } = await consentService.savePreferences(payload);
      state.successMessage.value = data.message;
      return data.data?.consents ?? [];
    } catch (err) {
      state.applyError(err, 'Unable to save preferences');
      throw err;
    } finally {
      state.saving.value = false;
    }
  }

  return {
    consents,
    meta,
    current,
    timeline,
    history,
    historyMeta,
    types,
    statistics,
    recent,
    preferences,
    preferenceSubject,
    filters,
    ...state,
    resetFilters,
    fetchDashboard,
    fetchTypes,
    fetchConsents,
    fetchConsent,
    fetchTimeline,
    fetchHistory,
    createConsent,
    withdrawConsent,
    loadPreferences,
    savePreferences,
  };
});
