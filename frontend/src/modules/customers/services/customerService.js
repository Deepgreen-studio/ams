import api from '@/services/api';

export const customerService = {
  list(params = {}) {
    return api.get('/customers', { params });
  },

  statistics(params = {}) {
    return api.get('/customers/statistics', { params });
  },

  get(id) {
    return api.get(`/customers/${id}`);
  },

  create(payload) {
    return api.post('/customers', payload);
  },

  update(id, payload) {
    return api.put(`/customers/${id}`, payload);
  },

  remove(id) {
    return api.delete(`/customers/${id}`);
  },

  restore(id) {
    return api.post(`/customers/${id}/restore`);
  },
};
