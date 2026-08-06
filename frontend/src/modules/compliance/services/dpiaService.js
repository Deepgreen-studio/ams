import api from '@/services/api';

export const dpiaService = {
  dashboard(params = {}) {
    return api.get('/compliance/dpia/dashboard', { params });
  },
  riskMatrix(params = {}) {
    return api.get('/compliance/dpia/risk-matrix', { params });
  },
  templates() {
    return api.get('/compliance/dpia/templates');
  },
  list(params = {}) {
    return api.get('/compliance/dpia', { params });
  },
  get(id) {
    return api.get(`/compliance/dpia/${id}`);
  },
  create(payload) {
    return api.post('/compliance/dpia', payload);
  },
  update(id, payload) {
    return api.put(`/compliance/dpia/${id}`, payload);
  },
  remove(id) {
    return api.delete(`/compliance/dpia/${id}`);
  },
  saveWizard(id, payload) {
    return api.post(`/compliance/dpia/${id}/wizard`, payload);
  },
  submit(id, payload = {}) {
    return api.post(`/compliance/dpia/${id}/submit`, payload);
  },
  approve(id, payload = {}) {
    return api.post(`/compliance/dpia/${id}/approve`, payload);
  },
  reject(id, payload) {
    return api.post(`/compliance/dpia/${id}/reject`, payload);
  },
  risks(params = {}) {
    return api.get('/compliance/dpia/risks', { params });
  },
  mitigation(params = {}) {
    return api.get('/compliance/dpia/mitigation', { params });
  },
  getRisk(id) {
    return api.get(`/compliance/dpia/risks/${id}`);
  },
  createRisk(payload) {
    return api.post('/compliance/dpia/risks', payload);
  },
  updateRisk(id, payload) {
    return api.put(`/compliance/dpia/risks/${id}`, payload);
  },
  assessRisk(id, payload) {
    return api.post(`/compliance/dpia/risks/${id}/assess`, payload);
  },
  addRiskAction(id, payload) {
    return api.post(`/compliance/dpia/risks/${id}/actions`, payload);
  },
  completeRiskAction(id, actionId) {
    return api.post(`/compliance/dpia/risks/${id}/actions/${actionId}/complete`);
  },
  actions(params = {}) {
    return api.get('/compliance/dpia/actions', { params });
  },
};
