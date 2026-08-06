import api from '@/services/api';

export const environmentService = {
  dashboard(applicationId) {
    return api.get(`/applications/${applicationId}/environments/dashboard`);
  },
  list(applicationId, params = {}) {
    return api.get(`/applications/${applicationId}/environments`, { params });
  },
  get(applicationId, environmentId) {
    return api.get(`/applications/${applicationId}/environments/${environmentId}`);
  },
  create(applicationId, payload) {
    return api.post(`/applications/${applicationId}/environments`, payload);
  },
  update(applicationId, environmentId, payload) {
    return api.put(`/applications/${applicationId}/environments/${environmentId}`, payload);
  },
  remove(applicationId, environmentId) {
    return api.delete(`/applications/${applicationId}/environments/${environmentId}`);
  },
  switchTo(applicationId, environmentId) {
    return api.post(`/applications/${applicationId}/environments/${environmentId}/switch`);
  },
  healthCheck(applicationId, environmentId) {
    return api.post(`/applications/${applicationId}/environments/${environmentId}/health-check`);
  },
};
