import { defineStore } from 'pinia';
import { computed, ref } from 'vue';
import { analyticsService } from '@/modules/analytics/services/analyticsService';

function defaultRange() {
  const to = new Date();
  const from = new Date();
  from.setDate(to.getDate() - 29);
  return {
    from: from.toISOString().slice(0, 10),
    to: to.toISOString().slice(0, 10),
    company: '',
    category: '',
    search: '',
    kind: '',
    status: '',
    page: 1,
    per_page: 15,
  };
}

function extractError(error, fallback = 'Request failed') {
  return error?.response?.data?.message || error?.message || fallback;
}

export const useEnterpriseAnalyticsStore = defineStore('enterpriseAnalytics', () => {
  const filters = ref(defaultRange());
  const overview = ref(null);
  const categories = ref([]);
  const dashboards = ref([]);
  const dashboardsMeta = ref(null);
  const savedViews = ref([]);
  const savedViewsMeta = ref(null);
  const events = ref([]);
  const eventsMeta = ref(null);
  const eventsSummary = ref(null);
  const currentDashboard = ref(null);
  const dashboardWidgets = ref([]);
  const widgetLibrary = ref(null);
  const templates = ref([]);
  const shares = ref([]);
  const reports = ref([]);
  const reportsMeta = ref(null);
  const currentReport = ref(null);
  const reportPreview = ref(null);
  const reportRuns = ref([]);
  const reportRunsMeta = ref(null);
  const lastReportRun = ref(null);
  const columnCatalog = ref([]);
  const reportTypes = ref([]);
  const reportFormats = ref(['csv', 'excel', 'pdf', 'print', 'json']);
  const loading = ref(false);
  const saving = ref(false);
  const error = ref(null);
  const successMessage = ref(null);
  const fieldErrors = ref({});

  const hasDashboards = computed(() => dashboards.value.length > 0);

  function params(overrides = {}) {
    return Object.fromEntries(
      Object.entries({ ...filters.value, ...overrides }).filter(([, v]) => v !== '' && v != null)
    );
  }

  function clearMessages() {
    error.value = null;
    successMessage.value = null;
    fieldErrors.value = {};
  }

  async function fetchOverview(overrides = {}) {
    loading.value = true;
    clearMessages();
    try {
      const { data } = await analyticsService.overview(params(overrides));
      overview.value = data.data ?? null;
      categories.value = overview.value?.supported_categories || [];
      return overview.value;
    } catch (err) {
      error.value = extractError(err, 'Unable to load analytics overview');
      throw err;
    } finally {
      loading.value = false;
    }
  }

  async function fetchDashboards(overrides = {}) {
    loading.value = true;
    clearMessages();
    try {
      const { data } = await analyticsService.dashboards(
        params({ kind: 'dashboard', is_template: false, ...overrides })
      );
      const payload = data.data?.dashboards || {};
      dashboards.value = payload.items || [];
      dashboardsMeta.value = payload.meta || null;
      return dashboards.value;
    } catch (err) {
      error.value = extractError(err, 'Unable to load dashboards');
      throw err;
    } finally {
      loading.value = false;
    }
  }

  async function fetchSavedViews(overrides = {}) {
    loading.value = true;
    clearMessages();
    try {
      const { data } = await analyticsService.dashboards(
        params({ kind: 'saved_view', ...overrides })
      );
      const payload = data.data?.dashboards || {};
      savedViews.value = payload.items || [];
      savedViewsMeta.value = payload.meta || null;
      return savedViews.value;
    } catch (err) {
      error.value = extractError(err, 'Unable to load saved views');
      throw err;
    } finally {
      loading.value = false;
    }
  }

  async function fetchEvents(overrides = {}) {
    loading.value = true;
    clearMessages();
    try {
      const { data } = await analyticsService.events(params(overrides));
      const payload = data.data?.events || {};
      events.value = payload.items || [];
      eventsMeta.value = payload.meta || null;
      return events.value;
    } catch (err) {
      error.value = extractError(err, 'Unable to load analytics events');
      throw err;
    } finally {
      loading.value = false;
    }
  }

  async function fetchEventsSummary(overrides = {}) {
    try {
      const { data } = await analyticsService.eventsSummary(params(overrides));
      eventsSummary.value = data.data ?? null;
      return eventsSummary.value;
    } catch (err) {
      error.value = extractError(err, 'Unable to load event summary');
      throw err;
    }
  }

  async function createDashboard(payload) {
    saving.value = true;
    clearMessages();
    try {
      const { data } = await analyticsService.createDashboard(payload);
      successMessage.value = data.message || 'Dashboard created.';
      return data.data?.dashboard ?? null;
    } catch (err) {
      error.value = extractError(err, 'Unable to create dashboard');
      fieldErrors.value = err?.response?.data?.errors || {};
      throw err;
    } finally {
      saving.value = false;
    }
  }

  async function createSavedView(payload) {
    return createDashboard({ ...payload, kind: 'saved_view' });
  }

  async function loadDashboardData(uuid, overrides = {}) {
    loading.value = true;
    clearMessages();
    try {
      const { data } = await analyticsService.dashboardData(uuid, params(overrides));
      currentDashboard.value = data.data?.dashboard ?? null;
      dashboardWidgets.value = data.data?.widgets || [];
      if (data.data?.filters) {
        filters.value = {
          ...filters.value,
          from: data.data.filters.from || filters.value.from,
          to: data.data.filters.to || filters.value.to,
          category: data.data.filters.category || filters.value.category,
        };
      }
      return data.data;
    } catch (err) {
      error.value = extractError(err, 'Unable to load dashboard data');
      throw err;
    } finally {
      loading.value = false;
    }
  }

  async function createWidget(dashboardUuid, payload) {
    saving.value = true;
    clearMessages();
    try {
      const { data } = await analyticsService.createWidget(dashboardUuid, payload);
      successMessage.value = data.message || 'Widget created.';
      return data.data?.widget ?? null;
    } catch (err) {
      error.value = extractError(err, 'Unable to create widget');
      fieldErrors.value = err?.response?.data?.errors || {};
      throw err;
    } finally {
      saving.value = false;
    }
  }

  async function deleteDashboard(uuid) {
    saving.value = true;
    clearMessages();
    try {
      await analyticsService.deleteDashboard(uuid);
      successMessage.value = 'Dashboard deleted.';
      dashboards.value = dashboards.value.filter((item) => item.uuid !== uuid);
      savedViews.value = savedViews.value.filter((item) => item.uuid !== uuid);
    } catch (err) {
      error.value = extractError(err, 'Unable to delete dashboard');
      throw err;
    } finally {
      saving.value = false;
    }
  }

  async function duplicateDashboard(uuid) {
    saving.value = true;
    clearMessages();
    try {
      const { data } = await analyticsService.duplicateDashboard(uuid);
      successMessage.value = data.message || 'Dashboard duplicated.';
      return data.data?.dashboard ?? null;
    } catch (err) {
      error.value = extractError(err, 'Unable to duplicate dashboard');
      throw err;
    } finally {
      saving.value = false;
    }
  }

  async function updateDashboard(uuid, payload) {
    saving.value = true;
    clearMessages();
    try {
      const { data } = await analyticsService.updateDashboard(uuid, payload);
      currentDashboard.value = data.data?.dashboard ?? currentDashboard.value;
      successMessage.value = data.message || 'Dashboard settings saved.';
      return currentDashboard.value;
    } catch (err) {
      error.value = extractError(err, 'Unable to update dashboard');
      fieldErrors.value = err?.response?.data?.errors || {};
      throw err;
    } finally {
      saving.value = false;
    }
  }

  async function fetchWidgetLibrary() {
    const { data } = await analyticsService.widgetLibrary();
    widgetLibrary.value = data.data ?? null;
    return widgetLibrary.value;
  }

  async function fetchTemplates() {
    loading.value = true;
    clearMessages();
    try {
      const { data } = await analyticsService.templates();
      templates.value = data.data?.templates || [];
      return templates.value;
    } catch (err) {
      error.value = extractError(err, 'Unable to load templates');
      throw err;
    } finally {
      loading.value = false;
    }
  }

  async function createFromTemplate(uuid, payload) {
    saving.value = true;
    clearMessages();
    try {
      const { data } = await analyticsService.createFromTemplate(uuid, payload);
      successMessage.value = data.message || 'Dashboard created from template.';
      return data.data?.dashboard ?? null;
    } catch (err) {
      error.value = extractError(err, 'Unable to create from template');
      throw err;
    } finally {
      saving.value = false;
    }
  }

  async function saveLayout(uuid, payload) {
    saving.value = true;
    clearMessages();
    try {
      const { data } = await analyticsService.saveLayout(uuid, payload);
      currentDashboard.value = data.data?.dashboard ?? currentDashboard.value;
      successMessage.value = data.message || 'Layout saved.';
      return currentDashboard.value;
    } catch (err) {
      error.value = extractError(err, 'Unable to save layout');
      throw err;
    } finally {
      saving.value = false;
    }
  }

  async function deleteWidget(uuid) {
    saving.value = true;
    clearMessages();
    try {
      await analyticsService.deleteWidget(uuid);
      dashboardWidgets.value = dashboardWidgets.value.filter((item) => item.uuid !== uuid);
      successMessage.value = 'Widget removed.';
    } catch (err) {
      error.value = extractError(err, 'Unable to delete widget');
      throw err;
    } finally {
      saving.value = false;
    }
  }

  async function fetchShares(uuid) {
    const { data } = await analyticsService.shares(uuid);
    shares.value = data.data?.shares || [];
    return shares.value;
  }

  async function shareDashboard(uuid, payload) {
    saving.value = true;
    clearMessages();
    try {
      const { data } = await analyticsService.shareDashboard(uuid, payload);
      successMessage.value = data.message || 'Dashboard shared.';
      await fetchShares(uuid);
      return data.data?.share ?? null;
    } catch (err) {
      error.value = extractError(err, 'Unable to share dashboard');
      throw err;
    } finally {
      saving.value = false;
    }
  }

  async function revokeShare(dashboardUuid, shareUuid) {
    saving.value = true;
    clearMessages();
    try {
      await analyticsService.revokeShare(dashboardUuid, shareUuid);
      successMessage.value = 'Share revoked.';
      await fetchShares(dashboardUuid);
    } catch (err) {
      error.value = extractError(err, 'Unable to revoke share');
      throw err;
    } finally {
      saving.value = false;
    }
  }

  async function fetchReports(overrides = {}) {
    loading.value = true;
    clearMessages();
    try {
      const { data } = await analyticsService.reports(params(overrides));
      const payload = data.data?.reports || {};
      reports.value = payload.items || [];
      reportsMeta.value = payload.meta || null;
      columnCatalog.value = data.data?.column_catalog || columnCatalog.value;
      reportTypes.value = data.data?.report_types || reportTypes.value;
      reportFormats.value = data.data?.formats || reportFormats.value;
      return reports.value;
    } catch (err) {
      error.value = extractError(err, 'Unable to load reports');
      throw err;
    } finally {
      loading.value = false;
    }
  }

  async function fetchReport(uuid) {
    loading.value = true;
    clearMessages();
    try {
      const { data } = await analyticsService.showReport(uuid);
      currentReport.value = data.data?.report ?? null;
      columnCatalog.value = data.data?.column_catalog || columnCatalog.value;
      return currentReport.value;
    } catch (err) {
      error.value = extractError(err, 'Unable to load report');
      throw err;
    } finally {
      loading.value = false;
    }
  }

  async function createReport(payload) {
    saving.value = true;
    clearMessages();
    try {
      const { data } = await analyticsService.createReport(payload);
      successMessage.value = data.message || 'Report created.';
      currentReport.value = data.data?.report ?? null;
      await fetchReports();
      return currentReport.value;
    } catch (err) {
      error.value = extractError(err, 'Unable to create report');
      fieldErrors.value = err?.response?.data?.errors || {};
      throw err;
    } finally {
      saving.value = false;
    }
  }

  async function saveReportDesigner(uuid, payload) {
    saving.value = true;
    clearMessages();
    try {
      const { data } = await analyticsService.saveReportDesigner(uuid, payload);
      currentReport.value = data.data?.report ?? currentReport.value;
      successMessage.value = data.message || 'Designer saved.';
      return currentReport.value;
    } catch (err) {
      error.value = extractError(err, 'Unable to save designer');
      fieldErrors.value = err?.response?.data?.errors || {};
      throw err;
    } finally {
      saving.value = false;
    }
  }

  async function deleteReport(uuid) {
    saving.value = true;
    clearMessages();
    try {
      await analyticsService.deleteReport(uuid);
      reports.value = reports.value.filter((item) => item.uuid !== uuid);
      successMessage.value = 'Report deleted.';
    } catch (err) {
      error.value = extractError(err, 'Unable to delete report');
      throw err;
    } finally {
      saving.value = false;
    }
  }

  async function previewReport(uuid, payload = {}) {
    saving.value = true;
    clearMessages();
    try {
      const { data } = await analyticsService.previewReport(uuid, payload);
      reportPreview.value = data.data?.dataset ?? null;
      return reportPreview.value;
    } catch (err) {
      error.value = extractError(err, 'Unable to preview report');
      throw err;
    } finally {
      saving.value = false;
    }
  }

  async function runReport(uuid, payload = {}) {
    saving.value = true;
    clearMessages();
    try {
      const { data } = await analyticsService.runReport(uuid, payload);
      lastReportRun.value = data.data?.run ?? null;
      reportPreview.value = data.data?.dataset ?? reportPreview.value;
      successMessage.value = data.message || 'Report generated.';
      if (lastReportRun.value?.uuid && lastReportRun.value.status === 'completed') {
        await downloadReportRun(
          uuid,
          lastReportRun.value.uuid,
          lastReportRun.value.file_name || `report.${payload.format || 'csv'}`
        );
      }
      return lastReportRun.value;
    } catch (err) {
      error.value = extractError(err, 'Unable to generate report');
      throw err;
    } finally {
      saving.value = false;
    }
  }

  async function fetchReportRuns(uuid, overrides = {}) {
    const { data } = await analyticsService.reportRuns(uuid, overrides);
    const payload = data.data?.runs || {};
    reportRuns.value = payload.items || [];
    reportRunsMeta.value = payload.meta || null;
    return reportRuns.value;
  }

  async function downloadReportRun(uuid, runUuid, filename = 'report.bin') {
    const response = await analyticsService.downloadReportRun(uuid, runUuid);
    const blob = new Blob([response.data], {
      type: response.headers['content-type'] || 'application/octet-stream',
    });
    const url = window.URL.createObjectURL(blob);
    const anchor = document.createElement('a');
    anchor.href = url;
    anchor.download = filename;
    document.body.appendChild(anchor);
    anchor.click();
    anchor.remove();
    window.URL.revokeObjectURL(url);
  }

  async function scheduleReport(uuid, payload) {
    saving.value = true;
    clearMessages();
    try {
      const { data } = await analyticsService.scheduleReport(uuid, payload);
      currentReport.value = data.data?.report ?? currentReport.value;
      successMessage.value = data.message || 'Schedule updated.';
      return currentReport.value;
    } catch (err) {
      error.value = extractError(err, 'Unable to update schedule');
      throw err;
    } finally {
      saving.value = false;
    }
  }

  return {
    filters,
    overview,
    categories,
    dashboards,
    dashboardsMeta,
    savedViews,
    savedViewsMeta,
    events,
    eventsMeta,
    eventsSummary,
    currentDashboard,
    dashboardWidgets,
    widgetLibrary,
    templates,
    shares,
    reports,
    reportsMeta,
    currentReport,
    reportPreview,
    reportRuns,
    reportRunsMeta,
    lastReportRun,
    columnCatalog,
    reportTypes,
    reportFormats,
    loading,
    saving,
    error,
    successMessage,
    fieldErrors,
    hasDashboards,
    fetchOverview,
    fetchDashboards,
    fetchSavedViews,
    fetchEvents,
    fetchEventsSummary,
    createDashboard,
    createSavedView,
    loadDashboardData,
    createWidget,
    deleteDashboard,
    duplicateDashboard,
    updateDashboard,
    fetchWidgetLibrary,
    fetchTemplates,
    createFromTemplate,
    saveLayout,
    deleteWidget,
    fetchShares,
    shareDashboard,
    revokeShare,
    fetchReports,
    fetchReport,
    createReport,
    saveReportDesigner,
    deleteReport,
    previewReport,
    runReport,
    fetchReportRuns,
    downloadReportRun,
    scheduleReport,
  };
});
