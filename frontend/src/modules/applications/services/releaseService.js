import api from '@/services/api';

export const releaseService = {
  dashboard(applicationId) {
    return api.get(`/applications/${applicationId}/releases/dashboard`);
  },
  calendar(applicationId, params = {}) {
    return api.get(`/applications/${applicationId}/releases/calendar`, { params });
  },
  timeline(applicationId, params = {}) {
    return api.get(`/applications/${applicationId}/releases/timeline`, { params });
  },
  list(applicationId, params = {}) {
    return api.get(`/applications/${applicationId}/releases`, { params });
  },
  get(applicationId, releaseId) {
    return api.get(`/applications/${applicationId}/releases/${releaseId}`);
  },
  create(applicationId, payload) {
    return api.post(`/applications/${applicationId}/releases`, payload);
  },
  update(applicationId, releaseId, payload) {
    return api.put(`/applications/${applicationId}/releases/${releaseId}`, payload);
  },
  remove(applicationId, releaseId) {
    return api.delete(`/applications/${applicationId}/releases/${releaseId}`);
  },
  schedule(applicationId, releaseId, payload) {
    return api.post(`/applications/${applicationId}/releases/${releaseId}/schedule`, payload);
  },
  submitApproval(applicationId, releaseId) {
    return api.post(`/applications/${applicationId}/releases/${releaseId}/submit-approval`);
  },
  approve(applicationId, releaseId, payload = {}) {
    return api.post(`/applications/${applicationId}/releases/${releaseId}/approve`, payload);
  },
  reject(applicationId, releaseId, payload) {
    return api.post(`/applications/${applicationId}/releases/${releaseId}/reject`, payload);
  },
  deploy(applicationId, releaseId, payload = {}) {
    return api.post(`/applications/${applicationId}/releases/${releaseId}/deploy`, payload);
  },
  rollback(applicationId, releaseId, payload = {}) {
    return api.post(`/applications/${applicationId}/releases/${releaseId}/rollback`, payload);
  },
};
