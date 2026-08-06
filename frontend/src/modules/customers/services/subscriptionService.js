import api from '@/services/api';

export const subscriptionService = {
  dashboard(params = {}) {
    return api.get('/customer-subscriptions/dashboard', { params });
  },

  list(params = {}) {
    return api.get('/customer-subscriptions', { params });
  },

  statistics(params = {}) {
    return api.get('/customer-subscriptions/statistics', { params });
  },

  get(id) {
    return api.get(`/customer-subscriptions/${id}`);
  },

  create(payload) {
    return api.post('/customer-subscriptions', payload);
  },

  update(id, payload) {
    return api.put(`/customer-subscriptions/${id}`, payload);
  },

  cancel(id, payload = {}) {
    return api.post(`/customer-subscriptions/${id}/cancel`, payload);
  },

  remove(id) {
    return api.delete(`/customer-subscriptions/${id}`);
  },

  restore(id) {
    return api.post(`/customer-subscriptions/${id}/restore`);
  },

  timeline(id, params = {}) {
    return api.get(`/customer-subscriptions/${id}/timeline`, { params });
  },
};
