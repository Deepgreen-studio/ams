import { defineStore } from 'pinia';
import { ref } from 'vue';
import { customerApplicationService } from '@/modules/customers/services/customerApplicationService';

const defaultFilters = () => ({
  search: '',
  status: '',
  ownership_type: '',
  customer: '',
  application: '',
  trashed: '',
  sort_by: 'created_at',
  sort_dir: 'desc',
  per_page: 10,
  page: 1,
});

export const useCustomerApplicationsStore = defineStore('customerApplications', () => {
  const assignments = ref([]);
  const history = ref([]);
  const meta = ref(null);
  const historyMeta = ref(null);
  const currentAssignment = ref(null);
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

  async function fetchAssignments(overrides = {}) {
    loading.value = true;
    clearMessages();
    filters.value = { ...filters.value, ...overrides };

    try {
      const params = Object.fromEntries(
        Object.entries(filters.value).filter(([, value]) => value !== '' && value !== null && value !== undefined)
      );
      const { data } = await customerApplicationService.list(params);
      assignments.value = data.data?.assignments?.items ?? [];
      meta.value = data.data?.assignments?.meta ?? null;
      return data;
    } catch (err) {
      applyError(err, 'Unable to load assigned applications');
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
      const { data } = await customerApplicationService.history(params);
      history.value = data.data?.history?.items ?? [];
      historyMeta.value = data.data?.history?.meta ?? null;
      return data;
    } catch (err) {
      applyError(err, 'Unable to load application history');
      throw err;
    } finally {
      loading.value = false;
    }
  }

  async function fetchAssignment(id) {
    loading.value = true;
    clearMessages();

    try {
      const { data } = await customerApplicationService.get(id);
      currentAssignment.value = data.data?.assignment ?? null;
      return currentAssignment.value;
    } catch (err) {
      applyError(err, 'Unable to load assignment');
      throw err;
    } finally {
      loading.value = false;
    }
  }

  async function fetchTimeline(id, limit = 50) {
    try {
      const { data } = await customerApplicationService.timeline(id, { limit });
      timeline.value = data.data?.timeline ?? [];
      return timeline.value;
    } catch (err) {
      applyError(err, 'Unable to load application timeline');
      throw err;
    }
  }

  async function assignApplication(payload) {
    saving.value = true;
    clearMessages();

    try {
      const { data } = await customerApplicationService.create(payload);
      successMessage.value = data.message || 'Application assigned successfully.';
      return data.data?.assignment;
    } catch (err) {
      applyError(err, 'Unable to assign application');
      throw err;
    } finally {
      saving.value = false;
    }
  }

  async function updateAssignment(id, payload) {
    saving.value = true;
    clearMessages();

    try {
      const { data } = await customerApplicationService.update(id, payload);
      currentAssignment.value = data.data?.assignment ?? currentAssignment.value;
      successMessage.value = data.message || 'Assignment updated successfully.';
      return data.data?.assignment;
    } catch (err) {
      applyError(err, 'Unable to update assignment');
      throw err;
    } finally {
      saving.value = false;
    }
  }

  async function archiveAssignment(id) {
    saving.value = true;
    clearMessages();

    try {
      const { data } = await customerApplicationService.remove(id);
      successMessage.value = data.message || 'Assignment archived successfully.';
      return data;
    } catch (err) {
      applyError(err, 'Unable to archive assignment');
      throw err;
    } finally {
      saving.value = false;
    }
  }

  async function restoreAssignment(id) {
    saving.value = true;
    clearMessages();

    try {
      const { data } = await customerApplicationService.restore(id);
      currentAssignment.value = data.data?.assignment ?? currentAssignment.value;
      successMessage.value = data.message || 'Assignment restored successfully.';
      return data.data?.assignment;
    } catch (err) {
      applyError(err, 'Unable to restore assignment');
      throw err;
    } finally {
      saving.value = false;
    }
  }

  function resetFilters(customer = '') {
    filters.value = { ...defaultFilters(), customer };
  }

  return {
    assignments,
    history,
    meta,
    historyMeta,
    currentAssignment,
    timeline,
    filters,
    loading,
    saving,
    error,
    fieldErrors,
    successMessage,
    fetchAssignments,
    fetchHistory,
    fetchAssignment,
    fetchTimeline,
    assignApplication,
    updateAssignment,
    archiveAssignment,
    restoreAssignment,
    resetFilters,
    clearMessages,
  };
});
