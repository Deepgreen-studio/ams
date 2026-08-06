import api from '@/services/api';

export const supportSlaService = {
  dashboard(params = {}) {
    return api.get('/support/sla/dashboard', { params });
  },

  escalations(params = {}) {
    return api.get('/support/sla/escalations', { params });
  },

  acknowledgeEscalation(id, payload = {}) {
    return api.post(`/support/sla/escalations/${id}/acknowledge`, payload);
  },

  resolveEscalation(id, payload = {}) {
    return api.post(`/support/sla/escalations/${id}/resolve`, payload);
  },

  violations(params = {}) {
    return api.get('/support/sla/violations', { params });
  },

  policies(params = {}) {
    return api.get('/support/sla/policies', { params });
  },

  getPolicy(id) {
    return api.get(`/support/sla/policies/${id}`);
  },

  createPolicy(payload) {
    return api.post('/support/sla/policies', payload);
  },

  updatePolicy(id, payload) {
    return api.put(`/support/sla/policies/${id}`, payload);
  },

  deletePolicy(id) {
    return api.delete(`/support/sla/policies/${id}`);
  },

  calendars(params = {}) {
    return api.get('/support/sla/calendars', { params });
  },

  createCalendar(payload) {
    return api.post('/support/sla/calendars', payload);
  },

  updateCalendar(id, payload) {
    return api.put(`/support/sla/calendars/${id}`, payload);
  },

  holidays(params = {}) {
    return api.get('/support/sla/holidays', { params });
  },

  createHoliday(payload) {
    return api.post('/support/sla/holidays', payload);
  },

  updateHoliday(id, payload) {
    return api.put(`/support/sla/holidays/${id}`, payload);
  },

  deleteHoliday(id) {
    return api.delete(`/support/sla/holidays/${id}`);
  },

  evaluate() {
    return api.post('/support/sla/evaluate');
  },
};
