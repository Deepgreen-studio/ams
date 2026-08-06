import api from '@/services/api';

export const dataBreachService = {
  dashboard(params = {}) {
    return api.get('/compliance/breaches/dashboard', { params });
  },
  riskMatrix(params = {}) {
    return api.get('/compliance/breaches/risk-matrix', { params });
  },
  reports(params = {}) {
    return api.get('/compliance/breaches/reports', { params });
  },
  notifications(params = {}) {
    return api.get('/compliance/breaches/notifications', { params });
  },
  list(params = {}) {
    return api.get('/compliance/breaches', { params });
  },
  get(id) {
    return api.get(`/compliance/breaches/${id}`);
  },
  create(payload) {
    return api.post('/compliance/breaches', payload);
  },
  update(id, payload) {
    return api.put(`/compliance/breaches/${id}`, payload);
  },
  remove(id) {
    return api.delete(`/compliance/breaches/${id}`);
  },
  restore(id) {
    return api.post(`/compliance/breaches/${id}/restore`);
  },
  timeline(id) {
    return api.get(`/compliance/breaches/${id}/timeline`);
  },
  assess(id, payload) {
    return api.post(`/compliance/breaches/${id}/assess`, payload);
  },
  contain(id, payload) {
    return api.post(`/compliance/breaches/${id}/contain`, payload);
  },
  recover(id, payload) {
    return api.post(`/compliance/breaches/${id}/recover`, payload);
  },
  rootCause(id, payload) {
    return api.post(`/compliance/breaches/${id}/root-cause`, payload);
  },
  lessonsLearned(id, payload) {
    return api.post(`/compliance/breaches/${id}/lessons-learned`, payload);
  },
  affectedUsers(id, payload) {
    return api.put(`/compliance/breaches/${id}/affected-users`, payload);
  },
  close(id, payload = {}) {
    return api.post(`/compliance/breaches/${id}/close`, payload);
  },
  addAction(id, payload) {
    return api.post(`/compliance/breaches/${id}/actions`, payload);
  },
  createNotification(id, payload) {
    return api.post(`/compliance/breaches/${id}/notifications`, payload);
  },
  sendNotification(id, notificationId, payload = {}) {
    return api.post(`/compliance/breaches/${id}/notifications/${notificationId}/send`, payload);
  },
};
