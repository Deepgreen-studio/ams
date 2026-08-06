import api from '@/services/api';

export const workflowService = {
  dashboard() {
    return api.get('/workflows/dashboard');
  },

  catalog() {
    return api.get('/workflows/catalog');
  },

  list(params = {}) {
    return api.get('/workflows', { params });
  },

  show(id) {
    return api.get(`/workflows/${id}`);
  },

  create(payload) {
    return api.post('/workflows', payload);
  },

  update(id, payload) {
    return api.put(`/workflows/${id}`, payload);
  },

  remove(id) {
    return api.delete(`/workflows/${id}`);
  },

  toggle(id, isEnabled) {
    return api.post(`/workflows/${id}/toggle`, { is_enabled: isEnabled });
  },

  publish(id) {
    return api.post(`/workflows/${id}/publish`);
  },

  archive(id) {
    return api.post(`/workflows/${id}/archive`);
  },

  start(id, payload = {}) {
    return api.post(`/workflows/${id}/start`, payload);
  },

  monitor() {
    return api.get('/workflows/monitor');
  },

  instances(params = {}) {
    return api.get('/workflows/instances', { params });
  },

  instance(id) {
    return api.get(`/workflows/instances/${id}`);
  },

  queue(params = {}) {
    return api.get('/workflows/queue', { params });
  },

  history(params = {}) {
    return api.get('/workflows/history', { params });
  },

  approve(id, comment = '') {
    return api.post(`/workflows/instances/${id}/approve`, { comment });
  },

  reject(id, comment = '') {
    return api.post(`/workflows/instances/${id}/reject`, { comment });
  },

  cancel(id, comment = '') {
    return api.post(`/workflows/instances/${id}/cancel`, { comment });
  },
};
