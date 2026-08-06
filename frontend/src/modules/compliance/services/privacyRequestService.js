import api from '@/services/api';

export const privacyRequestService = {
  dashboard(params = {}) {
    return api.get('/compliance/privacy-requests/dashboard', { params });
  },
  list(params = {}) {
    return api.get('/compliance/privacy-requests', { params });
  },
  get(id) {
    return api.get(`/compliance/privacy-requests/${id}`);
  },
  create(payload) {
    return api.post('/compliance/privacy-requests', payload);
  },
  update(id, payload) {
    return api.put(`/compliance/privacy-requests/${id}`, payload);
  },
  remove(id) {
    return api.delete(`/compliance/privacy-requests/${id}`);
  },
  restore(id) {
    return api.post(`/compliance/privacy-requests/${id}/restore`);
  },
  timeline(id) {
    return api.get(`/compliance/privacy-requests/${id}/timeline`);
  },
  verifyIdentity(id, payload) {
    return api.post(`/compliance/privacy-requests/${id}/verify-identity`, payload);
  },
  approve(id, payload) {
    return api.post(`/compliance/privacy-requests/${id}/approve`, payload);
  },
  reject(id, payload) {
    return api.post(`/compliance/privacy-requests/${id}/reject`, payload);
  },
  export(id) {
    return api.post(`/compliance/privacy-requests/${id}/export`);
  },
  downloadExport(id) {
    return api.get(`/compliance/privacy-requests/${id}/export/download`, {
      responseType: 'blob',
    });
  },
  confirmDeletion(id, payload) {
    return api.post(`/compliance/privacy-requests/${id}/confirm-deletion`, payload);
  },
  complete(id, payload = {}) {
    return api.post(`/compliance/privacy-requests/${id}/complete`, payload);
  },
};
