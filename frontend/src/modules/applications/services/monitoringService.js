import api from '@/services/api';

export const monitoringService = {
  crashDashboard(applicationId, params = {}) {
    return api.get(`/applications/${applicationId}/monitoring/crash-dashboard`, { params });
  },
  healthDashboard(applicationId, params = {}) {
    return api.get(`/applications/${applicationId}/monitoring/health-dashboard`, { params });
  },
  charts(applicationId, params = {}) {
    return api.get(`/applications/${applicationId}/monitoring/charts`, { params });
  },
  deviceStatistics(applicationId, params = {}) {
    return api.get(`/applications/${applicationId}/monitoring/device-statistics`, { params });
  },
  listCrashes(applicationId, params = {}) {
    return api.get(`/applications/${applicationId}/monitoring/crashes`, { params });
  },
  getCrash(applicationId, crashId) {
    return api.get(`/applications/${applicationId}/monitoring/crashes/${crashId}`);
  },
  createCrash(applicationId, payload) {
    return api.post(`/applications/${applicationId}/monitoring/crashes`, payload);
  },
  updateCrash(applicationId, crashId, payload) {
    return api.put(`/applications/${applicationId}/monitoring/crashes/${crashId}`, payload);
  },
  ingestCrash(applicationId, payload) {
    return api.post(`/applications/${applicationId}/monitoring/ingest/crash`, payload);
  },
  ingestApiError(applicationId, payload) {
    return api.post(`/applications/${applicationId}/monitoring/ingest/api-error`, payload);
  },
  ingestHealth(applicationId, payload) {
    return api.post(`/applications/${applicationId}/monitoring/ingest/health`, payload);
  },
  refreshHealth(applicationId) {
    return api.post(`/applications/${applicationId}/monitoring/health/refresh`);
  },
  alerts(applicationId) {
    return api.get(`/applications/${applicationId}/monitoring/alerts`);
  },
  createAlert(applicationId, payload) {
    return api.post(`/applications/${applicationId}/monitoring/alerts`, payload);
  },
  updateAlert(applicationId, alertId, payload) {
    return api.put(`/applications/${applicationId}/monitoring/alerts/${alertId}`, payload);
  },
  deleteAlert(applicationId, alertId) {
    return api.delete(`/applications/${applicationId}/monitoring/alerts/${alertId}`);
  },
  acknowledgeAlertEvent(applicationId, eventId) {
    return api.post(`/applications/${applicationId}/monitoring/alert-events/${eventId}/acknowledge`);
  },
};
