import api from '@/services/api';

export const versionService = {
  list(applicationId, params = {}) {
    return api.get(`/applications/${applicationId}/versions`, { params });
  },
  get(applicationId, versionId) {
    return api.get(`/applications/${applicationId}/versions/${versionId}`);
  },
  create(applicationId, payload) {
    return api.post(`/applications/${applicationId}/versions`, payload);
  },
  update(applicationId, versionId, payload) {
    return api.put(`/applications/${applicationId}/versions/${versionId}`, payload);
  },
  remove(applicationId, versionId) {
    return api.delete(`/applications/${applicationId}/versions/${versionId}`);
  },
  compare(applicationId, params = {}) {
    return api.get(`/applications/${applicationId}/versions/compare`, { params });
  },
  timeline(applicationId) {
    return api.get(`/applications/${applicationId}/versions/timeline`);
  },
  history(applicationId) {
    return api.get(`/applications/${applicationId}/versions/history`);
  },
};
