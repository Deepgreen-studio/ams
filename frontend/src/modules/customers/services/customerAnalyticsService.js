import api from '@/services/api';

export const customerAnalyticsService = {
  dashboard(params = {}) {
    return api.get('/customer-analytics/dashboard', { params });
  },
  health(params = {}) {
    return api.get('/customer-analytics/health', { params });
  },
  trends(params = {}) {
    return api.get('/customer-analytics/trends', { params });
  },
  usage(params = {}) {
    return api.get('/customer-analytics/usage', { params });
  },
  refresh(payload) {
    return api.post('/customer-analytics/refresh', payload);
  },
};
