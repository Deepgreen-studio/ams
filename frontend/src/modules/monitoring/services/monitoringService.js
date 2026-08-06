import api from '@/services/api';

export const monitoringService = {
  dashboard(params = {}) {
    return api.get('/monitoring/dashboard', { params });
  },
  apiMonitor(params = {}) {
    return api.get('/monitoring/api', { params });
  },
  webhookMonitor(params = {}) {
    return api.get('/monitoring/webhooks', { params });
  },
  queueHealth() {
    return api.get('/monitoring/queue');
  },
  responseHistory(params = {}) {
    return api.get('/monitoring/response-history', { params });
  },
  realtime(params = {}) {
    return api.get('/monitoring/realtime', { params });
  },
  integrations(params = {}) {
    return api.get('/monitoring/integrations', { params });
  },
  timeline(params = {}) {
    return api.get('/monitoring/timeline', { params });
  },
  healthChecks(params = {}) {
    return api.get('/monitoring/health-checks', { params });
  },
  services(params = {}) {
    return api.get('/monitoring/services', { params });
  },
  logs(params = {}) {
    return api.get('/monitoring/logs', { params });
  },
  capture(payload = {}) {
    return api.post('/monitoring/capture', payload);
  },
  listAlerts(params = {}) {
    return api.get('/monitoring/alerts', { params });
  },
  createAlert(payload) {
    return api.post('/monitoring/alerts', payload);
  },
  updateAlert(id, payload) {
    return api.put(`/monitoring/alerts/${id}`, payload);
  },
  removeAlert(id) {
    return api.delete(`/monitoring/alerts/${id}`);
  },
  listAlertEvents(params = {}) {
    return api.get('/monitoring/alert-events', { params });
  },
  acknowledgeEvent(id) {
    return api.post(`/monitoring/alert-events/${id}/acknowledge`);
  },
};
