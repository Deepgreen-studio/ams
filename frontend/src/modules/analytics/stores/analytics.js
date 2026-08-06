import { defineStore } from 'pinia';
import { ref } from 'vue';
import { analyticsService } from '@/modules/analytics/services/analyticsService';

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

function extractError(error, fallback = 'Request failed') {
  return error?.response?.data?.message || error?.message || fallback;
}

export const useAnalyticsStore = defineStore('platformAnalytics', () => {
  const filters = ref(defaultRange());
  const dashboard = ref(null);
  const notifications = ref(null);
  const automation = ref(null);
  const workflows = ref(null);
  const ai = ref(null);
  const loading = ref(false);
  const exporting = ref(false);
  const error = ref(null);
  const successMessage = ref(null);

  function params(overrides = {}) {
    return Object.fromEntries(
      Object.entries({ ...filters.value, ...overrides }).filter(([, v]) => v !== '' && v != null)
    );
  }

  function clearMessages() {
    error.value = null;
    successMessage.value = null;
  }

  async function fetchDashboard(overrides = {}) {
    loading.value = true;
    clearMessages();
    try {
      const { data } = await analyticsService.dashboard(params(overrides));
      dashboard.value = data.data ?? null;
      return dashboard.value;
    } catch (err) {
      error.value = extractError(err, 'Unable to load analytics dashboard');
      throw err;
    } finally {
      loading.value = false;
    }
  }

  async function fetchNotifications(overrides = {}) {
    loading.value = true;
    clearMessages();
    try {
      const { data } = await analyticsService.notifications(params(overrides));
      notifications.value = data.data ?? null;
      return notifications.value;
    } catch (err) {
      error.value = extractError(err, 'Unable to load delivery reports');
      throw err;
    } finally {
      loading.value = false;
    }
  }

  async function fetchAutomation(overrides = {}) {
    loading.value = true;
    clearMessages();
    try {
      const { data } = await analyticsService.automation(params(overrides));
      automation.value = data.data ?? null;
      return automation.value;
    } catch (err) {
      error.value = extractError(err, 'Unable to load automation reports');
      throw err;
    } finally {
      loading.value = false;
    }
  }

  async function fetchWorkflows(overrides = {}) {
    loading.value = true;
    clearMessages();
    try {
      const { data } = await analyticsService.workflows(params(overrides));
      workflows.value = data.data ?? null;
      return workflows.value;
    } catch (err) {
      error.value = extractError(err, 'Unable to load workflow reports');
      throw err;
    } finally {
      loading.value = false;
    }
  }

  async function fetchAi(overrides = {}) {
    loading.value = true;
    clearMessages();
    try {
      const { data } = await analyticsService.ai(params(overrides));
      ai.value = data.data ?? null;
      return ai.value;
    } catch (err) {
      error.value = extractError(err, 'Unable to load AI usage analytics');
      throw err;
    } finally {
      loading.value = false;
    }
  }

  async function exportReport(format = 'csv', report = 'overview') {
    exporting.value = true;
    clearMessages();
    try {
      const response = await analyticsService.export({
        ...params(),
        format,
        report,
      });

      if (format === 'pdf') {
        error.value =
          response?.data?.message ||
          'PDF export is architecture-ready. Use CSV or Excel, or print this page.';
        return;
      }

      const blob = response.data;
      const url = window.URL.createObjectURL(blob);
      const link = document.createElement('a');
      const extension = format === 'excel' ? 'xls' : 'csv';
      link.href = url;
      link.download = `platform-analytics-${report}-${Date.now()}.${extension}`;
      link.click();
      window.URL.revokeObjectURL(url);
      successMessage.value = `${format.toUpperCase()} export downloaded.`;
    } catch (err) {
      if (format === 'pdf') {
        error.value =
          extractError(err) ||
          'PDF export is architecture-ready. Use CSV or Excel, or print this page.';
        return;
      }
      error.value = extractError(err, 'Unable to export analytics');
      throw err;
    } finally {
      exporting.value = false;
    }
  }

  return {
    filters,
    dashboard,
    notifications,
    automation,
    workflows,
    ai,
    loading,
    exporting,
    error,
    successMessage,
    fetchDashboard,
    fetchNotifications,
    fetchAutomation,
    fetchWorkflows,
    fetchAi,
    exportReport,
  };
});
