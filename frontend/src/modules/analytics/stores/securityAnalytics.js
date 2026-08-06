import { defineStore } from 'pinia';
import { ref } from 'vue';
import { analyticsService } from '@/modules/analytics/services/analyticsService';

function extractError(error, fallback = 'Request failed') {
  return error?.response?.data?.message || error?.message || fallback;
}

export const useSecurityAnalyticsStore = defineStore('securityAnalytics', () => {
  const overview = ref(null);
  const audit = ref(null);
  const security = ref(null);
  const timeline = ref(null);
  const risk = ref(null);
  const heatmap = ref(null);
  const exportReport = ref(null);
  const loading = ref(false);
  const saving = ref(false);
  const error = ref(null);
  const successMessage = ref(null);

  function clearMessages() {
    error.value = null;
    successMessage.value = null;
  }

  async function fetchOverview(params = {}) {
    loading.value = true;
    clearMessages();
    try {
      const { data } = await analyticsService.securityOverview(params);
      overview.value = data.data ?? null;
      return overview.value;
    } catch (err) {
      error.value = extractError(err, 'Unable to load security overview');
      throw err;
    } finally {
      loading.value = false;
    }
  }

  async function fetchAudit(params = {}) {
    loading.value = true;
    clearMessages();
    try {
      const { data } = await analyticsService.securityAudit(params);
      audit.value = data.data ?? null;
      return audit.value;
    } catch (err) {
      error.value = extractError(err, 'Unable to load audit dashboard');
      throw err;
    } finally {
      loading.value = false;
    }
  }

  async function fetchSecurity(params = {}) {
    loading.value = true;
    clearMessages();
    try {
      const { data } = await analyticsService.securityDashboard(params);
      security.value = data.data ?? null;
      return security.value;
    } catch (err) {
      error.value = extractError(err, 'Unable to load security dashboard');
      throw err;
    } finally {
      loading.value = false;
    }
  }

  async function fetchTimeline(params = {}) {
    loading.value = true;
    clearMessages();
    try {
      const { data } = await analyticsService.securityTimeline(params);
      timeline.value = data.data ?? null;
      return timeline.value;
    } catch (err) {
      error.value = extractError(err, 'Unable to load threat timeline');
      throw err;
    } finally {
      loading.value = false;
    }
  }

  async function fetchRisk(params = {}) {
    loading.value = true;
    clearMessages();
    try {
      const { data } = await analyticsService.securityRisk(params);
      risk.value = data.data ?? null;
      return risk.value;
    } catch (err) {
      error.value = extractError(err, 'Unable to load risk indicators');
      throw err;
    } finally {
      loading.value = false;
    }
  }

  async function fetchHeatmap(params = {}) {
    loading.value = true;
    clearMessages();
    try {
      const { data } = await analyticsService.securityHeatmap(params);
      heatmap.value = data.data ?? null;
      return heatmap.value;
    } catch (err) {
      error.value = extractError(err, 'Unable to load activity heatmap');
      throw err;
    } finally {
      loading.value = false;
    }
  }

  async function fetchExport(params = {}) {
    loading.value = true;
    clearMessages();
    try {
      const { data } = await analyticsService.securityExport(params);
      exportReport.value = data.data ?? null;
      return exportReport.value;
    } catch (err) {
      error.value = extractError(err, 'Unable to export security report');
      throw err;
    } finally {
      loading.value = false;
    }
  }

  async function capture(payload = {}) {
    saving.value = true;
    clearMessages();
    try {
      const { data } = await analyticsService.securityCapture(payload);
      successMessage.value = data.message || 'Snapshot captured.';
      return data.data;
    } catch (err) {
      error.value = extractError(err, 'Unable to capture snapshot');
      throw err;
    } finally {
      saving.value = false;
    }
  }

  return {
    overview,
    audit,
    security,
    timeline,
    risk,
    heatmap,
    exportReport,
    loading,
    saving,
    error,
    successMessage,
    fetchOverview,
    fetchAudit,
    fetchSecurity,
    fetchTimeline,
    fetchRisk,
    fetchHeatmap,
    fetchExport,
    capture,
  };
});
