import api from '@/services/api';

export const licenseService = {
  list(params = {}) {
    return api.get('/customer-licenses', { params });
  },

  history(params = {}) {
    return api.get('/customer-licenses/history', { params });
  },

  get(id) {
    return api.get(`/customer-licenses/${id}`);
  },

  create(payload) {
    return api.post('/customer-licenses', payload);
  },

  update(id, payload) {
    return api.put(`/customer-licenses/${id}`, payload);
  },

  revoke(id, payload = {}) {
    return api.post(`/customer-licenses/${id}/revoke`, payload);
  },

  remove(id) {
    return api.delete(`/customer-licenses/${id}`);
  },

  restore(id) {
    return api.post(`/customer-licenses/${id}/restore`);
  },

  timeline(id, params = {}) {
    return api.get(`/customer-licenses/${id}/timeline`, { params });
  },
};
