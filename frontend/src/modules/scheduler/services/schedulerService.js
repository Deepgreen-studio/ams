import api from '@/services/api';

export const schedulerService = {
  dashboard() {
    return api.get('/scheduler/dashboard');
  },

  catalog() {
    return api.get('/scheduler/catalog');
  },

  statistics() {
    return api.get('/scheduler/statistics');
  },

  list(params = {}) {
    return api.get('/scheduler/jobs', { params });
  },

  show(id) {
    return api.get(`/scheduler/jobs/${id}`);
  },

  create(payload) {
    return api.post('/scheduler/jobs', payload);
  },

  update(id, payload) {
    return api.put(`/scheduler/jobs/${id}`, payload);
  },

  remove(id) {
    return api.delete(`/scheduler/jobs/${id}`);
  },

  toggle(id, isEnabled) {
    return api.post(`/scheduler/jobs/${id}/toggle`, { is_enabled: isEnabled });
  },

  runNow(id) {
    return api.post(`/scheduler/jobs/${id}/run`);
  },

  history(params = {}) {
    return api.get('/scheduler/history', { params });
  },

  running(params = {}) {
    return api.get('/scheduler/running', { params });
  },

  failed(params = {}) {
    return api.get('/scheduler/failed', { params });
  },

  logs(params = {}) {
    return api.get('/scheduler/logs', { params });
  },

  showRun(id) {
    return api.get(`/scheduler/runs/${id}`);
  },

  retry(id) {
    return api.post(`/scheduler/runs/${id}/retry`);
  },
};
