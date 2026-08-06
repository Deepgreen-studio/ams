import api from '@/services/api';

export const portalSupportService = {
  profile() {
    return api.get('/portal/me');
  },

  tickets(params = {}) {
    return api.get('/portal/support/tickets', { params });
  },

  getTicket(id) {
    return api.get(`/portal/support/tickets/${id}`);
  },

  createTicket(payload) {
    return api.post('/portal/support/tickets', payload);
  },

  messages(id) {
    return api.get(`/portal/support/tickets/${id}/messages`);
  },

  reply(id, formData) {
    return api.post(`/portal/support/tickets/${id}/messages`, formData, {
      headers: { 'Content-Type': 'multipart/form-data' },
    });
  },
};
