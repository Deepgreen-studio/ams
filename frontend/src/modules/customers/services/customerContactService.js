import api from '@/services/api';

export const customerContactService = {
  list(params = {}) {
    return api.get('/customer-contacts', { params });
  },

  get(id) {
    return api.get(`/customer-contacts/${id}`);
  },

  create(payload) {
    return api.post('/customer-contacts', payload);
  },

  update(id, payload) {
    return api.put(`/customer-contacts/${id}`, payload);
  },

  remove(id) {
    return api.delete(`/customer-contacts/${id}`);
  },

  restore(id) {
    return api.post(`/customer-contacts/${id}/restore`);
  },

  timeline(id, params = {}) {
    return api.get(`/customer-contacts/${id}/timeline`, { params });
  },
};
