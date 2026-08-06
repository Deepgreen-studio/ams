import api from '@/services/api';

export const cannedResponseService = {
  dashboard() {
    return api.get('/support/canned-responses/dashboard');
  },

  list(params = {}) {
    return api.get('/support/canned-responses', { params });
  },

  get(id) {
    return api.get(`/support/canned-responses/${id}`);
  },

  create(payload) {
    return api.post('/support/canned-responses', payload);
  },

  update(id, payload) {
    return api.put(`/support/canned-responses/${id}`, payload);
  },

  remove(id) {
    return api.delete(`/support/canned-responses/${id}`);
  },

  use(id) {
    return api.post(`/support/canned-responses/${id}/use`);
  },
};
