import { defineStore } from 'pinia';
import { ref } from 'vue';
import { complianceCaseService } from '@/modules/compliance/services/complianceCaseService';

const defaultFilters = () => ({
  search: '',
  status: '',
  case_type: '',
  priority: '',
  company: '',
  overdue: '',
  trashed: '',
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

export const useComplianceStore = defineStore('compliance', () => {
  const cases = ref([]);
  const meta = ref(null);
  const currentCase = ref(null);
  const statistics = ref(null);
  const recentActive = ref([]);
  const elevated = ref([]);
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
      const { data } = await complianceCaseService.dashboard(params);
      statistics.value = data.data?.statistics ?? null;
      recentActive.value = data.data?.recent_active ?? [];
      elevated.value = data.data?.elevated ?? [];
    } catch (err) {
      state.applyError(err, 'Unable to load compliance dashboard');
      throw err;
    } finally {
      state.loading.value = false;
    }
  }

  async function fetchCases(overrides = {}) {
    state.loading.value = true;
    state.clearMessages();
    filters.value = { ...filters.value, ...overrides };
    try {
      const params = Object.fromEntries(
        Object.entries(filters.value).filter(([, v]) => v !== '' && v != null)
      );
      const { data } = await complianceCaseService.list(params);
      cases.value = data.data?.cases?.items ?? [];
      meta.value = data.data?.cases?.meta ?? null;
      statistics.value = data.data?.statistics ?? statistics.value;
    } catch (err) {
      state.applyError(err, 'Unable to load compliance cases');
      throw err;
    } finally {
      state.loading.value = false;
    }
  }

  async function fetchCase(id) {
    state.loading.value = true;
    state.clearMessages();
    try {
      const { data } = await complianceCaseService.get(id);
      currentCase.value = data.data?.case ?? null;
      return currentCase.value;
    } catch (err) {
      state.applyError(err, 'Unable to load compliance case');
      throw err;
    } finally {
      state.loading.value = false;
    }
  }

  async function createCase(payload) {
    state.saving.value = true;
    state.clearMessages();
    try {
      const { data } = await complianceCaseService.create(payload);
      state.successMessage.value = data.message;
      return data.data?.case;
    } catch (err) {
      state.applyError(err, 'Unable to create compliance case');
      throw err;
    } finally {
      state.saving.value = false;
    }
  }

  async function updateCase(id, payload) {
    state.saving.value = true;
    state.clearMessages();
    try {
      const { data } = await complianceCaseService.update(id, payload);
      currentCase.value = data.data?.case ?? currentCase.value;
      state.successMessage.value = data.message;
      return data.data?.case;
    } catch (err) {
      state.applyError(err, 'Unable to update compliance case');
      throw err;
    } finally {
      state.saving.value = false;
    }
  }

  async function deleteCase(id) {
    state.saving.value = true;
    state.clearMessages();
    try {
      const { data } = await complianceCaseService.remove(id);
      state.successMessage.value = data.message;
      return data;
    } catch (err) {
      state.applyError(err, 'Unable to delete compliance case');
      throw err;
    } finally {
      state.saving.value = false;
    }
  }

  async function restoreCase(id) {
    state.saving.value = true;
    state.clearMessages();
    try {
      const { data } = await complianceCaseService.restore(id);
      currentCase.value = data.data?.case ?? currentCase.value;
      state.successMessage.value = data.message;
      return data.data?.case;
    } catch (err) {
      state.applyError(err, 'Unable to restore compliance case');
      throw err;
    } finally {
      state.saving.value = false;
    }
  }

  return {
    cases,
    meta,
    currentCase,
    statistics,
    recentActive,
    elevated,
    filters,
    ...state,
    resetFilters,
    fetchDashboard,
    fetchCases,
    fetchCase,
    createCase,
    updateCase,
    deleteCase,
    restoreCase,
  };
});
