import { defineStore } from 'pinia';
import { ref } from 'vue';
import { supportSlaService } from '@/modules/support/services/supportSlaService';

export const useSupportSlaStore = defineStore('supportSla', () => {
  const dashboard = ref(null);
  const timers = ref([]);
  const timerMeta = ref(null);
  const statistics = ref(null);
  const escalations = ref([]);
  const escalationMeta = ref(null);
  const escalationQuery = ref({});
  const violations = ref([]);
  const violationSummary = ref(null);
  const violationMeta = ref(null);
  const policies = ref([]);
  const policyMeta = ref(null);
  const calendars = ref([]);
  const holidays = ref([]);
  const loading = ref(false);
  const saving = ref(false);
  const error = ref(null);
  const successMessage = ref(null);

  function clearMessages() {
    error.value = null;
    successMessage.value = null;
  }

  function applyError(err, fallback = 'Unexpected error') {
    error.value = err?.response?.data?.message || err?.message || fallback;
  }

  async function fetchDashboard(params = {}) {
    loading.value = true;
    clearMessages();
    try {
      const { data } = await supportSlaService.dashboard(params);
      dashboard.value = data.data ?? null;
      statistics.value = data.data?.statistics ?? null;
      timers.value = data.data?.timers?.items ?? [];
      timerMeta.value = data.data?.timers?.meta ?? null;
      return dashboard.value;
    } catch (err) {
      applyError(err, 'Unable to load SLA dashboard');
      throw err;
    } finally {
      loading.value = false;
    }
  }

  async function fetchEscalations(params = {}) {
    loading.value = true;
    error.value = null;
    escalationQuery.value = { ...params };
    try {
      const { data } = await supportSlaService.escalations(params);
      escalations.value = data.data?.escalations?.items ?? [];
      escalationMeta.value = data.data?.escalations?.meta ?? null;
      return escalations.value;
    } catch (err) {
      applyError(err, 'Unable to load escalation queue');
      throw err;
    } finally {
      loading.value = false;
    }
  }

  async function acknowledgeEscalation(id, payload = {}) {
    saving.value = true;
    error.value = null;
    try {
      const { data } = await supportSlaService.acknowledgeEscalation(id, payload);
      successMessage.value = data.message || 'Escalation acknowledged.';
      await fetchEscalations(escalationQuery.value);
      return data.data?.escalation;
    } catch (err) {
      applyError(err, 'Unable to acknowledge escalation');
      throw err;
    } finally {
      saving.value = false;
    }
  }

  async function resolveEscalation(id, payload = {}) {
    saving.value = true;
    error.value = null;
    try {
      const { data } = await supportSlaService.resolveEscalation(id, payload);
      successMessage.value = data.message || 'Escalation resolved.';
      await fetchEscalations(escalationQuery.value);
      return data.data?.escalation;
    } catch (err) {
      applyError(err, 'Unable to resolve escalation');
      throw err;
    } finally {
      saving.value = false;
    }
  }

  async function fetchViolations(params = {}) {
    loading.value = true;
    clearMessages();
    try {
      const { data } = await supportSlaService.violations(params);
      violations.value = data.data?.violations?.items ?? [];
      violationMeta.value = data.data?.violations?.meta ?? null;
      violationSummary.value = data.data?.summary ?? null;
      return violations.value;
    } catch (err) {
      applyError(err, 'Unable to load violation report');
      throw err;
    } finally {
      loading.value = false;
    }
  }

  async function fetchPolicies(params = {}) {
    loading.value = true;
    clearMessages();
    try {
      const { data } = await supportSlaService.policies(params);
      policies.value = data.data?.policies?.items ?? [];
      policyMeta.value = data.data?.policies?.meta ?? null;
      return policies.value;
    } catch (err) {
      applyError(err, 'Unable to load SLA policies');
      throw err;
    } finally {
      loading.value = false;
    }
  }

  async function fetchCalendars(params = {}) {
    try {
      const { data } = await supportSlaService.calendars(params);
      calendars.value = data.data?.calendars?.items ?? [];
      return calendars.value;
    } catch (err) {
      applyError(err, 'Unable to load calendars');
      throw err;
    }
  }

  async function fetchHolidays(params = {}) {
    try {
      const { data } = await supportSlaService.holidays(params);
      holidays.value = data.data?.holidays?.items ?? [];
      return holidays.value;
    } catch (err) {
      applyError(err, 'Unable to load holidays');
      throw err;
    }
  }

  async function evaluateNow(params = {}) {
    saving.value = true;
    clearMessages();
    try {
      const { data } = await supportSlaService.evaluate();
      successMessage.value = data.message || 'Evaluation completed.';
      await fetchDashboard(params);
      return data.data?.evaluated ?? 0;
    } catch (err) {
      applyError(err, 'Unable to evaluate SLA');
      throw err;
    } finally {
      saving.value = false;
    }
  }

  return {
    dashboard,
    timers,
    timerMeta,
    statistics,
    escalations,
    escalationMeta,
    violations,
    violationSummary,
    violationMeta,
    policies,
    policyMeta,
    calendars,
    holidays,
    loading,
    saving,
    error,
    successMessage,
    fetchDashboard,
    fetchEscalations,
    acknowledgeEscalation,
    resolveEscalation,
    fetchViolations,
    fetchPolicies,
    fetchCalendars,
    fetchHolidays,
    evaluateNow,
    clearMessages,
  };
});
