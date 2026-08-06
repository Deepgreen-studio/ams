import api from '@/services/api';

export const integrationService = {
  list(params = {}) {
    return api.get('/integrations', { params });
  },
  get(id) {
    return api.get(`/integrations/${id}`);
  },
  create(payload) {
    return api.post('/integrations', payload);
  },
  update(id, payload) {
    return api.put(`/integrations/${id}`, payload);
  },
  remove(id) {
    return api.delete(`/integrations/${id}`);
  },
  restore(id) {
    return api.post(`/integrations/${id}/restore`);
  },
  updateConfiguration(id, payload) {
    return api.put(`/integrations/${id}/configuration`, payload);
  },
  testConnection(id) {
    return api.post(`/integrations/${id}/test-connection`);
  },
  testAuthentication(id) {
    return api.post(`/integrations/${id}/test-authentication`);
  },
  execute(id, payload, file = null) {
    if (file) {
      const formData = new FormData();
      Object.entries(payload).forEach(([key, value]) => {
        if (value === undefined || value === null) return;
        if (typeof value === 'object') {
          formData.append(key, JSON.stringify(value));
        } else {
          formData.append(key, String(value));
        }
      });
      formData.append('file', file);
      return api.post(`/integrations/${id}/execute`, formData, {
        headers: { 'Content-Type': 'multipart/form-data' },
      });
    }
    return api.post(`/integrations/${id}/execute`, payload);
  },
  history(id, params = {}) {
    return api.get(`/integrations/${id}/history`, { params });
  },
  historyEntry(id, logId) {
    return api.get(`/integrations/${id}/history/${logId}`);
  },
};
