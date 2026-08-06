import api from '@/services/api';

export const customerApplicationService = {
  list(params = {}) {
    return api.get('/customer-applications', { params });
  },

  history(params = {}) {
    return api.get('/customer-applications/history', { params });
  },

  get(id) {
    return api.get(`/customer-applications/${id}`);
  },

  create(payload) {
    return api.post('/customer-applications', payload);
  },

  update(id, payload) {
    return api.put(`/customer-applications/${id}`, payload);
  },

  remove(id) {
    return api.delete(`/customer-applications/${id}`);
  },

  restore(id) {
    return api.post(`/customer-applications/${id}/restore`);
  },

  timeline(id, params = {}) {
    return api.get(`/customer-applications/${id}/timeline`, { params });
  },
};
