import api from '@/services/api';

export const syncService = {
  dashboard(params = {}) {
    return api.get('/sync/dashboard', { params });
  },
  listConfigs(params = {}) {
    return api.get('/sync/configs', { params });
  },
  getConfig(id) {
    return api.get(`/sync/configs/${id}`);
  },
  createConfig(payload) {
    return api.post('/sync/configs', payload);
  },
  updateConfig(id, payload) {
    return api.put(`/sync/configs/${id}`, payload);
  },
  removeConfig(id) {
    return api.delete(`/sync/configs/${id}`);
  },
  run(id, payload = {}) {
    return api.post(`/sync/configs/${id}/run`, payload);
  },
  listRuns(params = {}) {
    return api.get('/sync/runs', { params });
  },
  getRun(id) {
    return api.get(`/sync/runs/${id}`);
  },
  listLogs(params = {}) {
    return api.get('/sync/logs', { params });
  },
};
