import api from '@/services/api';

export const auditService = {
  activityLogs(params = {}) {
    return api.get('/activity-logs', { params });
  },
  activityLog(id) {
    return api.get(`/activity-logs/${id}`);
  },
  exportActivityLogs(params = {}) {
    return api.get('/activity-logs/export', {
      params: { format: 'csv', ...params },
      responseType: 'blob',
    });
  },
  auditLogs(params = {}) {
    return api.get('/audit-logs', { params });
  },
  auditLog(id) {
    return api.get(`/audit-logs/${id}`);
  },
  loginHistory(params = {}) {
    return api.get('/login-history', { params });
  },
  systemEvents(params = {}) {
    return api.get('/system-events', { params });
  },
  apiLogs(params = {}) {
    return api.get('/api-logs', { params });
  },
  errorLogs(params = {}) {
    return api.get('/error-logs', { params });
  },
};
