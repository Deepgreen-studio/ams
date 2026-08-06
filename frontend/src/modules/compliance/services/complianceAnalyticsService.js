import api from '@/services/api';

export const complianceAnalyticsService = {
  dashboard(params = {}) {
    return api.get('/compliance/analytics/dashboard', { params });
  },
  risks(params = {}) {
    return api.get('/compliance/analytics/risks', { params });
  },
  gdprReport(params = {}) {
    return api.get('/compliance/analytics/reports/gdpr', { params });
  },
  consentReport(params = {}) {
    return api.get('/compliance/analytics/reports/consent', { params });
  },
  auditReport(params = {}) {
    return api.get('/compliance/analytics/reports/audit', { params });
  },
  export(params = {}) {
    const format = params.format || 'csv';
    return api.get('/compliance/analytics/export', {
      params,
      responseType: format === 'pdf' ? 'json' : 'blob',
    });
  },
};
