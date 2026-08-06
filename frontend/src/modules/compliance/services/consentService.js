import api from '@/services/api';

export const consentService = {
  dashboard(params = {}) {
    return api.get('/compliance/consents/dashboard', { params });
  },
  types(params = {}) {
    return api.get('/compliance/consents/types', { params });
  },
  list(params = {}) {
    return api.get('/compliance/consents', { params });
  },
  get(id) {
    return api.get(`/compliance/consents/${id}`);
  },
  create(payload) {
    return api.post('/compliance/consents', payload);
  },
  withdraw(id, payload = {}) {
    return api.post(`/compliance/consents/${id}/withdraw`, payload);
  },
  timeline(id) {
    return api.get(`/compliance/consents/${id}/timeline`);
  },
  history(params = {}) {
    return api.get('/compliance/consents/history', { params });
  },
  preferences(params = {}) {
    return api.get('/compliance/consents/preferences', { params });
  },
  savePreferences(payload) {
    return api.post('/compliance/consents/preferences', payload);
  },
};
