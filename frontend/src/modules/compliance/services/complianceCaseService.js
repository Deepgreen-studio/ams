import api from '@/services/api';

export const complianceCaseService = {
  dashboard(params = {}) {
    return api.get('/compliance/dashboard', { params });
  },
  list(params = {}) {
    return api.get('/compliance/cases', { params });
  },
  get(id) {
    return api.get(`/compliance/cases/${id}`);
  },
  create(payload) {
    return api.post('/compliance/cases', payload);
  },
  update(id, payload) {
    return api.put(`/compliance/cases/${id}`, payload);
  },
  remove(id) {
    return api.delete(`/compliance/cases/${id}`);
  },
  restore(id) {
    return api.post(`/compliance/cases/${id}/restore`);
  },
};
