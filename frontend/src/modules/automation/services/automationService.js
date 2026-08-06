import api from '@/services/api';

export const automationService = {
  dashboard() {
    return api.get('/automation/dashboard');
  },

  catalog() {
    return api.get('/automation/catalog');
  },

  list(params = {}) {
    return api.get('/automation/rules', { params });
  },

  show(id) {
    return api.get(`/automation/rules/${id}`);
  },

  create(payload) {
    return api.post('/automation/rules', payload);
  },

  update(id, payload) {
    return api.put(`/automation/rules/${id}`, payload);
  },

  remove(id) {
    return api.delete(`/automation/rules/${id}`);
  },

  toggle(id, isEnabled) {
    return api.post(`/automation/rules/${id}/toggle`, {
      is_enabled: isEnabled,
    });
  },

  test(id, context = {}) {
    return api.post(`/automation/rules/${id}/test`, { context });
  },

  logs(params = {}) {
    return api.get('/automation/logs', { params });
  },
};
