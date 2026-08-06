import api from '@/services/api';

export const supportTicketService = {
  dashboard(params = {}) {
    return api.get('/support/dashboard', { params });
  },

  list(params = {}) {
    return api.get('/support/tickets', { params });
  },

  board(params = {}) {
    return api.get('/support/tickets/board', { params });
  },

  queue(params = {}) {
    return api.get('/support/tickets/queue', { params });
  },

  agents() {
    return api.get('/support/agents');
  },

  get(id) {
    return api.get(`/support/tickets/${id}`);
  },

  timeline(id) {
    return api.get(`/support/tickets/${id}/timeline`);
  },

  create(payload) {
    return api.post('/support/tickets', payload);
  },

  update(id, payload) {
    return api.put(`/support/tickets/${id}`, payload);
  },

  transition(id, payload) {
    return api.post(`/support/tickets/${id}/transition`, payload);
  },

  assign(id, payload) {
    return api.post(`/support/tickets/${id}/assign`, payload);
  },

  close(id, payload = {}) {
    return api.post(`/support/tickets/${id}/close`, payload);
  },

  reopen(id, payload = {}) {
    return api.post(`/support/tickets/${id}/reopen`, payload);
  },

  messages(id) {
    return api.get(`/support/tickets/${id}/messages`);
  },

  postMessage(id, formData) {
    return api.post(`/support/tickets/${id}/messages`, formData);
  },

  markMessagesRead(id, payload = {}) {
    return api.post(`/support/tickets/${id}/messages/read`, payload);
  },

  deleteMessage(ticketId, messageId) {
    return api.delete(`/support/tickets/${ticketId}/messages/${messageId}`);
  },

  uploadAttachments(id, formData) {
    return api.post(`/support/tickets/${id}/attachments`, formData);
  },

  downloadAttachment(ticketId, attachmentId) {
    return api.get(`/support/tickets/${ticketId}/attachments/${attachmentId}/download`, {
      responseType: 'blob',
    });
  },

  previewAttachment(ticketId, attachmentId) {
    return api.get(`/support/tickets/${ticketId}/attachments/${attachmentId}/preview`, {
      responseType: 'blob',
    });
  },

  deleteAttachment(ticketId, attachmentId) {
    return api.delete(`/support/tickets/${ticketId}/attachments/${attachmentId}`);
  },

  remove(id) {
    return api.delete(`/support/tickets/${id}`);
  },

  restore(id) {
    return api.post(`/support/tickets/${id}/restore`);
  },
};
