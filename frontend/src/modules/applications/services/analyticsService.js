import api from '@/services/api';

export const analyticsService = {
  dashboard(applicationId, params = {}) {
    return api.get(`/applications/${applicationId}/analytics/dashboard`, { params });
  },
  trends(applicationId, params = {}) {
    return api.get(`/applications/${applicationId}/analytics/trends`, { params });
  },
  heatmap(applicationId, params = {}) {
    return api.get(`/applications/${applicationId}/analytics/heatmap`, { params });
  },
  countries(applicationId, params = {}) {
    return api.get(`/applications/${applicationId}/analytics/countries`, { params });
  },
  devices(applicationId, params = {}) {
    return api.get(`/applications/${applicationId}/analytics/devices`, { params });
  },
  ingest(applicationId, payload) {
    return api.post(`/applications/${applicationId}/analytics/ingest`, payload);
  },
};
