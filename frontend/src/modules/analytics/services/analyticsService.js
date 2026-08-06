import api from '@/services/api';

export const analyticsService = {
  // Enterprise foundation
  overview(params = {}) {
    return api.get('/analytics/overview', { params });
  },

  categories() {
    return api.get('/analytics/categories');
  },

  events(params = {}) {
    return api.get('/analytics/events', { params });
  },

  eventsSummary(params = {}) {
    return api.get('/analytics/events/summary', { params });
  },

  recordEvent(payload) {
    return api.post('/analytics/events', payload);
  },

  showEvent(uuid) {
    return api.get(`/analytics/events/${uuid}`);
  },

  dashboards(params = {}) {
    return api.get('/analytics/dashboards', { params });
  },

  createDashboard(payload) {
    return api.post('/analytics/dashboards', payload);
  },

  showDashboard(uuid) {
    return api.get(`/analytics/dashboards/${uuid}`);
  },

  updateDashboard(uuid, payload) {
    return api.put(`/analytics/dashboards/${uuid}`, payload);
  },

  deleteDashboard(uuid) {
    return api.delete(`/analytics/dashboards/${uuid}`);
  },

  duplicateDashboard(uuid) {
    return api.post(`/analytics/dashboards/${uuid}/duplicate`);
  },

  dashboardData(uuid, params = {}) {
    return api.get(`/analytics/dashboards/${uuid}/data`, { params });
  },

  createWidget(dashboardUuid, payload) {
    return api.post(`/analytics/dashboards/${dashboardUuid}/widgets`, payload);
  },

  updateWidget(uuid, payload) {
    return api.put(`/analytics/widgets/${uuid}`, payload);
  },

  deleteWidget(uuid) {
    return api.delete(`/analytics/widgets/${uuid}`);
  },

  widgetLibrary() {
    return api.get('/analytics/widgets/library');
  },

  templates() {
    return api.get('/analytics/dashboards/templates');
  },

  createFromTemplate(uuid, payload) {
    return api.post(`/analytics/dashboards/${uuid}/from-template`, payload);
  },

  saveLayout(uuid, payload) {
    return api.put(`/analytics/dashboards/${uuid}/layout`, payload);
  },

  shares(uuid) {
    return api.get(`/analytics/dashboards/${uuid}/shares`);
  },

  shareDashboard(uuid, payload) {
    return api.post(`/analytics/dashboards/${uuid}/shares`, payload);
  },

  revokeShare(dashboardUuid, shareUuid) {
    return api.delete(`/analytics/dashboards/${dashboardUuid}/shares/${shareUuid}`);
  },

  reports(params = {}) {
    return api.get('/analytics/reports', { params });
  },

  createReport(payload) {
    return api.post('/analytics/reports', payload);
  },

  showReport(uuid) {
    return api.get(`/analytics/reports/${uuid}`);
  },

  updateReport(uuid, payload) {
    return api.put(`/analytics/reports/${uuid}`, payload);
  },

  saveReportDesigner(uuid, payload) {
    return api.put(`/analytics/reports/${uuid}/designer`, payload);
  },

  deleteReport(uuid) {
    return api.delete(`/analytics/reports/${uuid}`);
  },

  previewReport(uuid, payload = {}) {
    return api.post(`/analytics/reports/${uuid}/preview`, payload);
  },

  runReport(uuid, payload = {}) {
    return api.post(`/analytics/reports/${uuid}/run`, payload);
  },

  reportRuns(uuid, params = {}) {
    return api.get(`/analytics/reports/${uuid}/runs`, { params });
  },

  downloadReportRun(uuid, runUuid) {
    return api.get(`/analytics/reports/${uuid}/runs/${runUuid}/download`, {
      responseType: 'blob',
    });
  },

  scheduleReport(uuid, payload) {
    return api.put(`/analytics/reports/${uuid}/schedule`, payload);
  },

  businessOverview(params = {}) {
    return api.get('/analytics/business/overview', { params });
  },

  businessCustomers(params = {}) {
    return api.get('/analytics/business/customers', { params });
  },

  businessRevenue(params = {}) {
    return api.get('/analytics/business/revenue', { params });
  },

  businessApplications(params = {}) {
    return api.get('/analytics/business/applications', { params });
  },

  businessGrowth(params = {}) {
    return api.get('/analytics/business/growth', { params });
  },

  businessForecast(params = {}) {
    return api.get('/analytics/business/forecast', { params });
  },

  businessCapture(payload = {}) {
    return api.post('/analytics/business/capture', payload);
  },

  securityOverview(params = {}) {
    return api.get('/analytics/security/overview', { params });
  },

  securityAudit(params = {}) {
    return api.get('/analytics/security/audit', { params });
  },

  securityDashboard(params = {}) {
    return api.get('/analytics/security/dashboard', { params });
  },

  securityTimeline(params = {}) {
    return api.get('/analytics/security/timeline', { params });
  },

  securityRisk(params = {}) {
    return api.get('/analytics/security/risk', { params });
  },

  securityHeatmap(params = {}) {
    return api.get('/analytics/security/heatmap', { params });
  },

  securityExport(params = {}) {
    return api.get('/analytics/security/export', { params });
  },

  securityExportCsv(params = {}) {
    return api.get('/analytics/security/export', {
      params: { ...params, format: 'csv' },
      responseType: 'blob',
    });
  },

  securityCapture(payload = {}) {
    return api.post('/analytics/security/capture', payload);
  },

  executiveOverview(params = {}) {
    return api.get('/analytics/executive/overview', { params });
  },

  executiveDashboard(type, params = {}) {
    return api.get(`/analytics/executive/${type}`, { params });
  },

  executiveScorecards(params = {}) {
    return api.get('/analytics/executive/scorecards', { params });
  },

  executiveTrends(params = {}) {
    return api.get('/analytics/executive/trends', { params });
  },

  executiveForecast(params = {}) {
    return api.get('/analytics/executive/forecast', { params });
  },

  executiveWidgets(params = {}) {
    return api.get('/analytics/executive/widgets', { params });
  },

  executiveCapture(payload = {}) {
    return api.post('/analytics/executive/capture', payload);
  },

  // Existing platform operational analytics
  dashboard(params = {}) {
    return api.get('/analytics/dashboard', { params });
  },

  notifications(params = {}) {
    return api.get('/analytics/notifications', { params });
  },

  automation(params = {}) {
    return api.get('/analytics/automation', { params });
  },

  workflows(params = {}) {
    return api.get('/analytics/workflows', { params });
  },

  ai(params = {}) {
    return api.get('/analytics/ai', { params });
  },

  export(params = {}) {
    const format = params.format || 'csv';
    return api.get('/analytics/export', {
      params,
      responseType: format === 'pdf' ? 'json' : 'blob',
    });
  },
};
