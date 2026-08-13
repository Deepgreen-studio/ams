import { defineStore } from 'pinia';
import { ref } from 'vue';
import { dataBreachService } from '@/modules/compliance/services/dataBreachService';

const defaultFilters = () => ({
  search: '',
  status: '',
  severity: '',
  breach_type: '',
  risk_level: '',
  company: '',
  sort_by: 'created_at',
  sort_dir: 'desc',
  per_page: 10,
  page: 1,
});

const defaultNotificationFilters = () => ({
  search: '',
  status: '',
  notification_type: '',
  channel: '',
  company: '',
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

export const useDataBreachStore = defineStore('dataBreaches', () => {
  const breaches = ref([]);
  const meta = ref(null);
  const current = ref(null);
  const timeline = ref([]);
  const statistics = ref(null);
  const recentActive = ref([]);
  const regulatorQueue = ref([]);
  const riskMatrix = ref(null);
  const reports = ref(null);
  const notifications = ref([]);
  const notificationsMeta = ref(null);
  const notificationStatistics = ref(null);
  const notificationFilters = ref(defaultNotificationFilters());
  const filters = ref(defaultFilters());
  const state = useAsyncState();

  function resetFilters() {
    filters.value = defaultFilters();
  }

  function resetNotificationFilters() {
    notificationFilters.value = defaultNotificationFilters();
  }

  async function fetchDashboard(company = '') {
    state.loading.value = true;
    state.clearMessages();
    try {
      const params = company ? { company } : {};
      const { data } = await dataBreachService.dashboard(params);
      statistics.value = data.data?.statistics ?? null;
      recentActive.value = data.data?.recent_active ?? [];
      regulatorQueue.value = data.data?.regulator_queue ?? [];
    } catch (err) {
      state.applyError(err, 'Unable to load breach dashboard');
      throw err;
    } finally {
      state.loading.value = false;
    }
  }

  async function fetchRiskMatrix(company = '') {
    state.loading.value = true;
    state.clearMessages();
    try {
      const params = company ? { company } : {};
      const { data } = await dataBreachService.riskMatrix(params);
      riskMatrix.value = data.data ?? null;
      return riskMatrix.value;
    } catch (err) {
      state.applyError(err, 'Unable to load risk matrix');
      throw err;
    } finally {
      state.loading.value = false;
    }
  }

  async function fetchReports(company = '') {
    state.loading.value = true;
    state.clearMessages();
    try {
      const params = company ? { company } : {};
      const { data } = await dataBreachService.reports(params);
      reports.value = data.data ?? null;
      return reports.value;
    } catch (err) {
      state.applyError(err, 'Unable to load breach reports');
      throw err;
    } finally {
      state.loading.value = false;
    }
  }

  async function fetchNotifications(overrides = {}) {
    state.loading.value = true;
    state.clearMessages();
    notificationFilters.value = { ...notificationFilters.value, ...overrides };
    try {
      const params = Object.fromEntries(
        Object.entries(notificationFilters.value).filter(([, v]) => v !== '' && v != null)
      );
      const { data } = await dataBreachService.notifications(params);
      notifications.value = data.data?.notifications?.items ?? [];
      notificationsMeta.value = data.data?.notifications?.meta ?? null;
      notificationStatistics.value = data.data?.statistics ?? notificationStatistics.value;
    } catch (err) {
      state.applyError(err, 'Unable to load notification center');
      throw err;
    } finally {
      state.loading.value = false;
    }
  }

  async function fetchBreaches(overrides = {}) {
    state.loading.value = true;
    state.clearMessages();
    filters.value = { ...filters.value, ...overrides };
    try {
      const params = Object.fromEntries(
        Object.entries(filters.value).filter(([, v]) => v !== '' && v != null)
      );
      const { data } = await dataBreachService.list(params);
      breaches.value = data.data?.breaches?.items ?? [];
      meta.value = data.data?.breaches?.meta ?? null;
      statistics.value = data.data?.statistics ?? statistics.value;
    } catch (err) {
      state.applyError(err, 'Unable to load data breaches');
      throw err;
    } finally {
      state.loading.value = false;
    }
  }

  async function fetchBreach(id) {
    state.loading.value = true;
    state.clearMessages();
    try {
      const { data } = await dataBreachService.get(id);
      current.value = data.data?.breach ?? null;
      return current.value;
    } catch (err) {
      state.applyError(err, 'Unable to load data breach');
      throw err;
    } finally {
      state.loading.value = false;
    }
  }

  async function fetchTimeline(id) {
    try {
      const { data } = await dataBreachService.timeline(id);
      timeline.value = data.data?.timeline ?? [];
      return timeline.value;
    } catch (err) {
      state.applyError(err, 'Unable to load breach timeline');
      throw err;
    }
  }

  async function createBreach(payload) {
    state.saving.value = true;
    state.clearMessages();
    try {
      const { data } = await dataBreachService.create(payload);
      state.successMessage.value = data.message;
      return data.data?.breach;
    } catch (err) {
      state.applyError(err, 'Unable to report data breach');
      throw err;
    } finally {
      state.saving.value = false;
    }
  }

  async function runAction(method, id, payload = {}) {
    state.saving.value = true;
    state.clearMessages();
    try {
      const { data } = await dataBreachService[method](id, payload);
      current.value = data.data?.breach ?? current.value;
      state.successMessage.value = data.message;
      return data.data?.breach ?? data.data;
    } catch (err) {
      state.applyError(err, 'Unable to update data breach');
      throw err;
    } finally {
      state.saving.value = false;
    }
  }

  return {
    breaches,
    meta,
    current,
    timeline,
    statistics,
    recentActive,
    regulatorQueue,
    riskMatrix,
    reports,
    notifications,
    notificationsMeta,
    notificationStatistics,
    notificationFilters,
    filters,
    ...state,
    resetFilters,
    resetNotificationFilters,
    fetchDashboard,
    fetchRiskMatrix,
    fetchReports,
    fetchNotifications,
    fetchBreaches,
    fetchBreach,
    fetchTimeline,
    createBreach,
    assess: (id, payload) => runAction('assess', id, payload),
    contain: (id, payload) => runAction('contain', id, payload),
    recover: (id, payload) => runAction('recover', id, payload),
    rootCause: (id, payload) => runAction('rootCause', id, payload),
    lessonsLearned: (id, payload) => runAction('lessonsLearned', id, payload),
    updateAffectedUsers: (id, payload) => runAction('affectedUsers', id, payload),
    close: (id, payload) => runAction('close', id, payload),
    addAction: (id, payload) => runAction('addAction', id, payload),
    createNotification: (id, payload) => runAction('createNotification', id, payload),
    sendNotification: async (id, notificationId, payload = {}) => {
      state.saving.value = true;
      state.clearMessages();
      try {
        const { data } = await dataBreachService.sendNotification(id, notificationId, payload);
        state.successMessage.value = data.message;
        await fetchBreach(id);
        return data.data?.notification;
      } catch (err) {
        state.applyError(err, 'Unable to send notification');
        throw err;
      } finally {
        state.saving.value = false;
      }
    },
    remove: async (id) => {
      state.saving.value = true;
      state.clearMessages();
      try {
        const { data } = await dataBreachService.remove(id);
        state.successMessage.value = data.message;
      } catch (err) {
        state.applyError(err, 'Unable to delete data breach');
        throw err;
      } finally {
        state.saving.value = false;
      }
    },
  };
});
