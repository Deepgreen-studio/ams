import api from '@/services/api';

export const applicationService = {
  list(params = {}) {
    return api.get('/applications', { params });
  },
  get(id) {
    return api.get(`/applications/${id}`);
  },
  create(payload) {
    return api.post('/applications', payload);
  },
  update(id, payload) {
    return api.put(`/applications/${id}`, payload);
  },
  remove(id) {
    return api.delete(`/applications/${id}`);
  },
  restore(id) {
    return api.post(`/applications/${id}/restore`);
  },
};
