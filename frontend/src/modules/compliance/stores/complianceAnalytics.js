import { defineStore } from 'pinia';
import { ref } from 'vue';
import { complianceAnalyticsService } from '@/modules/compliance/services/complianceAnalyticsService';

function defaultRange() {
  const to = new Date();
  const from = new Date();
  from.setDate(to.getDate() - 29);
  return {
    from: from.toISOString().slice(0, 10),
    to: to.toISOString().slice(0, 10),
    company: '',
  };
}

function useAsyncState() {
  const loading = ref(false);
  const exporting = ref(false);
  const error = ref(null);
  const successMessage = ref(null);

  function clearMessages() {
    error.value = null;
    successMessage.value = null;
  }

  function applyError(err, fallback) {
    error.value = err?.message || fallback;
  }

  return { loading, exporting, error, successMessage, clearMessages, applyError };
}

export const useComplianceAnalyticsStore = defineStore('complianceAnalytics', () => {
  const filters = ref(defaultRange());
  const dashboard = ref(null);
  const risks = ref(null);
  const gdpr = ref(null);
  const consent = ref(null);
  const audit = ref(null);
  const state = useAsyncState();

  function resetFilters() {
    filters.value = defaultRange();
  }

  function params(overrides = {}) {
    return Object.fromEntries(
      Object.entries({ ...filters.value, ...overrides }).filter(([, v]) => v !== '' && v != null)
    );
  }

  async function fetchDashboard(overrides = {}) {
    state.loading.value = true;
    state.clearMessages();
    try {
      const { data } = await complianceAnalyticsService.dashboard(params(overrides));
      dashboard.value = data.data ?? null;
      return dashboard.value;
    } catch (err) {
      state.applyError(err, 'Unable to load compliance analytics dashboard');
      throw err;
    } finally {
      state.loading.value = false;
    }
  }

  async function fetchRisks(overrides = {}) {
    state.loading.value = true;
    state.clearMessages();
    try {
      const { data } = await complianceAnalyticsService.risks(params(overrides));
      risks.value = data.data ?? null;
      return risks.value;
    } catch (err) {
      state.applyError(err, 'Unable to load risk charts');
      throw err;
    } finally {
      state.loading.value = false;
    }
  }

  async function fetchGdpr(overrides = {}) {
    state.loading.value = true;
    state.clearMessages();
    try {
      const { data } = await complianceAnalyticsService.gdprReport(params(overrides));
      gdpr.value = data.data ?? null;
      return gdpr.value;
    } catch (err) {
      state.applyError(err, 'Unable to load GDPR report');
      throw err;
    } finally {
      state.loading.value = false;
    }
  }

  async function fetchConsent(overrides = {}) {
    state.loading.value = true;
    state.clearMessages();
    try {
      const { data } = await complianceAnalyticsService.consentReport(params(overrides));
      consent.value = data.data ?? null;
      return consent.value;
    } catch (err) {
      state.applyError(err, 'Unable to load consent report');
      throw err;
    } finally {
      state.loading.value = false;
    }
  }

  async function fetchAudit(overrides = {}) {
    state.loading.value = true;
    state.clearMessages();
    try {
      const { data } = await complianceAnalyticsService.auditReport(params(overrides));
      audit.value = data.data ?? null;
      return audit.value;
    } catch (err) {
      state.applyError(err, 'Unable to load audit report');
      throw err;
    } finally {
      state.loading.value = false;
    }
  }

  async function exportReport(format = 'csv', report = 'overview') {
    state.exporting.value = true;
    state.clearMessages();
    try {
      const response = await complianceAnalyticsService.export({
        ...params(),
        format,
        report,
      });

      const blob = response.data;
      const url = window.URL.createObjectURL(blob);
      const link = document.createElement('a');
      const extension = format === 'excel' ? 'xls' : 'csv';
      link.href = url;
      link.download = `compliance-${report}-${Date.now()}.${extension}`;
      link.click();
      window.URL.revokeObjectURL(url);
      state.successMessage.value = `${format.toUpperCase()} export downloaded.`;
      return 'downloaded';
    } catch (err) {
      if (format === 'pdf') {
        state.successMessage.value =
          err?.message ||
          'PDF export is architecture-ready. Use CSV or Excel, or print this page.';
        return 'pdf-ready';
      }
      state.applyError(err, 'Unable to export analytics');
      throw err;
    } finally {
      state.exporting.value = false;
    }
  }

  return {
    filters,
    dashboard,
    risks,
    gdpr,
    consent,
    audit,
    ...state,
    resetFilters,
    fetchDashboard,
    fetchRisks,
    fetchGdpr,
    fetchConsent,
    fetchAudit,
    exportReport,
  };
});
