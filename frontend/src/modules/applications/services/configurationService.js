import api from '@/services/api';

export const configurationService = {
  catalog(applicationId) {
    return api.get(`/applications/${applicationId}/configurations/catalog`);
  },
  manager(applicationId, params = {}) {
    return api.get(`/applications/${applicationId}/configurations/manager`, { params });
  },
  list(applicationId, params = {}) {
    return api.get(`/applications/${applicationId}/configurations`, { params });
  },
  get(applicationId, configurationId) {
    return api.get(`/applications/${applicationId}/configurations/${configurationId}`);
  },
  create(applicationId, payload) {
    return api.post(`/applications/${applicationId}/configurations`, payload);
  },
  update(applicationId, configurationId, payload) {
    return api.put(`/applications/${applicationId}/configurations/${configurationId}`, payload);
  },
  remove(applicationId, configurationId) {
    return api.delete(`/applications/${applicationId}/configurations/${configurationId}`);
  },
  validate(applicationId, payload) {
    return api.post(`/applications/${applicationId}/configurations/validate`, payload);
  },
  history(applicationId, configurationId) {
    return api.get(`/applications/${applicationId}/configurations/${configurationId}/history`);
  },
  restoreHistory(applicationId, configurationId, historyId) {
    return api.post(`/applications/${applicationId}/configurations/${configurationId}/history/${historyId}/restore`);
  },
  upsertFeatureFlag(applicationId, configurationId, payload) {
    return api.post(`/applications/${applicationId}/configurations/${configurationId}/feature-flags`, payload);
  },
  toggleFeatureFlag(applicationId, configurationId, flagKey, enabled) {
    return api.post(`/applications/${applicationId}/configurations/${configurationId}/feature-flags/${flagKey}/toggle`, { enabled });
  },
};
